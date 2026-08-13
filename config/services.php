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
        'client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('GOOGLE_CLIENT_IDS', env('GOOGLE_CLIENT_ID', '')))
        ))),
        'maps_key' => env('GOOGLE_MAPS_KEY'),
        'distance_matrix_batch_size' => env('GOOGLE_DISTANCE_MATRIX_BATCH_SIZE', 25),
        'distance_matrix_max_batches' => env('GOOGLE_DISTANCE_MATRIX_MAX_BATCHES', 1),
        'distance_matrix_daily_element_limit' => env('GOOGLE_DISTANCE_MATRIX_DAILY_ELEMENT_LIMIT', 1000),
        'distance_matrix_dashboard_enabled' => env('GOOGLE_DISTANCE_MATRIX_DASHBOARD_ENABLED', false),
    ],
    'delivery' => [
        'rate' => env('DELIVERY_STARTING_RATE', 45),
        'additional_km_rate' => env('ADDITIONAL_KM_RATE', 15),
        'preparation_min_minutes' => env('DELIVERY_PREPARATION_MIN_MINUTES', 30),
        'preparation_max_minutes' => env('DELIVERY_PREPARATION_MAX_MINUTES', 45),
        'fast_speed_kph' => env('DELIVERY_FAST_SPEED_KPH', 30),
        'slow_speed_kph' => env('DELIVERY_SLOW_SPEED_KPH', 20),
    ],
    'firebase' => [
        'server_key' => env('FIREBASE_SERVER_KEY'),
    ],

];
