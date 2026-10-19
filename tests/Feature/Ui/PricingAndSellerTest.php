<?php

use App\Enums\SubscriptionPlan;
use App\Livewire\Settings\SubscriptionPage;
use App\Models\User;
use Livewire\Livewire;

describe('plan prices', function () {
    it('keeps prices in kopecks on the enum', function () {
        expect(SubscriptionPlan::Free->price())->toBe(0)
            ->and(SubscriptionPlan::Monthly->price())->toBe(9900)
            ->and(SubscriptionPlan::Yearly->price())->toBe(59000);
    });

    it('builds price placeholders in rubles', function () {
        expect(SubscriptionPlan::priceReplacements())->toBe([
            'monthly_price' => '99',
            'yearly_price' => '590',
            'yearly_savings' => '50',
        ]);
    });

    it('shows both prices on the landing, terms and FAQ pages', function (string $url) {
        foreach (['ru', 'en'] as $locale) {
            app()->setLocale($locale);

            $this->get($url)->assertOk()->assertSee('99 ₽')->assertSee('590 ₽')->assertDontSee(':monthly_price')->assertDontSee(':yearly_price');
        }
    })->with(['/', '/terms', '/faq']);

    it('shows plan prices and savings on the subscription page', function () {
        app()->setLocale('ru');

        Livewire::actingAs(User::factory()->create())
            ->test(SubscriptionPage::class)
            ->assertSee('99 ₽/мес')
            ->assertSee('590 ₽/год')
            ->assertSee('Экономия 50%');
    });

    it('leaves no price placeholders in translation files', function () {
        foreach (['ru', 'en'] as $locale) {
            foreach (['landing', 'subscription', 'legal', 'faq'] as $file) {
                $rendered = json_encode(trans($file, SubscriptionPlan::priceReplacements(), $locale), JSON_UNESCAPED_UNICODE);

                expect($rendered)->not->toContain(':monthly_price')
                    ->not->toContain(':yearly_price')
                    ->not->toContain(':yearly_savings');
            }
        }
    });
});

describe('landing reviews', function () {
    it('does not show made up user reviews', function () {
        foreach (['ru', 'en'] as $locale) {
            app()->setLocale($locale);

            $this->get('/')->assertOk()
                ->assertDontSee('Алексей К.')
                ->assertDontSee('Alexey K.')
                ->assertDontSee($locale === 'ru' ? 'Что говорят пользователи' : 'What users say');
        }
    });
});

describe('seller details in footer', function () {
    it('shows name, INN and NPD status when configured', function () {
        config()->set('app.seller', [
            'name' => 'Иванова Елизавета Ивановна',
            'inn' => '123456789012',
            'phone' => '+7 (900) 000-00-00',
            'email' => 'support@entropiq.ru',
        ]);
        app()->setLocale('ru');

        $this->get('/')->assertOk()
            ->assertSee('Иванова Елизавета Ивановна, ИНН 123456789012, плательщик НПД')
            ->assertSee('href="tel:+79000000000"', false)
            ->assertSee('mailto:support@entropiq.ru', false);
    });

    it('hides the seller block while details are missing', function () {
        config()->set('app.seller', [
            'name' => null,
            'inn' => null,
            'phone' => null,
            'email' => 'support@entropiq.ru',
        ]);
        app()->setLocale('ru');

        $this->get('/')->assertOk()
            ->assertDontSee('плательщик НПД')
            ->assertDontSee('tel:', false)
            ->assertSee('mailto:support@entropiq.ru', false);
    });
});
