<?php

namespace App\Services;

use App\LibraryStatus;
use App\Model\Cart;
use App\Model\CartItem;
use App\Model\Orders\OrderProcess;
use App\Model\Orders\Orders;
use App\PartnerLocation;
use App\PartnerLocationTable;
use App\Partners;
use App\PaymentMethod;
use App\Products;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffPos
{
    public function menu(Partners $merchant, int $branchId)
    {
        return Products::where('user_id', $merchant->user_id)->availableAtLocation($branchId)
            ->with(['category', 'variants.product_details', 'user.merchant'])->orderBy('title')->get();
    }

    public function quote(Partners $merchant, int $branchId, array $input): array
    {
        abort_unless(in_array($input['fulfillment_type'], [Cart::FULFILLMENT_DINE_IN, Cart::FULFILLMENT_PICKUP], true), 422, 'The cashier POS accepts dine-in and takeaway orders only.');
        $cart = new Cart([
            'partner_id' => $merchant->id, 'fulfillment_type' => $input['fulfillment_type'],
            'delivery_fee' => $input['fulfillment_type'] === Cart::FULFILLMENT_DELIVERY ? ($input['delivery_fee'] ?? 0) : 0,
        ]);
        $cart->setRelation('partner', $merchant);
        $cart->setRelation('details', $this->items($merchant, $branchId, $input['items']));

        return collect($cart->cartItemAmounts())->map(fn ($value, $key) => $key === 'qty' ? (int) $value : (int) round($value * 100))->all();
    }

    private function items(Partners $merchant, int $branchId, array $lines)
    {
        $menu = $this->menu($merchant, $branchId)->keyBy('id');
        $items = collect();
        foreach ($lines as $index => $line) {
            $product = $menu->get($line['product_id']);
            if (! $product) {
                throw ValidationException::withMessages(["items.$index.product_id" => 'This item is unavailable at this branch.']);
            }
            $selection = app(MenuSelection::class)->select($product, $line['selections'] ?? []);
            if (isset($selection['error'])) {
                throw ValidationException::withMessages(["items.$index.selections" => $selection['error']]);
            }
            $item = new CartItem([
                'item_id' => $product->id, 'qty' => $line['qty'],
                'price' => (float) $product->getPrice(true),
                'price_comm_total' => (float) str_replace(',', '', $product->getPriceComm()),
                'discount_amount' => (float) str_replace(',', '', $product->getDiscountAmount()),
                'variance_total' => $selection['total'], 'variance_total_comm_total' => $selection['commission_total'],
                'variance_content' => serialize($selection['content']), 'instruction' => $line['instruction'] ?? null,
            ]);
            $item->setRelation('item', $product);
            $items->push($item);
        }

        return $items;
    }

    public function create(Partners $merchant, int $branchId, int $actorId, array $input): Orders
    {
        return DB::transaction(function () use ($merchant, $branchId, $actorId, $input) {
            // Serialize duplicate submissions and table/bill changes at this branch.
            PartnerLocation::where('partner_id', $merchant->id)->where('active', true)->lockForUpdate()->findOrFail($branchId);
            $fingerprint = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
            $session = 'pos-'.$input['idempotency_key'];
            $existing = Cart::where('session_id', $session)->first();
            if ($existing) {
                abort_unless((int) $existing->partner_id === (int) $merchant->id && (int) $existing->partner_location_address_id === $branchId, 409);
                abort_unless(hash_equals((string) $existing->pos_request_hash, $fingerprint), 409, 'This submission key belongs to a different order. Review the previous order before submitting again.');

                return $existing->order;
            }
            $type = $input['fulfillment_type'];
            abort_unless(in_array($type, [Cart::FULFILLMENT_DINE_IN, Cart::FULFILLMENT_PICKUP], true), 422, 'The cashier POS accepts dine-in and takeaway orders only.');
            $table = $type === Cart::FULFILLMENT_DINE_IN
                ? PartnerLocationTable::where('partner_location_id', $branchId)->where('active', true)->where('is_available', true)->lockForUpdate()->findOrFail($input['dining_table_id'])
                : null;
            $paymentMethodId = $input['payment_method_id'] ?? null;
            if ($paymentMethodId) {
                PaymentMethod::active()->forQr()->findOrFail($paymentMethodId);
            }
            $cart = Cart::create([
                'session_id' => $session, 'partner_id' => $merchant->id,
                'partner_location_address_id' => $branchId, 'fulfillment_type' => $type,
                'dining_table_id' => $table?->id, 'order_source' => Cart::ORDER_SOURCE_POS,
                'pos_request_hash' => $fingerprint, 'payment_id' => $paymentMethodId, 'notes' => $input['notes'] ?? null,
                'delivery_fee' => $type === Cart::FULFILLMENT_DELIVERY ? ($input['delivery_fee'] ?? 0) : 0,
            ]);
            foreach ($this->items($merchant, $branchId, $input['items']) as $item) {
                $cart->details()->save($item);
            }
            $cart->forceFill([
                'fullname' => $input['fullname'] ?? 'Walk-in customer', 'mobile' => $input['mobile'] ?? null,
                'order_no' => str_pad((string) $cart->id, 6, '0', STR_PAD_LEFT),
                'processed_at' => now(), 'submitted_at' => now(), 'active' => true,
                'delivery_date' => now()->format('M d, Y'), 'delivery_time' => 'ASAP',
            ])->save();
            if ($type === Cart::FULFILLMENT_DELIVERY) {
                $cart->address()->create([
                    'address_1' => $input['address'], 'mobile' => $input['mobile'],
                    'lat' => $input['lat'], 'long' => $input['long'],
                ]);
            }
            $order = Orders::create([
                'cart_id' => $cart->id, 'partner_id' => $merchant->id,
                'submitted_at' => now(), 'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
            ]);
            OrderProcess::create(['order_id' => $order->id, 'status_id' => LibraryStatus::STATUS_ORDER_PLACED, 'user_id' => $actorId]);

            return $order;
        });
    }
}
