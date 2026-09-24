<?php

namespace Tests\Unit;

use App\Model\Cart;
use App\Model\CartItem;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CartSummaryTest extends TestCase
{
    public function test_summary_includes_convenience_fee_and_vat(): void
    {
        config()->set('checkout.convenience_fee_rate', 0.05);
        config()->set('checkout.vat_rate', 12);

        $cart = new Cart([
            'delivery_fee' => 50,
            'discount_amount' => 10,
        ]);
        $cart->setRelation('details', new Collection([
            new CartItem([
                'qty' => 2,
                'price' => 100,
                'variance_total' => 10,
                'discount_amount' => 5,
                'price_comm_total' => 0,
                'variance_total_comm_total' => 0,
            ]),
        ]));

        $summary = $cart->cartItemSummary();

        $this->assertSame('11.00', $summary['convenience_fee']);
        $this->assertSame('1.32', $summary['vat_amount']);
        $this->assertSame('262.32', $summary['total']);
        $this->assertSame(262.32, $cart->cartItemTotal());
    }

    public function test_empty_cart_does_not_apply_checkout_charges(): void
    {
        config()->set('checkout.convenience_fee_rate', 0.05);
        config()->set('checkout.vat_rate', 12);

        $cart = new Cart;
        $cart->setRelation('details', new Collection);

        $summary = $cart->cartItemSummary();

        $this->assertSame('0.00', $summary['convenience_fee']);
        $this->assertSame('0.00', $summary['vat_amount']);
        $this->assertSame('0.00', $summary['total']);
    }
}
