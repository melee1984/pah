<?php

namespace Tests\Unit;

use App\PartnerLocation;
use App\Services\DeliveryCityService;
use App\Services\ReverseGeocodingService;
use Mockery;
use Tests\TestCase;

class DeliveryCityServiceTest extends TestCase
{
    public function test_it_accepts_equivalent_city_names(): void
    {
        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')->once()->andReturn('Davao');
        $service = new DeliveryCityService($reverseGeocoder);

        $check = $service->check(new PartnerLocation(['city' => 'Davao City']), 7.0731, 125.6128);

        $this->assertTrue($check['allowed']);
        $this->assertNull($check['reason']);
    }

    public function test_it_rejects_a_delivery_address_in_another_city(): void
    {
        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')->once()->andReturn('Cebu City');
        $service = new DeliveryCityService($reverseGeocoder);

        $check = $service->check(new PartnerLocation(['city' => 'Davao City']), 10.3157, 123.8854);

        $this->assertFalse($check['allowed']);
        $this->assertSame('city_mismatch', $check['reason']);
        $this->assertSame('Davao City', $check['partner_city']);
        $this->assertSame('Cebu City', $check['delivery_city']);
    }
}
