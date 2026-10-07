<?php

namespace Tests\Unit;

use App\Support\DeliveryZone;
use Tests\TestCase;

class DeliveryZoneTest extends TestCase
{
    public function test_it_allows_locations_at_or_inside_the_maximum_distance(): void
    {
        config(['services.delivery.max_distance_km' => 10]);

        $deliveryZone = new DeliveryZone;

        $this->assertTrue($deliveryZone->isWithinRange(10));
        $this->assertTrue($deliveryZone->isWithinRange(9.99));
        $this->assertFalse($deliveryZone->isWithinRange(10.01));
    }

    public function test_it_selects_the_closest_location_and_calculates_distance(): void
    {
        $deliveryZone = new DeliveryZone;
        $near = (object) ['id' => 1, 'latitude' => 8.48, 'longtitude' => 124.64];
        $far = (object) ['id' => 2, 'latitude' => 8.58, 'longtitude' => 124.74];

        $result = $deliveryZone->closestLocation([$far, $near], 8.4801, 124.6401);

        $this->assertSame(1, $result['location']->id);
        $this->assertLessThanOrEqual(0.02, $result['distance_km']);
    }

    public function test_it_ignores_locations_without_valid_coordinates(): void
    {
        $deliveryZone = new DeliveryZone;
        $invalid = (object) ['id' => 1, 'latitude' => null, 'longtitude' => null];

        $this->assertNull($deliveryZone->closestLocation([$invalid], 8.48, 124.64));
    }

    public function test_it_rejects_invalid_user_coordinates(): void
    {
        $deliveryZone = new DeliveryZone;

        $this->assertFalse($deliveryZone->coordinatesAreValid(null, 124.64));
        $this->assertFalse($deliveryZone->coordinatesAreValid(91, 124.64));
        $this->assertTrue($deliveryZone->coordinatesAreValid(0, 0));
    }

    public function test_it_automatically_classifies_samal_coordinates(): void
    {
        $deliveryZone = new DeliveryZone;

        $this->assertSame('Samal Island', $deliveryZone->areaLabel(7.12, 125.72));
        $this->assertSame('Davao mainland / standard area', $deliveryZone->areaLabel(7.10, 125.61));
    }

    public function test_it_rejects_a_short_delivery_that_crosses_the_samal_boundary(): void
    {
        config(['services.delivery.max_distance_km' => 20]);
        $deliveryZone = new DeliveryZone;

        $result = $deliveryZone->check(7.12, 125.72, 7.10, 125.61);

        $this->assertFalse($result['allowed']);
        $this->assertSame('service_area', $result['reason']);
        $this->assertSame('samal_island', $result['merchant_area']['key']);
        $this->assertNull($result['customer_area']);
    }

    public function test_it_allows_delivery_within_the_same_samal_service_area(): void
    {
        config(['services.delivery.max_distance_km' => 20]);
        $deliveryZone = new DeliveryZone;

        $result = $deliveryZone->check(7.12, 125.72, 7.16, 125.73);

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
    }
}
