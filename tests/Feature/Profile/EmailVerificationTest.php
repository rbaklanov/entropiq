<?php

use App\Actions\ChangeUserEmail;
use App\Actions\SendEmailVerification;
use App\Actions\VerifyUserEmail;
use App\Livewire\Settings\ProfilePage;
use App\Livewire\Settings\SettingsPage;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function verificationUrl(User $user, ?string $hash = null): string
{
    return URL::temporarySignedRoute('email.verify', now()->addHours(24), [
        'user' => $user->id,
        'hash' => $hash ?? sha1($user->email),
    ]);
}

describe('User email', function () {
    it('normalizes the address to lower case', function () {
        $user = User::factory()->create(['email' => '  Person@Example.COM ']);

        expect($user->fresh()->email)->toBe('person@example.com');
    });

    it('treats a blank address as no email', function () {
        $user = User::factory()->create(['email' => '   ']);

        expect($user->fresh()->email)->toBeNull();
    });

    it('is verified only when the address is set and confirmed', function () {
        expect(User::factory()->create()->hasVerifiedEmail())->toBeFalse()
            ->and(User::factory()->withEmail('pending@example.com')->create()->hasVerifiedEmail())->toBeFalse()
            ->and(User::factory()->withVerifiedEmail('done@example.com')->create()->hasVerifiedEmail())->toBeTrue();
    });

    it('rejects a duplicate address regardless of case', function () {
        User::factory()->withEmail('same@example.com')->create();

        User::factory()->create(['email' => 'SAME@example.com']);
    })->throws(UniqueConstraintViolationException::class);

    it('allows the address of a deleted user to be used again', function () {
        $old = User::factory()->withEmail('reuse@example.com')->create();
        $old->delete();

        $new = User::factory()->withEmail('reuse@example.com')->create();

        expect($new->fresh()->email)->toBe('reuse@example.com');
    });

    it('allows many users without an email', function () {
        User::factory()->count(3)->create();

        expect(User::whereNull('email')->count())->toBe(3);
    });
});

describe('ChangeUserEmail', function () {
    it('stores a new address as unverified and sends a confirmation', function () {
        Mail::fake();
        $user = User::factory()->withVerifiedEmail('old@example.com')->create();

        app(ChangeUserEmail::class)->execute($user, 'New@Example.com');

        expect($user->fresh()->email)->toBe('new@example.com')
            ->and($user->fresh()->email_verified_at)->toBeNull();

        Mail::assertSent(VerifyEmailMail::class, fn ($mail) => $mail->hasTo('new@example.com'));
    });

    it('does nothing when the address is unchanged', function () {
        Mail::fake();
        $user = User::factory()->withVerifiedEmail('same@example.com')->create();

        app(ChangeUserEmail::class)->execute($user, 'SAME@example.com');

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
        Mail::assertNothingSent();
    });

    it('clears the address and its verification without sending mail', function () {
        Mail::fake();
        $user = User::factory()->withVerifiedEmail()->create();

        app(ChangeUserEmail::class)->execute($user, '');

        expect($user->fresh()->email)->toBeNull()
            ->and($user->fresh()->email_verified_at)->toBeNull();
        Mail::assertNothingSent();
    });
});

describe('SendEmailVerification', function () {
    it('sends a signed link valid for 24 hours', function () {
        Mail::fake();
        $user = User::factory()->withEmail('person@example.com')->create();

        expect(app(SendEmailVerification::class)->execute($user))->toBeTrue();

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use ($user) {
            return str_contains($mail->verificationUrl, '/email/verify/'.$user->id.'/'.sha1('person@example.com'))
                && str_contains($mail->verificationUrl, 'signature=')
                && str_contains($mail->verificationUrl, 'expires=');
        });
    });

    it('returns false and does not throw when the mailer fails', function () {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));
        $user = User::factory()->withEmail()->create();

        expect(app(SendEmailVerification::class)->execute($user))->toBeFalse();
    });

    it('does not send without an address', function () {
        Mail::fake();

        expect(app(SendEmailVerification::class)->execute(User::factory()->create()))->toBeFalse();
        Mail::assertNothingSent();
    });

    it('renders the confirmation mail', function () {
        $mail = new VerifyEmailMail('https://example.com/email/verify/1/abc');

        $mail->assertSeeInHtml('https://example.com/email/verify/1/abc');
    });
});

describe('VerifyUserEmail', function () {
    it('confirms the address for a matching hash', function () {
        $user = User::factory()->withEmail('person@example.com')->create();

        expect(app(VerifyUserEmail::class)->execute($user, sha1('person@example.com')))->toBeTrue()
            ->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('rejects a hash of a different address', function () {
        $user = User::factory()->withEmail('new@example.com')->create();

        expect(app(VerifyUserEmail::class)->execute($user, sha1('old@example.com')))->toBeFalse()
            ->and($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });
});

describe('GET /email/verify/{user}/{hash}', function () {
    it('confirms the address and redirects to the profile', function () {
        $user = User::factory()->onboarded()->withEmail('person@example.com')->create(['phone_verified_at' => now()]);

        $this->actingAs($user)
            ->get(verificationUrl($user))
            ->assertRedirect(route('settings.profile'));

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('works from another device without a session', function () {
        $user = User::factory()->withEmail('person@example.com')->create();

        $this->get(verificationUrl($user))->assertRedirect();

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('rejects an unsigned link', function () {
        $user = User::factory()->withEmail('person@example.com')->create();

        $this->get('/email/verify/'.$user->id.'/'.sha1('person@example.com'))->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('rejects an expired link', function () {
        $user = User::factory()->withEmail('person@example.com')->create();
        $url = verificationUrl($user);

        $this->travel(25)->hours();

        $this->get($url)->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('rejects a link issued for the previous address', function () {
        $user = User::factory()->withEmail('old@example.com')->create();
        $url = verificationUrl($user);

        $user->update(['email' => 'new@example.com']);

        $this->get($url)->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('rejects a link with a tampered hash', function () {
        $user = User::factory()->withEmail('person@example.com')->create();

        $this->get(verificationUrl($user, sha1('someone@else.com')))->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });
});

describe('profile page', function () {
    it('saves the email and sends a confirmation', function () {
        Mail::fake();
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now(), 'name' => 'Anna']);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('email', ' Anna@Example.com ')
            ->call('save')
            ->assertHasNoErrors();

        expect($user->fresh()->email)->toBe('anna@example.com')
            ->and($user->fresh()->hasVerifiedEmail())->toBeFalse();

        Mail::assertSent(VerifyEmailMail::class, fn ($mail) => $mail->hasTo('anna@example.com'));
    });

    it('validates the address format', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now(), 'name' => 'Anna']);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('email', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['email' => 'email']);
    });

    it('rejects an address that belongs to another user', function () {
        User::factory()->withVerifiedEmail('taken@example.com')->create();
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now(), 'name' => 'Anna']);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('email', 'TAKEN@example.com')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    });

    it('lets a user keep their own address while changing the name', function () {
        Mail::fake();
        $user = User::factory()->onboarded()->withVerifiedEmail('anna@example.com')->create(['phone_verified_at' => now(), 'name' => 'Anna']);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->set('name', 'Anna B')
            ->call('save')
            ->assertHasNoErrors();

        expect($user->fresh()->name)->toBe('Anna B')
            ->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
        Mail::assertNothingSent();
    });

    it('shows the unverified status and resends the confirmation', function () {
        Mail::fake();
        $user = User::factory()->onboarded()->withEmail('anna@example.com')->create(['phone_verified_at' => now(), 'name' => 'Anna']);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->assertSeeHtml('data-testid="email-status"')
            ->assertSee(__('settings.email_unverified'))
            ->call('resendVerification');

        Mail::assertSent(VerifyEmailMail::class, 1);
    });

    it('limits resending to once a minute', function () {
        Mail::fake();
        $user = User::factory()->onboarded()->withEmail('anna@example.com')->create(['phone_verified_at' => now(), 'name' => 'Anna']);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->call('resendVerification')
            ->call('resendVerification')
            ->call('resendVerification');

        Mail::assertSent(VerifyEmailMail::class, 1);
    });

    it('shows the verified status', function () {
        $user = User::factory()->onboarded()->withVerifiedEmail('anna@example.com')->create(['phone_verified_at' => now(), 'name' => 'Anna']);

        Livewire::actingAs($user)
            ->test(ProfilePage::class)
            ->assertSee(__('settings.email_verified'))
            ->assertDontSee(__('settings.email_resend'));
    });
});

describe('weekly digest toggle in settings', function () {
    it('is hidden behind a hint without a verified email', function () {
        $user = User::factory()->onboarded()->withEmail('anna@example.com')->create(['phone_verified_at' => now()]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSeeHtml('data-testid="email-weekly-hint"')
            ->assertDontSeeHtml('wire:model.live="emailWeekly"');
    });

    it('is shown with a verified email', function () {
        $user = User::factory()->onboarded()->withVerifiedEmail('anna@example.com')->create(['phone_verified_at' => now()]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSeeHtml('wire:model.live="emailWeekly"')
            ->assertDontSeeHtml('data-testid="email-weekly-hint"');
    });
});

describe('API', function () {
    function emailApiUser(?string $email = null): array
    {
        $user = $email
            ? User::factory()->withVerifiedEmail($email)->create(['phone_verified_at' => now()])
            : User::factory()->create(['phone_verified_at' => now()]);

        return [$user, ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken, 'Accept' => 'application/json']];
    }

    it('returns the email and its verification time', function () {
        [, $headers] = emailApiUser('anna@example.com');

        $this->withHeaders($headers)->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'anna@example.com')
            ->assertJsonStructure(['data' => ['email_verified_at']]);
    });

    it('sets the email and sends a confirmation', function () {
        Mail::fake();
        [$user, $headers] = emailApiUser();

        $this->withHeaders($headers)->putJson('/api/v1/user', ['email' => 'Anna@Example.com'])
            ->assertOk()
            ->assertJsonPath('data.email', 'anna@example.com')
            ->assertJsonPath('data.email_verified_at', null);

        Mail::assertSent(VerifyEmailMail::class, fn ($mail) => $mail->hasTo('anna@example.com'));
        expect($user->fresh()->email)->toBe('anna@example.com');
    });

    it('rejects an invalid or duplicate address', function () {
        User::factory()->withVerifiedEmail('taken@example.com')->create();
        [, $headers] = emailApiUser();

        $this->withHeaders($headers)->putJson('/api/v1/user', ['email' => 'nope'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->withHeaders($headers)->putJson('/api/v1/user', ['email' => 'taken@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    });

    it('clears the email with null', function () {
        [$user, $headers] = emailApiUser('anna@example.com');

        $this->withHeaders($headers)->putJson('/api/v1/user', ['email' => null])
            ->assertOk()
            ->assertJsonPath('data.email', null);

        expect($user->fresh()->email)->toBeNull();
    });

    it('does not touch the email when the field is absent', function () {
        [$user, $headers] = emailApiUser('anna@example.com');

        $this->withHeaders($headers)->putJson('/api/v1/user', ['name' => 'Anna'])->assertOk();

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('erases the email when the account is deleted', function () {
        [$user, $headers] = emailApiUser('anna@example.com');

        $this->withHeaders($headers)->deleteJson('/api/v1/user')->assertOk();

        $deleted = User::withTrashed()->find($user->id);

        expect($deleted->email)->toBeNull()
            ->and($deleted->email_verified_at)->toBeNull();
    });
});

describe('account deletion from settings', function () {
    it('erases the email', function () {
        $user = User::factory()->onboarded()->withVerifiedEmail('anna@example.com')->create(['phone_verified_at' => now()]);

        Livewire::actingAs($user)->test(SettingsPage::class)->call('deleteAccount');

        expect(User::withTrashed()->find($user->id)->email)->toBeNull();
    });
});
