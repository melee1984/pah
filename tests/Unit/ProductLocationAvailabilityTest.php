<?php

namespace Tests\Unit;

use App\ProductLocationAvailability;
use App\Products;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class ProductLocationAvailabilityTest extends TestCase
{
    public function test_product_is_available_at_every_location_without_an_override(): void
    {
        $product = new Products(['active' => true]);
        $product->setRelation('locationAvailability', new Collection);

        $this->assertTrue($product->isAvailableAtLocation(10));
        $this->assertTrue($product->isAvailableAtLocation(20));
    }

    public function test_inactive_override_only_disables_the_matching_location(): void
    {
        $product = new Products(['active' => true]);
        $product->setRelation('locationAvailability', new Collection([
            new ProductLocationAvailability([
                'partner_location_id' => 10,
                'active' => false,
            ]),
        ]));

        $this->assertFalse($product->isAvailableAtLocation(10));
        $this->assertTrue($product->isAvailableAtLocation(20));
    }

    public function test_explicit_branch_status_overrides_the_old_global_status(): void
    {
        $product = new Products(['active' => false]);
        $product->setRelation('locationAvailability', new Collection([
            new ProductLocationAvailability([
                'partner_location_id' => 10,
                'active' => true,
            ]),
        ]));

        $this->assertTrue($product->isAvailableAtLocation(10));
    }

    public function test_global_status_is_the_default_when_no_branch_override_exists(): void
    {
        $product = new Products(['active' => false]);
        $product->setRelation('locationAvailability', new Collection);

        $this->assertFalse($product->isAvailableAtLocation(10));
    }
}
