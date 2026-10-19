<?php

return [
    'title' => 'Consent to personal data processing',
    'last_updated' => 'Version of :date',

    'intro' => 'By ticking "I give my consent to the processing of personal data" on the sign-in page, I freely, of my own will and in my own interest consent to the operator :seller (email :email) processing my personal data on the following terms.',

    'sections' => [
        [
            'title' => '1. What data is processed',
            'items' => [
                'Phone number.',
                'Name, if I provide it.',
                'Email address, if I provide it.',
                'Financial data I enter: income, expenses, categories, goals and savings.',
                'Payment details: amount, date, status and payment ID. Bank card data is processed by the payment service and is not passed to the operator.',
                'Technical data: IP address, device and browser type, cookies.',
            ],
        ],
        [
            'title' => '2. Purposes',
            'items' => [
                'Registration, signing in and identifying the user.',
                'Providing the features of the Service: finance tracking, analytics, personal inflation, AI tips.',
                'Sending emails: address confirmation, weekly digest, subscription reminders.',
                'Paying for the Premium plan, issuing receipts, refunds.',
                'Replying to user requests.',
            ],
        ],
        [
            'title' => '3. Actions with data',
            'items' => [
                'Collection, recording, systematisation, accumulation, storage, clarification (updating, changing), retrieval, use, transfer (provision, access), anonymisation, blocking, deletion and destruction, with or without automation.',
            ],
        ],
        [
            'title' => '4. Who receives data',
            'items' => [
                'SMS Aero: phone number and the text of the SMS with the sign-in code.',
                'Robokassa (payment service): details needed to process the payment and issue the receipt.',
                'GigaChat (Sberbank): anonymised spending figures, categories and goal names, without phone number or name.',
            ],
        ],
        [
            'title' => '5. Term and withdrawal',
            'items' => [
                'The consent is valid until the account is deleted or the consent is withdrawn.',
                'I can withdraw the consent by emailing :email or by deleting the account in settings. After withdrawal the operator stops processing and destroys the data within 30 days, except data that must be kept by law (for example, payment details for tax records).',
                'A record that the consent was given (date, document version, IP address) is kept for 3 years after withdrawal to prove that processing was lawful.',
                'Withdrawal does not affect the lawfulness of processing carried out before it.',
            ],
        ],
    ],
];
