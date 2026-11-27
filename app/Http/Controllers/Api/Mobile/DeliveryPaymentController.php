<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Model\Orders\Orders;
use App\Model\Rider\RiderApiDelivery;
use App\QuickPayCheckout;
use App\Services\QuickPayPayments;
use App\Services\RiderApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryPaymentController extends Controller
{
    public function __construct(
        private readonly QuickPayPayments $payments,
        private readonly RiderApiService $riders,
    ) {}

    public function customer(Request $request, int $order, string $operation): JsonResponse
    {
        $order = Orders::whereKey($order)->where('user_id', $request->user()->id)->firstOrFail();
        $delivery = RiderApiDelivery::where('legacy_order_id', $order->id)->latest('id')->firstOrFail();

        return $this->payment($request, $order, $delivery, $operation);
    }

    public function rider(Request $request, string $delivery, string $operation): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $delivery = RiderApiDelivery::where('rider_id', $rider->id)
            ->when(ctype_digit($delivery), fn ($query) => $query->where('legacy_order_id', (int) $delivery),
                fn ($query) => $query->where('reference', $delivery))
            ->firstOrFail();
        $order = $delivery->order()->firstOrFail();

        return $this->payment($request, $order, $delivery, $operation);
    }

    private function payment(Request $request, Orders $order, RiderApiDelivery $delivery, string $operation): JsonResponse
    {
        if ($operation === 'start') {
            $input = $request->validate([
                'payment_method_id' => ['required', 'integer', 'min:1'],
                'expected_total_centavos' => ['required', 'integer', 'min:100'],
                'idempotency_key' => ['required', 'uuid'],
            ]);
            $checkout = $this->payments->reconcile($this->payments->startDelivery($order, $delivery, $input));
            $delivery->refresh();
        } else {
            $checkout = QuickPayCheckout::forDelivery($delivery->id)->latest('id')->first();
            if ($operation === 'refresh' && $checkout) {
                $checkout = $this->payments->reconcile($checkout);
                $delivery->refresh();
            }
        }

        $pending = in_array($checkout?->status, QuickPayCheckout::ACTIVE, true);
        $paid = $checkout?->status === 'paid';

        return response()->json([
            'order_id' => $order->id,
            'currency' => 'PHP',
            'cod_centavos' => $paid ? 0 : $delivery->cod_centavos,
            'cash_collection_blocked' => $pending || $paid,
            'payment_methods' => $pending || $paid ? [] : $this->payments->deliveryMethods($order, $delivery),
            'payment' => $checkout?->payload(),
        ], $operation !== 'show' && $pending ? 202 : 200)->header('Cache-Control', 'no-store');
    }
}
