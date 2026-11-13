<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mobileMenuOf(string $html): string
{
    preg_match('/<div\s+id="mobile-more-menu".*?<\/nav>/s', $html, $matches);

    return $matches[0] ?? '';
}

describe('mobile more menu', function () {
    it('lists analytics, advice, subscription and settings', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $menu = mobileMenuOf($this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent());

        expect($menu)
            ->toContain('href="'.route('analytics').'"')
            ->toContain('href="'.route('advice.index').'"')
            ->toContain('href="'.route('settings.subscription').'"')
            ->toContain('href="'.route('settings.index').'"');
    });

    it('opens the menu with a button instead of linking to settings', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('aria-controls="mobile-more-menu"', false)
            ->assertSee("\$dispatch('open-more-menu')", false);
    });

    it('marks the current section inside the menu', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $menu = mobileMenuOf($this->actingAs($user)->get(route('advice.index'))->assertOk()->getContent());

        expect($menu)->toMatch('/href="'.preg_quote(route('advice.index'), '/').'"\s+aria-current="page"/');
    });
});
