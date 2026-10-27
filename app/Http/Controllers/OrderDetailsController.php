<?php

namespace App\Http\Controllers;

use App\Model\Cart;
use App\Model\Orders\Orders;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderDetailsController extends Controller
{
    public function admin(Orders $order)
    {
        return $this->render($order, 'dashboard.template.main', 'dashboard.report.orders', 'dashboard.orders.delivery-proof');
    }

    public function merchant(Orders $order)
    {
        $merchantId = Auth::user()?->merchant?->id;

        abort_unless($merchantId && (int) $order->partner_id === (int) $merchantId, 404);

        return $this->render($order, 'merchant.template.main', 'merchant.dashboard.report.salestoday', 'merchant.orders.delivery-proof');
    }

    private function render(Orders $order, string $layout, string $backRoute, string $proofRoute): \Illuminate\View\View
    {
        $order->load([
            'cart.payment',
            'cart.address',
            'cart.partnerlocation',
            'cart.diningTable',
            'cart.details.item',
            'partner',
            'rider',
            'orderStatus',
            'status',
        ]);

        abort_unless($order->cart, 404);

        $order->cart->cartItemVariance();
        $summary = $order->cart->cartItemSummary();
        $fulfillmentType = $order->cart->fulfillment_type ?: Cart::FULFILLMENT_DELIVERY;

        $proofs = DB::table('rider_api_delivery_proofs as proofs')
            ->join('rider_api_deliveries as deliveries', 'deliveries.id', '=', 'proofs.delivery_id')
            ->where('deliveries.legacy_order_id', $order->id)
            ->orderByDesc('proofs.created_at')
            ->get([
                'deliveries.reference as delivery_reference',
                'proofs.reference',
                'proofs.method',
                'proofs.path',
                'proofs.created_at',
            ])
            ->map(function ($proof) use ($proofRoute) {
                $proof->file_url = $proof->path
                    ? route($proofRoute, ['delivery' => $proof->delivery_reference, 'proof' => $proof->reference])
                    : null;

                return $proof;
            });

        return view('orders.details', compact(
            'backRoute',
            'fulfillmentType',
            'layout',
            'order',
            'proofs',
            'summary',
        ));
    }
}
