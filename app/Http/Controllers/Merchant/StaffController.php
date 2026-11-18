<?php

namespace App\Http\Controllers\Merchant;

use App\DiningBill;
use App\DiningPayment;
use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\PartnerLocationTable;
use App\Partners;
use App\PaymentMethod;
use App\Services\DiningRoom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    private function context(Request $request): array
    {
        $user = $request->user();
        $merchant = $request->attributes->get('merchantAccount');
        $branch = $request->attributes->get('merchantBranch');
        $owner = false;
        if (! $merchant) {
            $merchant = Partners::where('user_id', $user->id)->first();
            $owner = (bool) $merchant;
            if ($owner) {
                $branch = $request->filled('branch_id')
                    ? $merchant->locations()->findOrFail($request->integer('branch_id'))
                    : $merchant->locations()->orderBy('id')->first();
            } else {
                $assignment = $user->merchantBranchAssignment()->where('active', true)->first();
                $merchant = $assignment?->partner;
                $branch = $assignment?->branch;
            }
        }
        abort_unless($merchant?->qr_ordering_enabled, 403, 'QR ordering is not enabled for this merchant.');
        abort_unless($merchant && $branch && (int) $branch->partner_id === (int) $merchant->id, 403, 'No active branch is assigned to this login.');
        $staff = $user->isMerchantStaff();
        $abilities = [
            'prepare' => ! $staff || $user->hasPermissionTo('branch.orders.prepare', 'web'),
            'cashier' => ! $staff || $user->hasPermissionTo('branch.bills.view', 'web'),
            'serve' => ! $staff || $user->hasPermissionTo('branch.orders.prepare', 'web') || $user->hasPermissionTo('branch.tables.manage', 'web'),
            'tables' => ! $staff || $user->hasPermissionTo('branch.tables.manage', 'web'),
            'pay' => ! $staff || $user->hasPermissionTo('branch.payments.record', 'web'),
        ];
        abort_unless(! $staff || $user->hasPermissionTo('branch.orders.view', 'web'), 403);

        return [$merchant, $branch, $abilities, $owner];
    }

    public function index(Request $request, DiningRoom $room)
    {
        [$merchant, $branch, $abilities, $owner] = $this->context($request);
        $view = $request->query('view', $abilities['cashier'] ? 'overview' : 'kitchen');
        abort_unless(in_array($view, ['overview', 'tables', 'orders', 'payments', 'kitchen'], true), 404);
        abort_unless($view === 'kitchen' || $abilities['cashier'], 403);

        return response()->view('merchant.pages.staff.index', [
            'bootstrap' => [
                'initial' => $this->payload($merchant->id, $branch->id, $abilities, $room),
                'view' => $view, 'abilities' => $abilities,
                'merchant' => $merchant->restaurant_name,
                'logo' => asset('images/logo.jpg'),
                'branch' => ['id' => $branch->id, 'name' => $branch->address_1, 'city' => $branch->city],
                'branches' => $owner ? $merchant->locations()->orderBy('id')->get(['id', 'address_1', 'city']) : [],
                'user' => ['name' => $request->user()->fullname, 'role' => $owner ? 'Merchant owner' : ($request->user()->isMerchantStaff() ? ($request->user()->hasRole('kitchen') ? 'Kitchen' : 'Cashier') : 'Branch operator')],
                'endpoint' => route('merchant.staff.data'),
                'base' => url('/merchant/staff'), 'logout' => route('merchant.logout'),
                'dashboard' => $owner ? route('merchant.dashboard.index') : null,
            ],
        ])->header('Cache-Control', 'no-store');
    }

    public function data(Request $request, DiningRoom $room)
    {
        [$merchant, $branch, $abilities] = $this->context($request);

        return response()->json($this->payload($merchant->id, $branch->id, $abilities, $room))->header('Cache-Control', 'no-store');
    }

    private function payload(int $partnerId, int $branchId, array $abilities, DiningRoom $room): array
    {
        $orders = Orders::with(['cart.details.item', 'cart.diningTable', 'cart.payment', 'billRound.bill'])
            ->where('partner_id', $partnerId)->whereNotNull('submitted_at')
            ->whereHas('cart', fn ($query) => $query->where('partner_location_address_id', $branchId)->where('fulfillment_type', Cart::FULFILLMENT_DINE_IN))
            ->where(function ($query) {
                $query->whereNotIn('order_status_id', [7, 8, 9])->orWhereNull('order_status_id')
                    ->orWhereHas('billRound.bill', fn ($bill) => $bill->where('status', 'open'))
                    ->orWhere('submitted_at', '>=', now()->startOfDay());
            })->oldest('submitted_at')->get();
        $tickets = $orders->map(function ($order) use ($room, $abilities) {
            $round = $order->billRound;
            $statusId = (int) ($order->order_status_id ?: $order->status_id);
            $items = $round?->items ?? $room->items($order->cart);
            if (! $abilities['cashier']) {
                $items = array_map(fn ($item) => array_diff_key($item, ['unit_price' => true]), $items);
            }

            return [
                'id' => $order->id, 'number' => $order->cart->order_no ?: (string) $order->id,
                'table_id' => $order->cart->dining_table_id, 'table_name' => $order->cart->diningTable?->name ?? 'Dine-in',
                'created' => Carbon::parse($order->submitted_at)->toIso8601String(),
                'status_id' => $statusId,
                'status' => match ($statusId) {
                    1 => 'new', 2, 3 => 'preparing', 4 => 'ready', 7, 9 => 'served', 8 => 'cancelled', default => 'new'
                },
                'round' => $round?->round ?? 1, 'bill_id' => $round?->dining_bill_id,
                'paid' => $round?->bill?->status === 'paid', 'items' => $items,
                'payment_method_id' => $order->cart->payment_id,
                'payment_method' => $this->paymentMethodName($order->cart->payment),
                'total' => $abilities['cashier'] ? ($round?->amounts['total'] ?? (int) round($order->cart->cartItemAmounts()['total'] * 100)) : null,
            ];
        });
        $bills = $abilities['cashier'] ? DiningBill::with('rounds.order.cart.payment')->where('partner_id', $partnerId)
            ->where('partner_location_id', $branchId)->where('status', 'open')->get()->map(function ($bill) use ($room) {
                $rounds = $bill->rounds->filter(fn ($round) => $round->order && (int) $round->order->order_status_id !== 8);
                $preferences = $rounds->map(fn ($round) => $round->order->cart?->payment)->filter()->unique('id')->values();
                $preferred = $preferences->count() === 1 ? $preferences->first() : null;

                return [
                    'id' => $bill->id, 'table_id' => $bill->dining_table_id, 'totals' => $room->totals($bill),
                    'order_ids' => $rounds->pluck('order_id')->values(),
                    'payment_method_id' => $preferred?->id,
                    'payment_method' => $preferences->isEmpty()
                        ? null
                        : ($preferred ? $this->paymentMethodName($preferred) : 'Mixed preferences'),
                ];
            }) : collect();
        $payments = $abilities['cashier'] ? DiningPayment::whereHas('bill', fn ($query) => $query->where('partner_id', $partnerId)->where('partner_location_id', $branchId))
            ->where('created_at', '>=', now()->startOfDay())->latest()->get()->map(fn ($payment) => [
                'id' => $payment->id, 'bill_id' => $payment->dining_bill_id, 'table_id' => $payment->bill->dining_table_id,
                'method' => $payment->method_name, 'amount' => $payment->amount_cents, 'reference' => $payment->reference,
                'cash_tendered' => $payment->cash_tendered_cents, 'change' => $payment->change_cents,
                'created' => $payment->created_at->toIso8601String(),
            ]) : collect();

        return [
            'abilities' => $abilities, 'orders' => $tickets, 'bills' => $bills, 'payments' => $payments,
            'tables' => $abilities['cashier'] ? PartnerLocationTable::where('partner_location_id', $branchId)->orderBy('name')->get(['id', 'name', 'capacity', 'active', 'is_available']) : [],
            'payment_methods' => $abilities['pay'] ? PaymentMethod::active()->forQr()->get(['id', 'title'])->map(fn ($method) => [
                'id' => $method->id,
                'title' => (int) $method->id === PaymentMethod::CHECKOUT_COD ? 'Cash' : $method->title,
                'is_cash' => (int) $method->id === PaymentMethod::CHECKOUT_COD,
            ]) : [],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function transition(Request $request, int $order, DiningRoom $room)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        $input = $request->validate(['action' => ['required', Rule::in(['preparing', 'ready', 'served'])], 'expected_status' => ['required', 'integer']]);
        abort_unless($input['action'] === 'served' ? $abilities['serve'] : $abilities['prepare'], 403);
        $room->transition($order, $merchant->id, $branch->id, $input['action'], $request->user()->id, $input['expected_status']);

        return response()->json(['message' => 'Order updated.']);
    }

    public function payment(Request $request, int $bill, DiningRoom $room)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        abort_unless($abilities['pay'], 403);
        $input = $request->validate([
            'payment_method_id' => ['required', 'integer'], 'idempotency_key' => ['required', 'uuid'],
            'expected_total' => ['required', 'integer', 'min:0'], 'reference' => ['nullable', 'string', 'max:150'],
            'cash_tendered_cents' => ['nullable', 'integer', 'min:0'],
        ]);
        $payment = $room->pay($bill, $merchant->id, $branch->id, $request->user()->id, $input);

        return response()->json(['message' => 'Payment recorded. Table is available.', 'payment_id' => $payment->id]);
    }

    public function receipt(Request $request, int $bill, DiningRoom $room)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        abort_unless($abilities['cashier'], 403);
        $bill = DiningBill::with(['rounds.order.cart', 'payment'])->where('partner_id', $merchant->id)
            ->where('partner_location_id', $branch->id)->findOrFail($bill);
        $table = PartnerLocationTable::find($bill->dining_table_id);

        return response()->json([
            'id' => $bill->id, 'table_name' => $table?->name ?? 'Dining table',
            'paid' => $bill->status === 'paid', 'totals' => $bill->totals ?? $room->totals($bill),
            'orders' => $bill->receipt ?? $bill->rounds->filter(fn ($round) => $round->order && (int) $round->order->order_status_id !== 8)
                ->map(fn ($round) => ['id' => $round->order_id, 'number' => $round->order->cart?->order_no ?: (string) $round->order_id, 'round' => $round->round, 'items' => $round->items])->values(),
            'payment' => $bill->payment ? [
                'method' => $bill->payment->method_name,
                'reference' => $bill->payment->reference,
                'cash_tendered' => $bill->payment->cash_tendered_cents,
                'change' => $bill->payment->change_cents,
                'created' => $bill->payment->created_at->toIso8601String(),
            ] : null,
        ])->header('Cache-Control', 'no-store');
    }

    public function release(Request $request, int $bill)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        abort_unless($abilities['tables'], 403);
        DB::transaction(function () use ($bill, $merchant, $branch) {
            $record = DiningBill::where('partner_id', $merchant->id)->where('partner_location_id', $branch->id)->findOrFail($bill);
            PartnerLocationTable::whereKey($record->dining_table_id)->lockForUpdate()->firstOrFail();
            $record = DiningBill::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'open', 409, 'This bill is already closed.');
            $orderIds = $record->rounds()->pluck('order_id');
            $orders = Orders::whereIn('id', $orderIds)->lockForUpdate()->get();
            abort_if($orders->count() !== $orderIds->count(), 409, 'An order is missing from this bill.');
            abort_if($orders->contains(fn ($order) => (int) $order->order_status_id !== 8), 409, 'Only fully cancelled bills can be released without payment.');
            $record->update(['status' => 'void', 'closed_at' => now()]);
        });

        return response()->json(['message' => 'Cancelled bill closed. Table released.']);
    }

    public function table(Request $request, int $table)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        abort_unless($abilities['tables'], 403);
        $input = $request->validate(['is_available' => ['required', 'boolean']]);
        PartnerLocationTable::where('partner_location_id', $branch->id)->findOrFail($table)->update($input);

        return response()->json(['message' => 'Table ordering availability updated.']);
    }

    private function paymentMethodName(?PaymentMethod $method): ?string
    {
        if (! $method) {
            return null;
        }

        return (int) $method->id === PaymentMethod::CHECKOUT_COD ? 'Cash' : $method->title;
    }
}
