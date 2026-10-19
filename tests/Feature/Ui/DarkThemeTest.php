<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('dark theme', function () {
    it('offers system, light and dark appearance in settings', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee(__('settings.appearance'))
            ->assertSee("\$flux.appearance = 'system'", false)
            ->assertSee("\$flux.appearance = 'light'", false)
            ->assertSee("\$flux.appearance = 'dark'", false);
    });

    it('applies the stored appearance on every layout', function (string $url, bool $onboarded) {
        $factory = $onboarded ? User::factory()->onboarded() : User::factory();
        $user = $factory->create(['phone_verified_at' => now()]);

        $this->actingAs($user)
            ->get($url)
            ->assertOk()
            ->assertSee('flux.appearance', false);
    })->with([
        'app layout' => ['/dashboard', true],
        'onboarding layout' => ['/onboarding/1', false],
    ]);

    it('applies the stored appearance on guest pages', function () {
        $this->get('/')->assertOk()->assertSee('flux.appearance', false);
    });
});
