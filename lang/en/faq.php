<?php

return [
    'sections' => [
        [
            'title' => 'Getting started',
            'items' => [
                [
                    'q' => 'How do I sign in, and do I need a password?',
                    'a' => 'No password is needed. Enter your phone number, receive a four digit code by SMS and type it in. If you do not have an account yet, it is created automatically.',
                ],
                [
                    'q' => 'The SMS code does not arrive. What should I do?',
                    'a' => 'Check that the number is correct and wait a minute: you can request a new code after 60 seconds. A code is valid for 5 minutes and you get 3 attempts to enter it. If the SMS still does not arrive, write to support@entropiq.ru.',
                ],
                [
                    'q' => 'Do I need to connect a bank?',
                    'a' => 'No. You enter transactions manually. The service has no access to your bank accounts and does not ask for bank card details.',
                ],
            ],
        ],
        [
            'title' => 'Inflation and balance',
            'items' => [
                [
                    'q' => 'What is the real balance?',
                    'a' => 'The nominal balance is the amount in rubles as it is. The real balance shows what that amount is worth after inflation: the amount is divided by the accumulated consumer price index for the period. So when prices grow, the real balance is lower than the nominal one.',
                ],
                [
                    'q' => 'Where does the inflation data come from?',
                    'a' => 'From the consumer price index published by Rosstat. We receive it from official sources: the EMISS portal (fedstat.ru) or directly from the Rosstat website. The data is updated once a month.',
                ],
                [
                    'q' => 'Why is the real balance estimated for the most recent months?',
                    'a' => 'Rosstat publishes the index with a delay, so official data for recent months is not available yet. For them we use the average monthly index of the last 12 published months and show a note on the screen. Once Rosstat publishes the data, the calculation switches to it automatically.',
                ],
                [
                    'q' => 'What is personal inflation?',
                    'a' => 'It is inflation calculated from the structure of your spending. The index of each category counts in proportion to your share of spending on it. If you spend a lot on groceries and they get more expensive faster than average, your inflation is higher than the overall rate.',
                ],
            ],
        ],
        [
            'title' => 'Plans',
            'items' => [
                [
                    'q' => 'What does the free plan include?',
                    'a' => 'Up to 50 transactions a month, one financial goal, basic analytics for the last month, personal inflation and one AI tip a week. Data export to CSV is available in the settings.',
                ],
                [
                    'q' => 'What does Premium offer and how much does it cost?',
                    'a' => 'Premium costs 99 ₽ a month or 590 ₽ a year. It includes unlimited transactions, up to 10 goals, daily AI tips, analytics for any period, scenario modelling and export to PDF and Excel.',
                ],
                [
                    'q' => 'How do I cancel the subscription, and can I get a refund?',
                    'a' => 'You can cancel the subscription in Settings, Subscription. Premium access remains until the end of the paid period. A 7-day money-back guarantee applies.',
                ],
            ],
        ],
        [
            'title' => 'Data and account',
            'items' => [
                [
                    'q' => 'How do I export my data?',
                    'a' => 'In Settings, press Export data (CSV). Export to PDF and Excel for a chosen period is available in Analytics on the Premium plan.',
                ],
                [
                    'q' => 'How do I receive the weekly digest by email?',
                    'a' => 'Add your email in Settings, Profile and confirm the address with the link from the email. After that the weekly digest switch becomes available in the settings. The digest arrives once a week, on Mondays.',
                ],
                [
                    'q' => 'How do I delete my account?',
                    'a' => 'In Settings, press Delete account. Before that you can export your data to CSV.',
                ],
                [
                    'q' => 'How is my data protected?',
                    'a' => 'Data is stored on protected servers and transferred over an encrypted channel (TLS/SSL), access to personal data is restricted. See the privacy policy for details.',
                ],
                [
                    'q' => 'Is AI advice a financial consultation?',
                    'a' => 'No. AI tips are informational and are not a financial consultation. The service is not responsible for the financial decisions of the user.',
                ],
            ],
        ],
    ],
];
