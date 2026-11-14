<?php

return [
    'warning_subject' => ':app: 80% of the daily code limit used',
    'warning_heading' => 'Sign-in codes are being used faster than usual',
    'warning_intro' => ':sent codes of the daily limit of :limit have been sent today.',
    'exhausted_subject' => ':app: daily code limit reached, sign-in is closed',
    'exhausted_heading' => 'Code delivery is stopped until the end of the day',
    'exhausted_intro' => ':sent codes have been sent today, the limit is :limit. New users cannot sign in until the next day starts or the limit is raised.',
    'next_step' => 'Check the request log in the SMS Aero account: many codes to unknown numbers mean brute force. The limit is set with SMS_DAILY_LIMIT, countries with SMS_ALLOWED_COUNTRIES (recreate the container after editing .env).',
];
