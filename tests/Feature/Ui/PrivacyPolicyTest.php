<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiAdviceRuleEngine;
use App\Services\FakeLlmService;
use App\Services\GigaChatService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function privacyKeys(string $locale): array
{
    return collect(array_keys(trans('legal', [], $locale)))
        ->filter(fn (string $key) => str_starts_with($key, 'privacy_'))
        ->sort()
        ->values()
        ->all();
}

describe('privacy policy page', function () {
    it('mentions the email address in data, purposes, storage and rights', function (string $locale) {
        app()->setLocale($locale);

        $this->get('/privacy')->assertOk()
            ->assertSee(__('legal.privacy_data_email'))
            ->assertSee(__('legal.privacy_purpose_notification'))
            ->assertSee(__('legal.privacy_storage_email'))
            ->assertSee(__('legal.privacy_rights_edit'));
    })->with(['ru', 'en']);

    it('names the contractors that receive data and the own mail infrastructure', function (string $locale) {
        app()->setLocale($locale);

        $this->get('/privacy')->assertOk()
            ->assertSee('SMS Aero')
            ->assertSee('GigaChat')
            ->assertSee(__('legal.privacy_sharing_email'))
            ->assertSee(__('legal.privacy_sharing_law'));
    })->with(['ru', 'en']);

    it('no longer claims that data is never passed to third parties', function () {
        app()->setLocale('ru');

        $this->get('/privacy')->assertOk()
            ->assertDontSee('не передаём ваши персональные данные третьим лицам');
    });

    it('shows the new last updated date', function () {
        $this->get('/privacy')->assertOk()->assertSee('06.10.2026');
    });

    it('has the same policy keys in Russian and English', function () {
        expect(privacyKeys('ru'))->toBe(privacyKeys('en'))
            ->and(privacyKeys('ru'))->toContain('privacy_data_email', 'privacy_storage_email', 'privacy_sharing_sms', 'privacy_sharing_ai', 'privacy_sharing_email');
    });
});

describe('what the policy promises matches the code', function () {
    it('erases phone, name and email on deletion and keeps only anonymized financial records', function () {
        $this->seed(CategorySeeder::class);
        $user = User::factory()->withVerifiedEmail('person@example.com')->create(['phone' => '79991234567', 'name' => 'Анна', 'phone_verified_at' => now()]);
        $category = Category::factory()->expense()->create();
        Transaction::factory()->for($user)->expense()->create(['category_id' => $category->id, 'amount' => 10000]);

        $token = $user->createToken('t')->plainTextToken;

        $this->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->deleteJson('/api/v1/user')
            ->assertOk();

        $deleted = User::withTrashed()->find($user->id);

        expect($deleted->phone)->not->toBe('79991234567')
            ->and($deleted->name)->toBeNull()
            ->and($deleted->email)->toBeNull()
            ->and($deleted->tokens()->count())->toBe(0)
            ->and(Transaction::where('user_id', $user->id)->count())->toBe(1);
    });

    it('sends no phone number or name to GigaChat in the advice request', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);
        $user = User::where('phone', '+79990000001')->first();

        $payloads = app(AiAdviceRuleEngine::class)->evaluate($user);
        $method = new ReflectionMethod(GigaChatService::class, 'buildMessages');
        $service = new GigaChatService(new FakeLlmService, 'id', 'secret');

        expect($payloads)->not->toBeEmpty();

        foreach ($payloads as $payload) {
            $request = json_encode($method->invoke($service, $payload), JSON_UNESCAPED_UNICODE);

            expect($request)->not->toContain('79990000001')->not->toContain($user->name);
        }
    });

});
