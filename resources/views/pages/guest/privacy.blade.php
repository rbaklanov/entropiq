<x-layouts.guest :title="__('legal.privacy_title')">

    <section class="py-20 sm:py-24">
        <article class="prose prose-gray mx-auto max-w-3xl px-4 sm:px-6">
            <h1>{{ __('legal.privacy_title') }}</h1>
            <p class="text-sm text-gray-400">{{ __('legal.privacy_last_updated', ['date' => '25.04.2026']) }}</p>

            <h2>{{ __('legal.privacy_intro_title') }}</h2>
            <p>{{ __('legal.privacy_intro_text') }}</p>

            <h2>{{ __('legal.privacy_data_title') }}</h2>
            <ul>
                <li>{{ __('legal.privacy_data_phone') }}</li>
                <li>{{ __('legal.privacy_data_name') }}</li>
                <li>{{ __('legal.privacy_data_financial') }}</li>
                <li>{{ __('legal.privacy_data_technical') }}</li>
            </ul>

            <h2>{{ __('legal.privacy_purpose_title') }}</h2>
            <ul>
                <li>{{ __('legal.privacy_purpose_auth') }}</li>
                <li>{{ __('legal.privacy_purpose_service') }}</li>
                <li>{{ __('legal.privacy_purpose_notification') }}</li>
                <li>{{ __('legal.privacy_purpose_improvement') }}</li>
            </ul>

            <h2>{{ __('legal.privacy_storage_title') }}</h2>
            <p>{{ __('legal.privacy_storage_text') }}</p>

            <h2>{{ __('legal.privacy_sharing_title') }}</h2>
            <p>{{ __('legal.privacy_sharing_text') }}</p>

            <h2>{{ __('legal.privacy_rights_title') }}</h2>
            <ul>
                <li>{{ __('legal.privacy_rights_access') }}</li>
                <li>{{ __('legal.privacy_rights_edit') }}</li>
                <li>{{ __('legal.privacy_rights_delete') }}</li>
                <li>{{ __('legal.privacy_rights_export') }}</li>
            </ul>

            <h2>{{ __('legal.privacy_cookies_title') }}</h2>
            <p>{{ __('legal.privacy_cookies_text') }}</p>

            <h2>{{ __('legal.privacy_changes_title') }}</h2>
            <p>{{ __('legal.privacy_changes_text') }}</p>

            <h2>{{ __('legal.privacy_contact_title') }}</h2>
            <p>{{ __('legal.privacy_contact_text') }}</p>
        </article>
    </section>

</x-layouts.guest>
