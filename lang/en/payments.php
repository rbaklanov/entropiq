<?php

return [
    'description' => 'Entropiq Premium subscription, :period',
    'period_monthly' => '1 month',
    'period_yearly' => '1 year',

    'processing' => 'Payment is being processed. Premium turns on as soon as the payment service confirms it.',
    'failed' => 'The payment did not go through. No money was taken, please try again.',

    'auto_renew_label' => 'Renew the subscription automatically. I agree to the price of the next period being charged to my card under the',
    'auto_renew_offer_link' => 'public offer',
    'offer_acceptance' => 'By tapping "Subscribe", you accept the',
    'offer_link' => 'public offer',

    'auto_renew_on' => 'Auto renewal is on. On :date, :amount ₽ will be charged to your card.',
    'auto_renew_off' => 'Auto renewal is off. Premium is active until :date, then the account moves to the free plan.',
    'auto_renew_soon' => 'Your subscription renews automatically in a few days: :amount ₽ will be charged on :date. You can turn auto renewal off below.',
    'disable_auto_renew' => 'Turn off auto renewal',
    'disable_auto_renew_confirm' => 'Turn off auto renewal? There will be no new charges, Premium stays until the end of the paid period.',
    'auto_renew_disabled' => 'Auto renewal is off. Premium stays until the end of the paid period.',

    'reminder_subject' => ':app: your subscription renews soon',
    'reminder_heading' => 'Your Premium subscription renews soon',
    'reminder_text' => 'On :date, :amount ₽ will be charged to your card for the next period of Entropiq Premium.',
    'reminder_cancel' => 'If you do not want to renew, turn off auto renewal in Settings, Subscription. Premium stays until the end of the paid period.',
    'reminder_button' => 'Manage subscription',
];
