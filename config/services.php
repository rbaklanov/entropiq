<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'demo_phone' => env('SMS_DEMO_PHONE', '79990000000'),
        'demo_code' => env('SMS_DEMO_CODE', '1111'),
        'fixed_code' => filter_var(env('SMS_FIXED_CODE', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'admin' => [
        'phones' => env('ADMIN_PHONES', ''),
    ],

    'llm' => [
        'driver' => env('LLM_DRIVER', 'fake'),
    ],

    'gigachat' => [
        'client_id' => env('GIGACHAT_CLIENT_ID', ''),
        'client_secret' => env('GIGACHAT_CLIENT_SECRET', ''),
        'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),
        'model' => env('GIGACHAT_MODEL', 'GigaChat'),
        'verify_ssl' => filter_var(env('GIGACHAT_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN),
        'timeout' => (float) env('GIGACHAT_TIMEOUT', 30.0),
    ],

    'sms_aero' => [
        'email' => env('SMSAERO_EMAIL'),
        'api_key' => env('SMSAERO_API_KEY'),
        'sign' => env('SMSAERO_SIGN', 'Entropiq'),
        'test_mode' => filter_var(env('SMSAERO_TEST_MODE', true), FILTER_VALIDATE_BOOLEAN),
    ],

];
