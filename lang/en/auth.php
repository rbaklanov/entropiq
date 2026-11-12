<?php

return [
    'login_title' => 'Sign in or create an account',
    'login_subtitle' => 'Enter your phone number to sign in or register',
    'phone_label' => 'Phone number',
    'send_code' => 'Get code',
    'verify_title' => 'Enter the SMS code',
    'verify_subtitle' => 'We sent an SMS code to :phone',
    'verify_button' => 'Verify',
    'change_number' => 'Change number',
    'resend_code' => 'Resend code',
    'resend_timer' => 'Resend in :seconds sec',
    'logout' => 'Log out',
    'terms_agreement' => 'By tapping "Get code", you agree to the',
    'terms_link' => 'terms of service',
    'and' => 'and',
    'privacy_link' => 'privacy policy',
    'consent_label' => 'I give my',
    'consent_link' => 'consent to the processing of personal data',
    'consent_required' => 'To get a code, you need to consent to the processing of personal data.',

    'sms_code' => 'SMS code',
    'resend_timer_prefix' => 'Resend in',
    'resend_timer_suffix' => 'sec',

    'phone_not_verified' => 'Phone number is not verified.',
    'invalid_code' => 'Invalid code. Please try again.',
    'code_expired' => 'Code has expired. Request a new one.',
    'too_many_attempts' => 'Too many attempts. Try again in :seconds sec.',
    'code_sent' => 'Code sent to :phone.',
    'verification_sms' => 'Entropiq: your verification code is :code',
    'sms_send_failed' => 'Could not send the SMS. Please try again later.',

    // Telegram codes (SMS_DRIVER=sms_aero_telegram)
    'verify_title_telegram' => 'Enter the code from Telegram',
    'verify_subtitle_telegram' => 'We sent the code to Telegram, in the “Verification Codes” chat, for the account with number :phone',
    'sms_code_telegram' => 'Code from Telegram',
    'code_sent_telegram' => 'The code was sent to Telegram for :phone.',
    'sms_send_failed_telegram' => 'Could not send the code to Telegram. Please try again later.',
    'telegram_undelivered' => 'The code was not delivered: no Telegram account is registered to this number. Check the number or write to :email.',
    'login_hint_telegram' => 'The code will arrive in Telegram for the account with this number.',
];
