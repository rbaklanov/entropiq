<x-mail::message>
# {{ __('payments.reminder_heading') }}

{{ __('payments.reminder_text', ['amount' => $amount, 'date' => $date]) }}

{{ __('payments.reminder_cancel') }}

<x-mail::button :url="$subscriptionUrl">
{{ __('payments.reminder_button') }}
</x-mail::button>
</x-mail::message>
