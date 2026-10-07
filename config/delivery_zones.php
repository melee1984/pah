<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Separated delivery service areas
    |--------------------------------------------------------------------------
    |
    | A merchant branch and delivery address may not cross the boundary of one
    | of these areas. Coordinates are classified automatically from the branch
    | and customer pins. Add more polygons here as Pahatud expands.
    |
    | Polygon points use [latitude, longitude]. Several polygons may share one
    | service-area key (for example, Samal and Talikud islands).
    |
    */
    'enabled' => env('DELIVERY_SERVICE_AREAS_ENABLED', true),

    'default_label' => 'Davao mainland / standard area',

    'areas' => [
        'samal_island' => [
            'label' => 'Samal Island',
            'polygons' => [
                [
                    [7.0220, 125.7000],
                    [7.0550, 125.6830],
                    [7.1050, 125.6750],
                    [7.1650, 125.6790],
                    [7.2250, 125.6690],
                    [7.2860, 125.6840],
                    [7.3330, 125.7200],
                    [7.3020, 125.7590],
                    [7.2320, 125.7810],
                    [7.1500, 125.7710],
                    [7.0800, 125.7500],
                ],
                [
                    [6.8840, 125.6770],
                    [6.9150, 125.6620],
                    [6.9720, 125.6670],
                    [7.0250, 125.6850],
                    [7.0610, 125.7100],
                    [7.0300, 125.7310],
                    [6.9750, 125.7270],
                    [6.9180, 125.7130],
                ],
            ],
        ],
    ],
];
