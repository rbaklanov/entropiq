<?php

return [
    'title' => 'Public offer',
    'last_updated' => 'Version of :date',

    'sections' => [
        [
            'title' => '1. General provisions',
            'items' => [
                'This document is a public offer (Article 437 of the Civil Code of the Russian Federation) to enter into an agreement for access to the paid Premium plan of the Entropiq service (the "Service") available at :site.',
                'Provider: :seller. The Provider applies the special tax regime "Professional Income Tax".',
                'Customer: a legally capable individual who is registered in the Service and has paid for the Premium plan.',
                'The offer is accepted by paying for the Premium plan (Article 438(3) of the Civil Code of the Russian Federation). From the moment of payment the agreement is concluded on the terms of this offer.',
                'This offer supplements the Terms of Service. Personal data is processed under the Privacy Policy and the consent to personal data processing.',
            ],
        ],
        [
            'title' => '2. Subject of the agreement',
            'items' => [
                'The Provider gives the Customer access to the extended features of the Service (Premium plan) for the paid period: unlimited transactions, up to 10 financial goals, daily AI tips, analytics for any period, scenario modelling, export to PDF and Excel.',
                'The service is provided remotely over the Internet in the Customer\'s account. Access opens right after the payment is confirmed.',
                'AI tips are for information only and are not an individual investment recommendation.',
            ],
        ],
        [
            'title' => '3. Price and payment',
            'items' => [
                'The Premium plan costs :monthly_price ₽ for one month or :yearly_price ₽ for one year. Not subject to VAT.',
                'Payment is made through the Robokassa payment service by bank card or another method available on the payment page. Card details are entered on the payment service side; the Provider neither receives nor stores them.',
                'After payment the Provider issues a receipt in the "Moy nalog" app. The receipt is sent to the email address entered at payment.',
                'The Provider may change the price. A new price does not affect a period already paid for and applies to renewal only after notifying the Customer at least 7 days in advance.',
            ],
        ],
        [
            'title' => '4. Automatic renewal',
            'items' => [
                'When paying, the Customer may separately agree to automatic renewal by ticking the corresponding box on the payment page. Without this consent the subscription is not renewed automatically.',
                'With automatic renewal on, the subscription is renewed for the same period when the paid period ends. The renewal price under the current plan is charged to the bank card used for payment on the day the period ends.',
                'At least 3 days before the charge the Provider sends a reminder to the Customer\'s confirmed email address, if one is set in the profile, and shows a notice in the "Subscription" section.',
                'If the charge fails, the subscription is not renewed: the account moves to the free plan and the Customer\'s data is kept.',
            ],
        ],
        [
            'title' => '5. Cancelling automatic renewal',
            'items' => [
                'The Customer may turn off automatic renewal at any time without giving reasons: in "Settings", "Subscription", or by email to :email.',
                'After it is turned off no new charges are made. Premium access remains until the end of the paid period, then the account moves to the free plan.',
            ],
        ],
        [
            'title' => '6. Refunds',
            'items' => [
                'Within 7 calendar days from any payment, including an automatic renewal, the Customer may cancel the service and receive a full refund of that payment.',
                'After 7 days the Customer may cancel the service at any time (Article 32 of the Russian Consumer Rights Protection Law). In this case the price of the unused full days of the paid period is refunded: the payment amount multiplied by the number of unused days and divided by the number of days in the period.',
                'To request a refund, the Customer emails :email with the account phone number, the payment date and amount.',
                'The refund is made to the bank card used for payment within 10 days of receiving the request. The time it takes to arrive depends on the Customer\'s bank.',
                'After a refund Premium access ends and the receipt is cancelled.',
            ],
        ],
        [
            'title' => '7. Rights and obligations',
            'items' => [
                'The Provider undertakes to provide access to Premium features during the paid period, accept the Customer\'s requests and reply within 10 days.',
                'The Provider may carry out maintenance during which the Service may be temporarily unavailable.',
                'The Customer undertakes to follow the Terms of Service and not to share access to the account with third parties.',
            ],
        ],
        [
            'title' => '8. Liability',
            'items' => [
                'The parties are liable in accordance with the laws of the Russian Federation.',
                'The Provider is not responsible for financial decisions the Customer makes based on the data and tips of the Service.',
            ],
        ],
        [
            'title' => '9. Term and changes',
            'items' => [
                'The offer is valid until withdrawn by the Provider. The current version is published on this page.',
                'Changes take effect when published. For a period already paid for, the terms accepted by the Customer at payment apply.',
                'Disputes are settled by negotiation and, failing agreement, in court under the laws of the Russian Federation, including consumer protection law.',
            ],
        ],
        [
            'title' => '10. Provider details',
            'items' => [
                ':seller',
                'Email: :email',
            ],
        ],
    ],
];
