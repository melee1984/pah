<?php

namespace Tests\Unit;

use App\Partners;
use App\Products;
use App\User;
use PHPUnit\Framework\TestCase;

class ProductPriceDisplayTest extends TestCase
{
    public function test_commission_is_not_added_twice_when_display_price_is_calculated_repeatedly(): void
    {
        $merchant = new Partners([
            'percentage' => 15,
            'addup' => 1,
        ]);

        $user = new User;
        $user->setRelation('merchant', $merchant);

        $product = new Products([
            'price' => 100,
            'price_comm' => 15,
        ]);
        $product->exists = true;
        $product->syncOriginal();
        $product->setRelation('user', $user);

        $this->assertSame('115.00', $product->getPriceDisplay());
        $this->assertSame('115.00', $product->getPriceDisplay());
        $this->assertSame('115.00', $product->price);
    }
}
