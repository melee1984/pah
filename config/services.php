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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'maps_key' => env('GOOGLE_MAPS_KEY'),
    ],
    'delivery' => [
        'rate' => env('DELIVERY_STARTING_RATE', 45),
        'additional_km_rate' => env('ADDITIONAL_KM_RATE', 15),
        'preparation_min_minutes' => env('DELIVERY_PREPARATION_MIN_MINUTES', 30),
        'preparation_max_minutes' => env('DELIVERY_PREPARATION_MAX_MINUTES', 45),
        'fast_speed_kph' => env('DELIVERY_FAST_SPEED_KPH', 30),
        'slow_speed_kph' => env('DELIVERY_SLOW_SPEED_KPH', 20),
    ],

    'rider_call_relay' => [
        'number' => env('RIDER_CALL_RELAY_NUMBER'),
    ],

    'rider_cod' => [
        'method' => env('RIDER_COD_REMITTANCE_METHOD', 'contact_support'),
        'account_name' => env('RIDER_COD_ACCOUNT_NAME'),
        'account_number' => env('RIDER_COD_ACCOUNT_NUMBER'),
        'notes' => env(
            'RIDER_COD_REMITTANCE_NOTES',
            'Contact Pahatud support for the current COD remittance destination.',
        ),
    ],

    'semaphore' => [
        'url' => env('SEMAPHORE_API_URL', 'https://semaphore.co/api/v4/messages'),
        'key' => env('SEMAPHORE_API_KEY'),
        'sender' => env('SEMAPHORE_SENDER_NAME', 'PahatudFood'),
    ],

];
