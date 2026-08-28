<?php

return [
    'default_commission_percentage' => (float) env('AGENT_COMMISSION_PERCENTAGE', 30),
    'pahatud_commission_percentage' => (float) env('PAHATUD_COMMISSION_PERCENTAGE', 15),
    'restaurant_invitation_expire_hours' => (int) env('RESTAURANT_INVITATION_EXPIRE_HOURS', 72),
];
