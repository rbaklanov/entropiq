<?php

use App\Enums\SubscriptionPlan;
use App\Livewire\Settings\ProfilePage;
use App\Livewire\Settings\SettingsPage;
use App\Livewire\Settings\SubscriptionPage;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.sms.demo_phone' => '79990000001', 'services.llm.driver' => 'fake']);

    $this->demo = User::factory()->onboarded()->create([
        'phone' => '79990000001',
        'name' => 'Алексей',
        'email' => null,
        'phone_verified_at' => now(),
    ]);
});

describe('demo account in the interface', function () {
    it('cannot be deleted', function () {
        Livewire::actingAs($this->demo)->test(SettingsPage::class)
            ->call('deleteAccount')
            ->assertRedirect(route('settings.index'));

        expect($this->demo->fresh()->phone)->toBe('79990000001')
            ->and($this->demo->fresh()->trashed())->toBeFalse()
            ->and(session('error'))->toBe(__('settings.demo_locked'));
    });

    it('cannot change its name and email', function () {
        Livewire::actingAs($this->demo)->test(ProfilePage::class)
            ->set('name', 'Другое имя')
            ->set('email', 'someone@example.com')
            ->call('save');

        expect($this->demo->fresh()->name)->toBe('Алексей')
            ->and($this->demo->fresh()->email)->toBeNull();
    });

    it('cannot start a payment', function () {
        Livewire::actingAs($this->demo)->test(SubscriptionPage::class)->call('subscribe');

        expect(Payment::count())->toBe(0);
    });

    it('leaves ordinary users able to delete their account', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        Livewire::actingAs($user)->test(SettingsPage::class)->call('deleteAccount');

        expect(User::find($user->id))->toBeNull();
    });
});

describe('demo account in the API', function () {
    it('rejects profile changes and deletion', function () {
        $headers = ['Authorization' => 'Bearer '.$this->demo->createToken('test')->plainTextToken];

        $this->withHeaders($headers)->putJson('/api/v1/user', ['name' => 'Другое имя'])->assertForbidden();
        $this->withHeaders($headers)->deleteJson('/api/v1/user')->assertForbidden();

        expect($this->demo->fresh()->name)->toBe('Алексей')
            ->and($this->demo->tokens()->count())->toBe(1);
    });
});

describe('isDemo', function () {
    it('matches only the configured phone', function () {
        expect($this->demo->isDemo())->toBeTrue()
            ->and(User::factory()->create()->isDemo())->toBeFalse();

        config(['services.sms.demo_phone' => '']);

        expect($this->demo->isDemo())->toBeFalse();
    });
});

describe('demo:reset', function () {
    it('restores the demo data and generates advice', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $this->demo->transactions()->delete();
        $this->demo->goals()->delete();
        $this->demo->update(['name' => 'Испорчено', 'locale' => 'en']);

        $this->artisan('demo:reset')->assertSuccessful();

        $demo = $this->demo->fresh();

        expect($demo->name)->toBe('Алексей')
            ->and($demo->locale->value ?? $demo->locale)->toBe('ru')
            ->and($demo->subscription_plan)->toBe(SubscriptionPlan::Yearly)
            ->and($demo->transactions()->count())->toBeGreaterThan(1000)
            ->and($demo->goals()->count())->toBe(3)
            ->and($demo->aiAdvices()->where('generated_at', '>=', today())->count())->toBeGreaterThanOrEqual(4);
    });

    it('does nothing when there is no demo account', function () {
        $this->demo->forceDelete();

        $this->artisan('demo:reset')->assertSuccessful();

        expect(User::count())->toBe(0);
    });

    it('is scheduled every night', function () {
        $this->artisan('schedule:list')->expectsOutputToContain('demo:reset')->assertSuccessful();
    });
});
