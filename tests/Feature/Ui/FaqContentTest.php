<?php

use App\Models\VerificationCode;
use App\Services\SubscriptionService;

function faqAnswers(string $locale): array
{
    $answers = [];

    foreach (trans('faq.sections', [], $locale) as $section) {
        foreach ($section['items'] as $item) {
            $answers[$item['q']] = $item['a'];
        }
    }

    return $answers;
}

function faqAnswerContaining(string $locale, string $needle): string
{
    $matches = array_filter(faqAnswers($locale), fn (string $answer) => str_contains($answer, $needle));

    return implode(' ', $matches);
}

describe('FAQ page', function () {
    it('renders every question and answer in both locales', function () {
        foreach (['ru', 'en'] as $locale) {
            app()->setLocale($locale);

            $html = $this->get('/faq')->assertOk()->getContent();

            foreach (faqAnswers($locale) as $question => $answer) {
                expect($html)->toContain(e($question))->toContain(e($answer));
            }
        }
    });

    it('uses accessible native disclosure widgets', function () {
        $html = $this->get('/faq')->assertOk()->getContent();

        expect(substr_count($html, '<details'))->toBe(count(faqAnswers('en')));
    });

    it('has the same structure in Russian and English', function () {
        $ru = trans('faq.sections', [], 'ru');
        $en = trans('faq.sections', [], 'en');

        expect($ru)->toHaveCount(count($en));

        foreach ($ru as $index => $section) {
            expect($section['items'])->toHaveCount(count($en[$index]['items']));
        }
    });

    it('has no empty questions or answers', function () {
        foreach (['ru', 'en'] as $locale) {
            foreach (faqAnswers($locale) as $question => $answer) {
                expect(trim($question))->not->toBeEmpty()
                    ->and(trim($answer))->not->toBeEmpty();
            }
        }
    });
});

describe('FAQ facts stay in sync with the product', function () {
    it('matches the SMS code limits', function () {
        foreach (['ru', 'en'] as $locale) {
            $answer = faqAnswerContaining($locale, 'support@entropiq.ru');

            expect($answer)
                ->toContain((string) VerificationCode::RESEND_COOLDOWN_SECONDS)
                ->toContain((string) VerificationCode::EXPIRATION_MINUTES)
                ->toContain((string) VerificationCode::MAX_ATTEMPTS);
        }
    });

    it('matches the free plan limits', function () {
        $constants = new ReflectionClass(SubscriptionService::class);
        $transactions = (string) $constants->getConstant('FREE_MONTHLY_TRANSACTION_LIMIT');

        foreach (['ru', 'en'] as $locale) {
            expect(faqAnswerContaining($locale, $transactions))->not->toBe('');
        }
    });

    it('matches the Premium prices', function () {
        foreach (['ru', 'en'] as $locale) {
            $answer = faqAnswerContaining($locale, '590');

            expect($answer)->toContain('99')->toContain('590');
        }

        expect(__('subscription.price_monthly', [], 'ru'))->toContain('99')
            ->and(__('subscription.price_yearly', [], 'ru'))->toContain('590');
    });
});
