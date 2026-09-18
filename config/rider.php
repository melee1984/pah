<?php

return [
    'pahatud_commission_percentage' => (float) env('RIDER_PAHATUD_COMMISSION_PERCENTAGE', 20),
    'offer_nearby_limit' => (int) env('RIDER_OFFER_NEARBY_LIMIT', 10),
    'offer_max_pending_per_rider' => (int) env('RIDER_OFFER_MAX_PENDING_PER_RIDER', 3),
    'offer_max_distance_km' => (float) env('RIDER_OFFER_MAX_DISTANCE_KM', 20),
    'offer_location_max_age_minutes' => (int) env('RIDER_OFFER_LOCATION_MAX_AGE_MINUTES', 5),
];
