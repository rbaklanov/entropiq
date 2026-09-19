<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('pulse.enabled', true);
    config()->set('services.sms.demo_phone', '79990000000');
    config()->set('services.admin.phones', ['79610893196']);
});

describe('Pulse and Horizon dashboard access', function () {
    it('denies guests', function (string $path) {
        $this->get($path)->assertForbidden();
    })->with(['/pulse', '/horizon']);

    it('denies a regular user', function (string $path) {
        $user = User::factory()->create(['phone' => '79001234567']);

        $this->actingAs($user)->get($path)->assertForbidden();
    })->with(['/pulse', '/horizon']);

    it('denies the SMS demo user even if listed as admin', function (string $path) {
        config()->set('services.admin.phones', ['79990000000']);

        $user = User::factory()->create(['phone' => '79990000000']);

        $this->actingAs($user)->get($path)->assertForbidden();
    })->with(['/pulse', '/horizon']);

    it('allows an admin user', function (string $path) {
        $user = User::factory()->create(['phone' => '79610893196']);

        $this->actingAs($user)->get($path)->assertOk();
    })->with(['/pulse', '/horizon']);
});
