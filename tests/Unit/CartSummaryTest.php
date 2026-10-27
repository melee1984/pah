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

    public function test_delivery_keeps_the_item_level_commission(): void
    {
        $cart = new Cart(['fulfillment_type' => Cart::FULFILLMENT_DELIVERY]);
        $cart->setRelation('details', new Collection([
            new CartItem([
                'qty' => 2,
                'price' => 100,
                'variance_total' => 20,
                'price_comm_total' => 12,
                'variance_total_comm_total' => 3,
                'discount_amount' => 0,
            ]),
        ]));

        $this->assertSame(30.0, $cart->cartItemAmounts()['total_comm']);
    }

    public function test_pickup_and_dine_in_use_five_percent_of_the_item_subtotal(): void
    {
        config()->set('checkout.non_delivery_commission_percentage', 5);

        foreach ([Cart::FULFILLMENT_PICKUP, Cart::FULFILLMENT_DINE_IN] as $fulfillmentType) {
            $cart = new Cart(['fulfillment_type' => $fulfillmentType]);
            $cart->setRelation('details', new Collection([
                new CartItem([
                    'qty' => 2,
                    'price' => 100,
                    'variance_total' => 20,
                    'price_comm_total' => 12,
                    'variance_total_comm_total' => 3,
                    'discount_amount' => 0,
                ]),
            ]));

            $this->assertSame(12.0, $cart->cartItemAmounts()['total_comm']);
        }
    }

    public function test_pickup_and_dine_in_replace_an_added_delivery_commission_with_five_percent(): void
    {
        config()->set('checkout.non_delivery_commission_percentage', 5);

        foreach ([Cart::FULFILLMENT_PICKUP, Cart::FULFILLMENT_DINE_IN] as $fulfillmentType) {
            $cart = new Cart(['fulfillment_type' => $fulfillmentType]);
            $cart->setRelation('partner', new \App\Partners(['addup' => true]));
            $cart->setRelation('details', new Collection([
                new CartItem([
                    'qty' => 1,
                    'price' => 115,
                    'variance_total' => 0,
                    'price_comm_total' => 15,
                    'variance_total_comm_total' => 0,
                    'discount_amount' => 0,
                ]),
            ]));

            $amounts = $cart->cartItemAmounts();

            $this->assertSame(115.0, $amounts['sub_total']);
            $this->assertSame(10.0, $amounts['discount']);
            $this->assertSame(10.0, $amounts['fulfillment_discount']);
            $this->assertSame(5.0, $amounts['total_comm']);
        }
    }
}
