<?php

return [
    'commission_tiers' => [
        0 => 20,
        35 => 25,
        50 => 30,
    ],
    'pahatud_commission_percentage' => (float) env('PAHATUD_COMMISSION_PERCENTAGE', 20),
    'restaurant_invitation_expire_hours' => (int) env('RESTAURANT_INVITATION_EXPIRE_HOURS', 72),
    'default_commission_addup' => (bool) env('PAHATUD_COMMISSION_ADDUP', true),
];
