<x-layouts.guest :title="__('legal.terms_title')">

    <section class="py-20 sm:py-24">
        <article class="prose prose-gray mx-auto max-w-3xl px-4 sm:px-6">
            <h1>{{ __('legal.terms_title') }}</h1>
            <p class="text-sm text-gray-400">{{ __('legal.terms_last_updated', ['date' => '25.04.2026']) }}</p>

            <h2>{{ __('legal.terms_intro_title') }}</h2>
            <p>{{ __('legal.terms_intro_text') }}</p>

            <h2>{{ __('legal.terms_service_title') }}</h2>
            <p>{{ __('legal.terms_service_text') }}</p>

            <h2>{{ __('legal.terms_account_title') }}</h2>
            <ul>
                <li>{{ __('legal.terms_account_auth') }}</li>
                <li>{{ __('legal.terms_account_responsibility') }}</li>
                <li>{{ __('legal.terms_account_accuracy') }}</li>
            </ul>

            <h2>{{ __('legal.terms_plans_title') }}</h2>
            <ul>
                <li>{{ __('legal.terms_plans_free') }}</li>
                <li>{{ __('legal.terms_plans_premium') }}</li>
                <li>{{ __('legal.terms_plans_refund') }}</li>
            </ul>

            <h2>{{ __('legal.terms_obligations_title') }}</h2>
            <ul>
                <li>{{ __('legal.terms_obligations_lawful') }}</li>
                <li>{{ __('legal.terms_obligations_no_abuse') }}</li>
                <li>{{ __('legal.terms_obligations_no_scraping') }}</li>
            </ul>

            <h2>{{ __('legal.terms_liability_title') }}</h2>
            <ul>
                <li>{{ __('legal.terms_liability_asis') }}</li>
                <li>{{ __('legal.terms_liability_ai') }}</li>
                <li>{{ __('legal.terms_liability_data') }}</li>
            </ul>

            <h2>{{ __('legal.terms_ip_title') }}</h2>
            <p>{{ __('legal.terms_ip_text') }}</p>

            <h2>{{ __('legal.terms_termination_title') }}</h2>
            <ul>
                <li>{{ __('legal.terms_termination_user') }}</li>
                <li>{{ __('legal.terms_termination_service') }}</li>
            </ul>

            <h2>{{ __('legal.terms_changes_title') }}</h2>
            <p>{{ __('legal.terms_changes_text') }}</p>

            <h2>{{ __('legal.terms_law_title') }}</h2>
            <p>{{ __('legal.terms_law_text') }}</p>

            <h2>{{ __('legal.terms_contact_title') }}</h2>
            <p>{{ __('legal.terms_contact_text') }}</p>
        </article>
    </section>

</x-layouts.guest>
