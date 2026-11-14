<x-mail::message>
# {{ __($exhausted ? 'sms_alert.exhausted_heading' : 'sms_alert.warning_heading') }}

{{ __($exhausted ? 'sms_alert.exhausted_intro' : 'sms_alert.warning_intro', ['sent' => $sent, 'limit' => $limit]) }}

{{ __('sms_alert.next_step') }}
</x-mail::message>
