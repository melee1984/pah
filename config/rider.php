<?php

return [
    'pahatud_commission_percentage' => (float) env('RIDER_PAHATUD_COMMISSION_PERCENTAGE', 20),
    'offer_nearby_limit' => (int) env('RIDER_OFFER_NEARBY_LIMIT', 10),
    'offer_max_pending_per_rider' => (int) env('RIDER_OFFER_MAX_PENDING_PER_RIDER', 3),
    'offer_max_distance_km' => (float) env('RIDER_OFFER_MAX_DISTANCE_KM', 20),
    'offer_location_max_age_minutes' => (int) env('RIDER_OFFER_LOCATION_MAX_AGE_MINUTES', 5),
    'batching_enabled' => (bool) env('RIDER_BATCHING_ENABLED', true),
    'batch_max_active_deliveries' => (int) env('RIDER_BATCH_MAX_ACTIVE_DELIVERIES', 2),
    'batch_max_pickup_detour_km' => (float) env('RIDER_BATCH_MAX_PICKUP_DETOUR_KM', 2),
    'batch_max_added_distance_km' => (float) env('RIDER_BATCH_MAX_ADDED_DISTANCE_KM', 8),
    'batch_max_existing_delay_minutes' => (int) env('RIDER_BATCH_MAX_EXISTING_DELAY_MINUTES', 10),
    'batch_average_speed_kph' => (float) env('RIDER_BATCH_AVERAGE_SPEED_KPH', 25),
];
