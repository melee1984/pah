<?php

namespace Tests\Unit;

use App\Model\Cart;
use PHPUnit\Framework\TestCase;

class CartFulfillmentTest extends TestCase
{
    public function test_delivery_is_the_default_and_only_type_that_requires_a_rider(): void
    {
        $this->assertTrue((new Cart)->requiresDelivery());

        $pickup = new Cart(['fulfillment_type' => Cart::FULFILLMENT_PICKUP]);
        $dineIn = new Cart(['fulfillment_type' => Cart::FULFILLMENT_DINE_IN]);

        $this->assertFalse($pickup->requiresDelivery());
        $this->assertFalse($dineIn->requiresDelivery());
    }
}
