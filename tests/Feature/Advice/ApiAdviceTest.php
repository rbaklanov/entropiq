<?php

use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adviceAuthUser(): array
{
    $user = User::factory()->create(['phone_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function adviceHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('GET /api/v1/advice', function () {
    it('returns paginated list of advice for authenticated user', function () {
        [$user, $token] = adviceAuthUser();

        AiAdvice::factory()->count(3)->for($user)->create();

        $this->withHeaders(adviceHeaders($token))
            ->getJson('/api/v1/advice')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'body', 'basis_data', 'rating', 'is_read', 'generated_at']],
                'meta',
                'links',
            ]);
    });

    it('does not return other users advice', function () {
        [$user, $token] = adviceAuthUser();
        $otherUser = User::factory()->create();

        AiAdvice::factory()->count(2)->for($user)->create();
        AiAdvice::factory()->count(3)->for($otherUser)->create();

        $this->withHeaders(adviceHeaders($token))
            ->getJson('/api/v1/advice')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns advice ordered by generated_at desc', function () {
        [$user, $token] = adviceAuthUser();

        $older = AiAdvice::factory()->for($user)->create([
            'generated_at' => now()->subDays(2),
            'title' => 'Older',
        ]);
        $newer = AiAdvice::factory()->for($user)->create([
            'generated_at' => now(),
            'title' => 'Newer',
        ]);

        $response = $this->withHeaders(adviceHeaders($token))
            ->getJson('/api/v1/advice')
            ->assertOk();

        expect($response->json('data.0.title'))->toBe('Newer');
        expect($response->json('data.1.title'))->toBe('Older');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/advice')
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/advice/{advice}', function () {
    it('returns advice details and marks as read', function () {
        [$user, $token] = adviceAuthUser();

        $advice = AiAdvice::factory()->for($user)->create(['is_read' => false]);

        $this->withHeaders(adviceHeaders($token))
            ->getJson("/api/v1/advice/{$advice->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $advice->id, 'is_read' => true]);

        expect($advice->fresh()->is_read)->toBeTrue();
    });

    it('returns 403 for other users advice', function () {
        [$user, $token] = adviceAuthUser();
        $otherUser = User::factory()->create();

        $advice = AiAdvice::factory()->for($otherUser)->create();

        $this->withHeaders(adviceHeaders($token))
            ->getJson("/api/v1/advice/{$advice->id}")
            ->assertForbidden();
    });
});

describe('POST /api/v1/advice/{advice}/rate', function () {
    it('saves positive rating', function () {
        [$user, $token] = adviceAuthUser();

        $advice = AiAdvice::factory()->for($user)->create();

        $this->withHeaders(adviceHeaders($token))
            ->postJson("/api/v1/advice/{$advice->id}/rate", ['rating' => 1])
            ->assertOk()
            ->assertJsonFragment(['rating' => 1]);

        expect($advice->fresh()->rating)->toBe(1);
    });

    it('saves negative rating', function () {
        [$user, $token] = adviceAuthUser();

        $advice = AiAdvice::factory()->for($user)->create();

        $this->withHeaders(adviceHeaders($token))
            ->postJson("/api/v1/advice/{$advice->id}/rate", ['rating' => -1])
            ->assertOk()
            ->assertJsonFragment(['rating' => -1]);

        expect($advice->fresh()->rating)->toBe(-1);
    });

    it('rejects invalid rating value', function () {
        [$user, $token] = adviceAuthUser();

        $advice = AiAdvice::factory()->for($user)->create();

        $this->withHeaders(adviceHeaders($token))
            ->postJson("/api/v1/advice/{$advice->id}/rate", ['rating' => 5])
            ->assertUnprocessable();
    });

    it('rejects missing rating', function () {
        [$user, $token] = adviceAuthUser();

        $advice = AiAdvice::factory()->for($user)->create();

        $this->withHeaders(adviceHeaders($token))
            ->postJson("/api/v1/advice/{$advice->id}/rate", [])
            ->assertUnprocessable();
    });

    it('returns 403 for other users advice', function () {
        [$user, $token] = adviceAuthUser();
        $otherUser = User::factory()->create();

        $advice = AiAdvice::factory()->for($otherUser)->create();

        $this->withHeaders(adviceHeaders($token))
            ->postJson("/api/v1/advice/{$advice->id}/rate", ['rating' => 1])
            ->assertForbidden();
    });
});
