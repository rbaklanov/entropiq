<x-mail::message>
# {{ __('email_verification.heading') }}

{{ __('email_verification.intro') }}

<x-mail::button :url="$verificationUrl">
{{ __('email_verification.button') }}
</x-mail::button>

{{ __('email_verification.expires') }}

{{ __('email_verification.ignore') }}
</x-mail::message>
