<?php

namespace App\Services;

use App\Events\SendPushNotificationEvent;
use App\LibraryStatus;
use App\Model\Cart;
use App\Model\Orders\OrderProcess;
use App\Model\Orders\Orders;
use App\QuickPayCheckout;
use Illuminate\Support\Facades\DB;

class QrOrderSubmission
{
    public function place(Cart $cart, array $customer, ?QuickPayCheckout $checkout = null): Orders
    {
        $cart->fullname = $customer['full_name'];
        $cart->mobile = $customer['mobile'];
        $cart->email = $customer['email'] ?? null;
        $cart->payment_id = $customer['payment_method_id'];
        $cart->processed_at = now();
        $cart->submitted_at = now();
        $cart->delivery_date = now()->format('M d, Y');
        $cart->delivery_time = 'ASAP';
        $cart->delivery_fee = 0;
        $cart->distance_rate = 0;
        $cart->order_no = $cart->generateOrderNo();
        $cart->active = true;
        $cart->save();

        $order = new Orders([
            'user_id' => null,
            'cart_id' => $cart->id,
            'submitted_at' => $cart->submitted_at,
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
            'partner_id' => $cart->partner_id,
        ]);
        $order->setRelation('cart', $cart);
        if ($checkout) {
            $order->setRelation('prepaidCheckout', $checkout);
        }
        $order->save();
        $order->unsetRelation('prepaidCheckout');

        OrderProcess::firstOrCreate([
            'status_id' => LibraryStatus::STATUS_ORDER_PLACED,
            'order_id' => $order->id,
        ]);

        DB::afterCommit(fn () => event(new SendPushNotificationEvent($order)));

        return $order;
    }
}
