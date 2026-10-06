<?php

use App\Contracts\SubscriptionServiceInterface;
use App\Http\Controllers\AiAdviceController;
use App\Livewire\Advice\AdviceDetail;
use App\Livewire\Advice\AdviceList;
use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function adviceUser(bool $premium = false): User
{
    $factory = User::factory()->onboarded();

    return ($premium ? $factory->premium() : $factory)->create(['phone_verified_at' => now()]);
}

function makeAdvice(User $user, string $label, bool $openedThisWeek = false, int $minutesAgo = 0): AiAdvice
{
    return AiAdvice::factory()->for($user)->create([
        'title' => "Заголовок {$label}",
        'body' => "Секретный текст {$label}",
        'basis_data' => ['rule' => 'overspending', 'income' => 111111, 'secret_marker' => "marker-{$label}"],
        'is_read' => $openedThisWeek,
        'generated_at' => now()->subMinutes($minutesAgo + 10),
        'updated_at' => $openedThisWeek ? now() : now()->subMonth(),
    ]);
}

describe('open advice of the week', function () {
    it('has no restriction for Premium', function () {
        $user = adviceUser(true);
        makeAdvice($user, 'A', true);

        expect(app(SubscriptionServiceInterface::class)->openAdviceId($user))->toBeNull();
    });

    it('has no restriction for Free until an advice is opened this week', function () {
        $user = adviceUser();
        makeAdvice($user, 'A');

        expect(app(SubscriptionServiceInterface::class)->openAdviceId($user))->toBeNull();
    });

    it('limits Free to the advice opened first this week', function () {
        $user = adviceUser();
        makeAdvice($user, 'A');
        $opened = makeAdvice($user, 'B', true);

        expect(app(SubscriptionServiceInterface::class)->openAdviceId($user))->toBe($opened->id);
    });

    it('ignores advice opened in earlier weeks', function () {
        $user = adviceUser();
        $old = makeAdvice($user, 'A', true);
        $old->forceFill(['updated_at' => now()->subWeeks(2)])->saveQuietly();

        expect(app(SubscriptionServiceInterface::class)->openAdviceId($user))->toBeNull();
    });
});

describe('advice list page', function () {
    it('never puts the text of an advice into the page for Free', function () {
        $user = adviceUser();
        makeAdvice($user, 'A');
        makeAdvice($user, 'B', minutesAgo: 5);

        Livewire::actingAs($user)->test(AdviceList::class)
            ->assertSee('Заголовок A')
            ->assertSee('Заголовок B')
            ->assertDontSee('Секретный текст A')
            ->assertDontSee('Секретный текст B');
    });

    it('hides even the title of advice locked by the weekly limit', function () {
        $user = adviceUser();
        makeAdvice($user, 'LOCKED');
        makeAdvice($user, 'OPEN', true, 5);

        Livewire::actingAs($user)->test(AdviceList::class)
            ->assertSee('Заголовок OPEN')
            ->assertDontSee('Заголовок LOCKED')
            ->assertDontSee('Секретный текст LOCKED')
            ->assertDontSee('Секретный текст OPEN')
            ->assertSeeHtml('aria-hidden="true" inert');
    });

    it('shows the full advice excerpt to Premium', function () {
        $user = adviceUser(true);
        makeAdvice($user, 'A');
        makeAdvice($user, 'B', true, 5);

        Livewire::actingAs($user)->test(AdviceList::class)
            ->assertSee('Заголовок A')
            ->assertSee('Секретный текст A')
            ->assertSee('Заголовок B')
            ->assertSee('Секретный текст B');
    });
});

describe('advice detail page', function () {
    it('shows nothing of a locked advice to Free', function () {
        $user = adviceUser();
        $locked = makeAdvice($user, 'LOCKED');
        makeAdvice($user, 'OPEN', true, 5);

        Livewire::actingAs($user)->test(AdviceDetail::class, ['advice' => $locked])
            ->assertSet('locked', true)
            ->assertDontSee('Заголовок LOCKED')
            ->assertDontSee('Секретный текст LOCKED')
            ->assertDontSee('marker-LOCKED');
    });

    it('shows the opened advice in full to Free', function () {
        $user = adviceUser();
        $open = makeAdvice($user, 'OPEN');

        Livewire::actingAs($user)->test(AdviceDetail::class, ['advice' => $open])
            ->assertSet('locked', false)
            ->assertSee('Заголовок OPEN')
            ->assertSee('Секретный текст OPEN');
    });
});

describe('dashboard daily advice', function () {
    it('shows only the title to Free', function () {
        $user = adviceUser();
        makeAdvice($user, 'DAILY');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Заголовок DAILY')
            ->assertDontSee('Секретный текст DAILY');
    });

    it('hides a daily advice locked by the weekly limit', function () {
        $user = adviceUser();
        makeAdvice($user, 'DAILY');
        makeAdvice($user, 'OPEN', true, 5);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Заголовок DAILY')
            ->assertDontSee('Секретный текст DAILY');
    });

    it('shows the excerpt to Premium', function () {
        $user = adviceUser(true);
        makeAdvice($user, 'DAILY');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Секретный текст DAILY');
    });
});

describe('API advice list', function () {
    function adviceApiHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken, 'Accept' => 'application/json'];
    }

    it('returns titles only to Free before any advice is opened', function () {
        $user = adviceUser();
        makeAdvice($user, 'A');

        $this->withHeaders(adviceApiHeaders($user))->getJson('/api/v1/advice')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Заголовок A')
            ->assertJsonPath('data.0.body', null)
            ->assertJsonPath('data.0.basis_data', null)
            ->assertJsonPath('data.0.locked', false);
    });

    it('masks the title as well for advice locked by the weekly limit', function () {
        $user = adviceUser();
        $locked = makeAdvice($user, 'LOCKED');
        makeAdvice($user, 'OPEN', true, 5);

        $response = $this->withHeaders(adviceApiHeaders($user))->getJson('/api/v1/advice')->assertOk();

        $item = collect($response->json('data'))->firstWhere('id', $locked->id);

        expect($item['title'])->toBeNull()
            ->and($item['body'])->toBeNull()
            ->and($item['basis_data'])->toBeNull()
            ->and($item['locked'])->toBeTrue()
            ->and($response->getContent())->not->toContain('LOCKED')->not->toContain('marker-');
    });

    it('returns full advice to Premium', function () {
        $user = adviceUser(true);
        makeAdvice($user, 'A');

        $this->withHeaders(adviceApiHeaders($user))->getJson('/api/v1/advice')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Заголовок A')
            ->assertJsonPath('data.0.body', 'Секретный текст A')
            ->assertJsonPath('data.0.basis_data.secret_marker', 'marker-A')
            ->assertJsonPath('data.0.locked', false);
    });

    it('keeps pagination metadata', function () {
        $user = adviceUser();
        makeAdvice($user, 'A');

        $this->withHeaders(adviceApiHeaders($user))->getJson('/api/v1/advice')
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'total']]);
    });
});

describe('API advice details', function () {
    it('still returns the full opened advice to Free', function () {
        $user = adviceUser();
        $advice = makeAdvice($user, 'OPEN');

        $this->withHeaders(adviceApiHeaders($user))->getJson("/api/v1/advice/{$advice->id}")
            ->assertOk()
            ->assertJsonPath('data.body', 'Секретный текст OPEN')
            ->assertJsonPath('data.locked', false);
    });

    it('refuses a locked advice to Free', function () {
        $user = adviceUser();
        $locked = makeAdvice($user, 'LOCKED');
        makeAdvice($user, 'OPEN', true, 5);

        $this->withHeaders(adviceApiHeaders($user))->getJson("/api/v1/advice/{$locked->id}")
            ->assertForbidden()
            ->assertDontSee('Секретный текст LOCKED');
    });
});

describe('web advice controller', function () {
    it('no longer exposes an unguarded show action', function () {
        expect(method_exists(AiAdviceController::class, 'show'))->toBeFalse();
    });
});
