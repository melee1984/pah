<?php

return [
    'enabled' => (bool) env('REWARDS_ENABLED', true),

    // A customer earns one whole point for every configured amount spent.
    'php_per_point' => max(0.01, (float) env('REWARD_PHP_PER_POINT', 100)),
];
