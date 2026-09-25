<?php

return [
    'enabled' => (bool) env('REWARDS_ENABLED', true),

    // A customer earns one whole point for every configured amount spent.
    'php_per_point' => max(0.01, (float) env('REWARD_PHP_PER_POINT', 100)),

    'point_tiers' => [
        [
            'name' => 'Tier 1',
            'minimum_points' => 0,
            'maximum_points' => 100,
        ],
        [
            'name' => 'Tier 2',
            'minimum_points' => 101,
            'maximum_points' => 200,
        ],
        [
            'name' => 'Tier 3',
            'minimum_points' => 201,
            'maximum_points' => 500,
        ],
    ],
];
