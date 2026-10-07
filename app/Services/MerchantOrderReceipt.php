<?php

namespace App\Services;

use App\Model\Cart;
use App\Model\Orders\Orders;
use Illuminate\Support\Str;

class MerchantOrderReceipt
{
    private const PAPER_WIDTH = 32;

    public function printJob(Orders $order): array
    {
        $order->loadMissing([
            'cart.partner',
            'cart.payment',
            'cart.details.item',
        ]);

        $cart = $order->cart;
        $amounts = $cart->cartItemAmounts();
        $orderNumber = $cart->order_no ?: $order->id;
        $lines = [];

        $lines[] = $this->center($cart->partner?->restaurant_name ?: 'PAHATUD MERCHANT');
        $lines[] = $this->center('ORDER #'.$orderNumber);
        $lines[] = $this->center($this->fulfillmentLabel($cart));
        $lines[] = str_repeat('-', self::PAPER_WIDTH);
        $lines[] = $this->columns('QTY ITEM', 'AMOUNT');
        $lines[] = str_repeat('-', self::PAPER_WIDTH);

        foreach ($cart->details as $detail) {
            $quantity = max(1, (int) $detail->qty);
            $name = $detail->item?->title ?: 'Item #'.$detail->item_id;
            $unitPrice = (float) $detail->price + (float) $detail->variance_total;
            $lines[] = $this->columns(
                $quantity.'x '.$name,
                $this->money($quantity * $unitPrice),
            );

            if (filled($detail->instruction)) {
                $lines[] = $this->wrapLine('  Note: '.$detail->instruction);
            }
        }

        $lines[] = str_repeat('-', self::PAPER_WIDTH);
        $lines[] = $this->columns('Subtotal', $this->money($amounts['sub_total']));

        if ($amounts['delivery_fee'] > 0) {
            $lines[] = $this->columns('Delivery', $this->money($amounts['delivery_fee']));
        }

        $lines[] = $this->columns('Convenience fee', $this->money($amounts['convenience_fee']));
        $lines[] = $this->columns('VAT', $this->money($amounts['vat_amount']));
        $lines[] = $this->columns('Discount', '-'.$this->money($amounts['discount']));

        $lines[] = $this->columns('TOTAL', $this->money($amounts['total']));
        $lines[] = str_repeat('-', self::PAPER_WIDTH);

        if (filled($cart->payment?->title)) {
            $lines[] = $this->wrapLine('Payment: '.$cart->payment->title);
        }

        $acceptedAt = $order->store_accepted_at ?: now();
        $lines[] = $this->wrapLine('Accepted: '.$acceptedAt->format('M d, Y h:i A'));
        $lines[] = '';
        $lines[] = $this->center('Thank you!');

        return [
            'id' => 'order-'.$order->id.'-accept',
            'format' => 'plain_text',
            'content' => implode("\n", $lines)."\n",
            'copies' => 1,
            'feed_lines' => 3,
        ];
    }

    private function fulfillmentLabel(Cart $cart): string
    {
        return match ($cart->fulfillment_type) {
            Cart::FULFILLMENT_PICKUP => 'PICKUP',
            Cart::FULFILLMENT_DINE_IN => 'DINE IN',
            default => 'DELIVERY',
        };
    }

    private function money(float|int|string $amount): string
    {
        return 'PHP '.number_format((float) $amount, 2, '.', ',');
    }

    private function center(string $text): string
    {
        $text = Str::limit(trim($text), self::PAPER_WIDTH, '');
        $padding = max(0, intdiv(self::PAPER_WIDTH - mb_strlen($text), 2));

        return str_repeat(' ', $padding).$text;
    }

    private function columns(string $left, string $right): string
    {
        $right = trim($right);
        $leftWidth = max(1, self::PAPER_WIDTH - mb_strlen($right) - 1);
        $left = Str::limit(trim($left), $leftWidth, '');
        $spaces = max(1, self::PAPER_WIDTH - mb_strlen($left) - mb_strlen($right));

        return $left.str_repeat(' ', $spaces).$right;
    }

    private function wrapLine(string $text): string
    {
        return collect(explode("\n", wordwrap(trim($text), self::PAPER_WIDTH, "\n", true)))
            ->map(fn (string $line) => rtrim($line))
            ->implode("\n");
    }
}
