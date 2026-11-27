<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DiningBill;
use App\LibraryStatus;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\Model\Rider\RiderApiDelivery;
use App\PaymentMethod;
use App\QrOrderingCode;
use App\QuickPayCheckout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuickPayPayments
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly DiningRoom $room,
        private readonly QrOrderSubmission $qrOrders,
    ) {}

    public function deliveryMethods(Orders $order, RiderApiDelivery $delivery): array
    {
        if (! config('payment.mobile_delivery_enabled')
            || ! $this->canStartDeliveryPayment($order, $delivery)
            || ! $this->gateway->availableFor((int) $order->partner_id)) {
            return [];
        }

        return PaymentMethod::active()->forQr()->whereIn('quickpay_channel', array_keys(PaymentMethod::QUICKPAY_CHANNELS))
            ->orderBy('id')->get(['id', 'title', 'quickpay_channel'])
            ->map(fn (PaymentMethod $method) => [
                'id' => $method->id,
                'title' => $method->title,
                'channel' => $method->quickpay_channel,
            ])->all();
    }

    public function startDelivery(Orders $order, RiderApiDelivery $delivery, array $input): QuickPayCheckout
    {
        return DB::transaction(function () use ($order, $delivery, $input) {
            $order = Orders::lockForUpdate()->findOrFail($order->id);
            $delivery = RiderApiDelivery::whereKey($delivery->id)->where('rider_id', $delivery->rider_id)
                ->where('legacy_order_id', $order->id)->lockForUpdate()->firstOrFail();

            $latest = QuickPayCheckout::forDelivery($delivery->id)->latest('id')->first();
            $attempt = QuickPayCheckout::forDelivery($delivery->id)->where('mobile_attempt_key', $input['idempotency_key'])->first();
            if ($attempt) {
                abort_unless($attempt->is($latest), 409, 'This payment attempt was replaced. Check the current payment status.');
                abort_unless($attempt->payment_method_id === (int) $input['payment_method_id']
                    && $attempt->amount_cents === (int) $input['expected_total_centavos'], 409, 'This idempotency key was used with different payment details.');

                return $attempt;
            }

            abort_if(in_array($latest?->status, [...QuickPayCheckout::ACTIVE, 'paid'], true), 409,
                'A checkout already exists. Check the current payment status.');
            $partnerId = (int) $order->partner_id;
            abort_unless(config('payment.mobile_delivery_enabled') && $partnerId > 0 && $this->gateway->availableFor($partnerId), 422,
                'Online delivery payments are unavailable for this order.');
            abort_unless($this->canStartDeliveryPayment($order, $delivery), 409, 'This delivery is not eligible for online payment.');
            abort_unless($delivery->cod_centavos === (int) $input['expected_total_centavos'], 409, 'The delivery amount changed. Review it before starting payment.');

            $method = PaymentMethod::active()->forQr()->whereIn('quickpay_channel', array_keys(PaymentMethod::QUICKPAY_CHANNELS))
                ->find($input['payment_method_id']);
            abort_unless($method, 422, 'The selected online payment method is unavailable.');

            return $this->reserve($method, $partnerId, $delivery->cod_centavos, [], [
                'rider_delivery_id' => $delivery->id,
                'mobile_attempt_key' => $input['idempotency_key'],
            ], 'Pahatud order #'.$order->id);
        }, 3);
    }

    private function canStartDeliveryPayment(Orders $order, RiderApiDelivery $delivery): bool
    {
        return (int) $order->partner_id > 0
            && (int) $order->order_status_id === LibraryStatus::STATUS_ARRIVAL_AT_CUSTOMER
            && $delivery->rider_id !== null
            && $delivery->cod_centavos > 0
            && $delivery->cod_centavos % 100 === 0
            && in_array($delivery->current_state, ['arrived_at_customer', 'customer_unreachable_resolved'], true)
            && ! DB::table('rider_api_delivery_events')->where('delivery_id', $delivery->id)->where('type', 'cod_collected')->exists();
    }

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

        $checkout = QuickPayCheckout::create($subject + [
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

        if (in_array($mode, ['qrph', 'banks'], true)) {
            Log::info('QuickPay checkout selected.', [
                'checkout_id' => $checkout->id,
                'partner_id' => $partnerId,
                'payment_method_id' => $method->id,
                'channel' => $mode,
                'amount_cents' => $amount,
                'livemode' => $checkout->livemode,
                'source' => match (true) {
                    isset($subject['rider_delivery_id']) => 'mobile_delivery',
                    isset($subject['cart_id']) => 'guest_qr',
                    default => 'pos',
                },
            ]);
        }

        return $checkout;
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

        if ($checkout->rider_delivery_id) {
            return DB::transaction(function () use ($checkout) {
                $delivery = RiderApiDelivery::lockForUpdate()->findOrFail($checkout->rider_delivery_id);
                $current = QuickPayCheckout::lockForUpdate()->findOrFail($checkout->id);
                if ($current->status === 'confirmed') {
                    $delivery->update(['cod_centavos' => 0]);
                    $current->update(['status' => 'paid', 'qr_payload' => null, 'qr_image' => null]);
                }

                return $current;
            }, 3);
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
