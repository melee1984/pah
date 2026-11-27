<?php

use App\Services\QuickPayGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This selects the gateway used for online payments. Add a driver below
    | when another provider is integrated, then switch it through the
    | PAYMENT_GATEWAY environment variable.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'quickpay'),
    'mobile_delivery_enabled' => env('MOBILE_DELIVERY_QUICKPAY_ENABLED', false),

    'gateways' => [
        'quickpay' => [
            'driver' => QuickPayGateway::class,
        ],
    ],

];
