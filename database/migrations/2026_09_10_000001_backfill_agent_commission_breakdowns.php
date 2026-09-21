<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_commissions') || ! Schema::hasColumn('agent_commissions', 'subtotal_amount')) {
            return;
        }

        DB::table('agent_commissions')
            ->whereNull('subtotal_amount')
            ->orderBy('id')
            ->chunkById(100, function ($commissions) {
                foreach ($commissions as $commission) {
                    $order = DB::table('order')->where('id', $commission->order_id)->first(['cart_id', 'partner_id']);

                    if (! $order) {
                        continue;
                    }

                    $cart = DB::table('cart')->where('id', $order->cart_id)->first(['delivery_fee', 'discount_amount']);

                    if (! $cart) {
                        continue;
                    }

                    $items = DB::table('cart_details')
                        ->where('cart_id', $order->cart_id)
                        ->selectRaw('COALESCE(SUM(qty * (price + variance_total)), 0) as subtotal')
                        ->selectRaw('COALESCE(SUM(qty * discount_amount), 0) as item_discount')
                        ->first();
                    $restaurantPercentage = DB::table('partners')
                        ->where('id', $commission->restaurant_id ?: $order->partner_id)
                        ->value('percentage');

                    $subtotal = round((float) $items->subtotal, 2);
                    $deliveryFee = round((float) $cart->delivery_fee, 2);
                    $discount = round((float) $cart->discount_amount + (float) $items->item_discount, 2);
                    $total = round($subtotal + $deliveryFee - $discount, 2);
                    $pahatudPercentage = round((float) ($restaurantPercentage ?? config('agent.pahatud_commission_percentage')), 2);
                    $pahatudAmount = round($subtotal * ($pahatudPercentage / 100), 2);
                    $agentAmount = round($pahatudAmount * ((float) $commission->commission_percentage / 100), 2);

                    DB::table('agent_commissions')->where('id', $commission->id)->update([
                        'order_amount' => $total,
                        'subtotal_amount' => $subtotal,
                        'delivery_fee_amount' => $deliveryFee,
                        'discount_amount' => $discount,
                        'total_amount' => $total,
                        'pahatud_commission_percentage' => $pahatudPercentage,
                        'pahatud_commission_amount' => $pahatudAmount,
                        'commission_amount' => $agentAmount,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Corrected financial snapshots are intentionally retained on rollback.
    }
};
