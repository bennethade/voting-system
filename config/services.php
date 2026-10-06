<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'currency' => env('PAYSTACK_CURRENCY', 'NGN'),
        'timeout' => (int) env('PAYSTACK_TIMEOUT', 20),

        // Settlement routing applied to every transaction (votes, donations,
        // bookings). Use ONE of:
        //   split_code  - a Transaction Split (SPL_xxx) created on Paystack
        //   subaccount  - a single subaccount (ACCT_xxx), optionally with a
        //                 flat transaction_charge (major units, e.g. 500 = ₦500)
        //                 kept by the main account, and who bears Paystack fees.
        // If both are set, split_code takes precedence.
        'split_code' => env('PAYSTACK_SPLIT_CODE'),
        'subaccount' => env('PAYSTACK_SUBACCOUNT_CODE'),
        'transaction_charge' => env('PAYSTACK_TRANSACTION_CHARGE'),
        'bearer' => env('PAYSTACK_BEARER'), // account | subaccount (subaccount mode only)
    ],

];
