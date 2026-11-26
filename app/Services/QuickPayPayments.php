<?php

namespace App\Services;

use App\DiningBill;
use App\Model\Cart;
use App\PaymentMethod;
use App\QrOrderingCode;
use App\QuickPayCheckout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuickPayPayments
{
    public function __construct(
        private readonly QuickPayGateway $gateway,
        private readonly DiningRoom $room,
        private readonly QrOrderSubmission $qrOrders,
    ) {}

    public function startPos(int $billId, int $partnerId, int $branchId, int $actorId, array $input): QuickPayCheckout
    {
        return DB::transaction(function () use ($billId, $partnerId, $branchId, $actorId, $input) {
            $bill = $this->room->lockBill($billId, $partnerId, $branchId);
            $checkout = QuickPayCheckout::latestFor($bill->id, lock: true);
            if ($checkout && in_array($checkout->status, [...QuickPayCheckout::ACTIVE, 'paid'], true)) {
                return $checkout;
            }

            $method = PaymentMethod::active()->forMerchantQr($partnerId)->findOrFail($input['payment_method_id']);
            $orders = $this->room->lockPayableOrders($bill);
            abort_if($orders->contains(fn ($order) => $order->cart->requiresDelivery()), 422, 'QuickPay POS only supports dine-in and takeaway bills.');
            $total = $this->room->totals($bill)['total'];
            abort_unless($total === (int) $input['expected_total'], 409, 'The bill total changed. Review it before starting payment.');

            return $this->reserve($method, $partnerId, $total, $input, [
                'dining_bill_id' => $bill->id,
                'actor_id' => $actorId,
            ], 'Pahatud bill #'.$bill->id);
        }, 3);
    }

    public function startGuest(Cart $cart, QrOrderingCode $code, PaymentMethod $method, array $input): QuickPayCheckout
    {
        $cart->delivery_fee = 0;
        $amounts = $this->room->amounts($cart);
        abort_unless($amounts['total'] === (int) ($input['expected_total'] ?? -1), 409, 'The cart total changed. Review it before starting payment.');
        $returnUrl = $code->orderingUrl().'&quickpay=return';
        $return = parse_url($returnUrl, PHP_URL_SCHEME) === 'https'
            ? ['success_url' => $returnUrl, 'cancel_url' => $returnUrl] : [];

        return $this->reserve($method, $cart->partner_id, $amounts['total'], $input, [
            'cart_id' => $cart->id,
            'guest_dining_table_id' => $cart->dining_table_id,
            'guest_snapshot' => [
                'customer' => array_intersect_key($input, array_flip(['full_name', 'mobile', 'email', 'payment_method_id'])),
                'amounts' => $amounts,
                'items' => $this->room->items($cart),
            ],
        ], 'Pahatud QR order', $return);
    }

    private function reserve(PaymentMethod $method, int $partnerId, int $total, array $input, array $subject, string $description, array $options = []): QuickPayCheckout
    {
        $mode = $method->quickpay_channel;
        abort_unless($mode && isset(PaymentMethod::QUICKPAY_CHANNELS[$mode]), 422, 'This payment method is unavailable through QuickPay.');
        $roundingDiscount = $total % 100;
        $amount = $total - $roundingDiscount;
        abort_if($amount <= 0, 422, 'QuickPay requires a positive whole-peso charge. Choose another payment method.');
        abort_if($roundingDiscount > 0 && empty($input['confirm_rounding_discount']), 422, 'Confirm the rounding discount before creating a QuickPay payment.');
        $key = (string) Str::uuid();

        return QuickPayCheckout::create($subject + [
            'partner_id' => $partnerId,
            'payment_method_id' => $method->id,
            'amount_cents' => $amount,
            'rounding_discount_cents' => $roundingDiscount,
            'request_key' => $key,
            'livemode' => $this->gateway->mode($partnerId),
            'status' => 'pending',
            'request_body' => $options + ($mode === 'hosted' ? [] : ['channel' => $mode]) + [
                'reference' => 'pahatud-'.$key,
                'amount' => $amount,
                'description' => $description,
            ],
        ]);
    }

    public function stopMonitoring(QuickPayCheckout $checkout): QuickPayCheckout
    {
        return DB::transaction(function () use ($checkout) {
            $current = QuickPayCheckout::lockForUpdate()->findOrFail($checkout->id);
            if ($current->status === 'pending' && $current->monitoring_stopped_at === null) {
                $current->update(['monitoring_stopped_at' => now()]);
            }

            return $current;
        }, 3);
    }

    public function reconcile(QuickPayCheckout $checkout, bool $manual = false): QuickPayCheckout
    {
        $checkout = DB::transaction(function () use ($checkout, $manual) {
            $current = QuickPayCheckout::lockForUpdate()->findOrFail($checkout->id);
            if ($current->status === 'pending' && ($manual || $current->monitoring_stopped_at === null)) {
                $this->gateway->refresh($current);
            }

            return $current;
        });

        if ($checkout->status !== 'confirmed') {
            return $checkout;
        }

        return DB::transaction(function () use ($checkout) {
            if ($checkout->cart_id) {
                $cart = Cart::lockForUpdate()->findOrFail($checkout->cart_id);
                $branchId = (int) $cart->partner_location_address_id;
            } else {
                $bill = DiningBill::findOrFail($checkout->dining_bill_id);
                $branchId = (int) $bill->partner_location_id;
                $this->room->lockBill($bill->id, $checkout->partner_id, $branchId);
            }

            $checkout = QuickPayCheckout::lockForUpdate()->findOrFail($checkout->id);
            if ($checkout->status !== 'confirmed') {
                return $checkout;
            }

            if ($checkout->cart_id) {
                $order = $this->qrOrders->place($cart, $checkout->guest_snapshot['customer'], $checkout);
                $checkout->dining_bill_id = $order->billRound()->value('dining_bill_id');
            }

            $this->room->pay($checkout->dining_bill_id, $checkout->partner_id, $branchId, $checkout->actor_id, [
                'payment_method_id' => $checkout->payment_method_id,
                'idempotency_key' => $checkout->request_key,
                'expected_total' => $checkout->amount_cents,
                'reference' => $checkout->provider_id,
            ], $checkout);
            $checkout->update(['status' => 'paid', 'qr_payload' => null, 'qr_image' => null, 'guest_snapshot' => null]);

            return $checkout;
        }, 3);
    }
}
