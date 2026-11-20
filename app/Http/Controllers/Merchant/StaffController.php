<?php

namespace App\Http\Controllers\Merchant;

use App\DiningBill;
use App\DiningPayment;
use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\PartnerLocationCheckoutOption;
use App\PartnerLocationTable;
use App\Partners;
use App\PaymentMethod;
use App\Services\DiningRoom;
use App\Services\StaffOrderPresenter;
use App\Services\StaffPos;
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
            'reports' => ! $staff,
            'pos' => ! $staff || $user->hasPermissionTo('branch.payments.record', 'web'),
            'pay' => ! $staff || $user->hasPermissionTo('branch.payments.record', 'web'),
        ];
        abort_unless(! $staff || $user->hasPermissionTo('branch.orders.view', 'web'), 403);

        $request->attributes->set('merchantAccount', $merchant);

        return [$merchant, $branch, $abilities, $owner];
    }

    public function index(Request $request, DiningRoom $room)
    {
        [$merchant, $branch, $abilities, $owner] = $this->context($request);
        $view = $request->query('view', $abilities['cashier'] ? 'overview' : 'kitchen');
        abort_unless(in_array($view, ['overview', 'tables', 'orders', 'payments', 'pos', 'kitchen', 'history'], true), 404);
        abort_unless($view !== 'pos' || $abilities['pos'], 403);
        abort_unless(in_array($view, ['kitchen', 'history'], true) || $abilities['cashier'], 403);

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
        $orders = Orders::with(['cart.details.item', 'cart.diningTable', 'cart.payment', 'billRound.bill.payment', 'orderprocess'])
            ->where('partner_id', $partnerId)->whereNotNull('submitted_at')
            ->whereHas('cart', fn ($query) => $query->where('partner_location_address_id', $branchId))
            ->where(function ($query) {
                $query->whereRaw('COALESCE(order_status_id, status_id, 1) NOT IN (7, 8, 9)')
                    ->orWhere(function ($served) {
                        $served->whereRaw('COALESCE(order_status_id, status_id, 1) IN (7, 9)')
                            ->whereDoesntHave('billRound.bill.payment')
                            ->where(function ($balance) {
                                $balance->whereHas('billRound.bill', fn ($bill) => $bill->where('status', 'open'))
                                    ->orWhere('submitted_at', '>=', now()->startOfDay());
                            });
                    });
            })->oldest('submitted_at')->get();
        $tickets = $orders->map(fn ($order) => app(StaffOrderPresenter::class)->ticket($order, $abilities));
        $bills = $abilities['cashier'] ? DiningBill::with('rounds.order.cart.payment')->where('partner_id', $partnerId)
            ->where('partner_location_id', $branchId)->where('status', 'open')->get()->map(function ($bill) use ($room) {
                $rounds = $bill->rounds->filter(fn ($round) => $round->order && (int) $round->order->order_status_id !== 8);
                $preferences = $rounds->map(fn ($round) => $round->order->cart?->payment)->filter()->unique('id')->values();
                $preferred = $preferences->count() === 1 ? $preferences->first() : null;

                return [
                    'id' => $bill->id, 'table_id' => $bill->dining_table_id, 'name' => $bill->dining_table_id ? null : 'Order #'.($rounds->first()?->order?->cart?->order_no ?? ''), 'totals' => $room->totals($bill),
                    'order_ids' => $rounds->pluck('order_id')->values(),
                    'payment_method_id' => $preferred?->id,
                    'payment_method' => $preferences->isEmpty()
                        ? null
                        : ($preferred ? $this->paymentMethodName($preferred) : 'Mixed preferences'),
                ];
            }) : collect();
        $payments = $abilities['cashier'] ? DiningPayment::whereHas('bill', fn ($query) => $query->where('partner_id', $partnerId)->where('partner_location_id', $branchId))
            ->where('created_at', '>=', now()->startOfDay())->latest()->get()->map(fn ($payment) => [
                'order_number' => $payment->bill->receipt[0]['number'] ?? null,
                'id' => $payment->id, 'bill_id' => $payment->dining_bill_id, 'table_id' => $payment->bill->dining_table_id,
                'method' => $payment->method_name, 'amount' => $payment->amount_cents, 'reference' => $payment->reference,
                'cash_tendered' => $payment->cash_tendered_cents, 'change' => $payment->change_cents,
                'created' => $payment->created_at->toIso8601String(),
            ]) : collect();

        return [
            'fulfillment_types' => $abilities['pos'] ? array_values(array_filter(PartnerLocationCheckoutOption::availableForLocation($branchId), fn ($option) => $option['type'] === Cart::FULFILLMENT_DINE_IN)) : [],
            'menu' => $abilities['pos'] ? app(StaffPos::class)->menu(Partners::findOrFail($partnerId), $branchId)->map(fn ($product) => [
                'category_id' => $product->category_id, 'category' => $product->category?->name ?? 'Other',
                'description' => trim(strip_tags($product->short_description ?: $product->description ?: '')),
                'image' => $product->img ? $product->getProductImage() : null,
                'id' => $product->id, 'name' => $product->title, 'price' => (int) round((float) $product->getPrice(true) * 100),
                'variants' => $product->variants->map(fn ($header) => [
                    'id' => $header->id, 'name' => $header->title, 'required' => (bool) $header->is_required, 'multiple' => (bool) $header->is_multiple,
                    'options' => $header->product_details->map(fn ($detail) => ['id' => $detail->id, 'name' => $detail->title, 'price' => (int) round((float) str_replace(',', '', $detail->getPrice()) * 100)]),
                ]),
            ]) : [],
            'abilities' => $abilities, 'orders' => $tickets, 'bills' => $bills, 'payments' => $payments,
            'tables' => $abilities['cashier'] ? PartnerLocationTable::where('partner_location_id', $branchId)->orderBy('name')->get(['id', 'name', 'capacity', 'active', 'is_available']) : [],
            'payment_methods' => $abilities['pay'] ? PaymentMethod::active()->forQr()->get(['id', 'title'])->map(fn ($method) => [
                'id' => $method->id,
                'title' => $method->isCash() ? 'Cash' : $method->title,
                'is_cash' => $method->isCash(),
            ]) : [],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function history(Request $request)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        $input = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['newest', 'fastest'])],
        ]);
        $from = Carbon::parse($input['from'] ?? now()->subDays(6)->toDateString())->startOfDay();
        $to = Carbon::parse($input['to'] ?? now()->toDateString())->endOfDay();
        abort_unless($from->lessThanOrEqualTo($to), 422, 'Choose a valid date range.');
        $fastest = ($input['sort'] ?? 'newest') === 'fastest';
        abort_unless(! $fastest || $abilities['reports'], 403);
        $query = Orders::with(['cart.details.item', 'cart.diningTable', 'cart.payment', 'billRound.bill.payment', 'orderprocess'])
            ->where('partner_id', $merchant->id)
            ->whereHas('cart', fn ($q) => $q->where('partner_location_address_id', $branch->id))
            ->where(function ($dates) use ($from, $to) {
                $dates->whereBetween('submitted_at', [$from, $to])
                    ->orWhere(fn ($missing) => $missing->whereNull('submitted_at')->whereBetween('created_at', [$from, $to]));
            });
        if ($fastest) {
            $query->whereHas('cart', fn ($q) => $q->whereIn('fulfillment_type', [Cart::FULFILLMENT_DINE_IN, Cart::FULFILLMENT_PICKUP]))
                ->whereNotNull('delivered_at')->whereColumn('delivered_at', '>=', 'submitted_at')
                ->where(fn ($served) => $served->whereRaw('COALESCE(order_status_id, status_id, 1) = 9')->orWhereHas('orderprocess', fn ($process) => $process->where('status_id', 9)));
            $duration = DB::connection()->getDriverName() === 'sqlite'
                ? "(strftime('%s', delivered_at) - strftime('%s', submitted_at))"
                : 'TIMESTAMPDIFF(SECOND, submitted_at, delivered_at)';
            $query->orderByRaw($duration.' ASC')->orderBy('id');
        } else {
            $query->latest('submitted_at')->latest('id');
        }
        $page = $query->paginate(30);

        return response()->json([
            'orders' => collect($page->items())->map(fn ($order) => app(StaffOrderPresenter::class)->ticket($order, $abilities)),
            'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(),
            'branch' => ['id' => $branch->id, 'name' => $branch->address_1],
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, StaffPos $pos)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        abort_unless($abilities['pos'] && $abilities['cashier'], 403);
        $input = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'fulfillment_type' => ['required', Rule::in([Cart::FULFILLMENT_DINE_IN])],
            'dining_table_id' => ['required_if:fulfillment_type,dine_in', 'nullable', 'integer'],
            'payment_method_id' => ['required', 'integer'],
            'fullname' => ['nullable', 'string', 'max:150'], 'mobile' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'max:30'],
            'address' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'max:250'],
            'lat' => ['required_if:fulfillment_type,delivery', 'nullable', 'numeric', 'between:-90,90'],
            'long' => ['required_if:fulfillment_type,delivery', 'nullable', 'numeric', 'between:-180,180'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer'], 'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.instruction' => ['nullable', 'string', 'max:250'],
            'items.*.selections' => ['nullable', 'array', 'max:100'],
            'items.*.selections.*.variant_id' => ['required', 'integer'],
            'items.*.selections.*.product_detail_id' => ['required', 'integer'],
        ]);
        $order = $pos->create($merchant, $branch->id, $request->user()->id, $input);

        return response()->json(['message' => 'Order sent to kitchen.', 'order_id' => $order->id,
            'bill_id' => $order->billRound?->dining_bill_id, 'number' => $order->cart->order_no], 201);
    }

    public function quote(Request $request, StaffPos $pos)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        abort_unless($abilities['pos'] && $abilities['cashier'], 403);
        $input = $request->validate([
            'fulfillment_type' => ['required', Rule::in([Cart::FULFILLMENT_DINE_IN])],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.instruction' => ['nullable', 'string', 'max:250'],
            'items.*.selections' => ['nullable', 'array', 'max:100'],
            'items.*.selections.*.variant_id' => ['required', 'integer'],
            'items.*.selections.*.product_detail_id' => ['required', 'integer'],
        ]);

        return response()->json(['totals' => $pos->quote($merchant, $branch->id, $input)])->header('Cache-Control', 'no-store');
    }

    public function transition(Request $request, int $order, DiningRoom $room)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        $input = $request->validate(['action' => ['required', Rule::in(['preparing', 'ready', 'served'])], 'expected_status' => ['required', 'integer']]);
        abort_unless($input['action'] === 'served' ? $abilities['serve'] : $abilities['prepare'], 403);
        $record = Orders::where('partner_id', $merchant->id)->whereHas('cart', fn ($q) => $q->where('partner_location_address_id', $branch->id))->findOrFail($order);
        if ($record->cart->fulfillment_type !== Cart::FULFILLMENT_DINE_IN) {
            $request->merge(['store_location_id' => $branch->id, 'merchant_location_id' => $branch->id]);
            $request->attributes->set('merchantBranch', $branch);

            return DB::transaction(function () use ($record, $request, $input) {
                $record = Orders::whereKey($record->id)->lockForUpdate()->firstOrFail();
                $status = (int) ($record->order_status_id ?: $record->status_id);
                $target = ['preparing' => 3, 'ready' => 4, 'served' => 9][$input['action']];
                abort_if($record->cart->requiresDelivery() && $input['action'] === 'served', 409, 'Delivery completion is recorded by the rider.');
                if ($status === $target) {
                    return response()->json(['message' => 'Order already updated.']);
                }
                abort_unless($status === $input['expected_status'], 409, 'This order changed. Refresh and try again.');
                $controller = app(OrderController::class);

                return match ($input['action']) {
                    'preparing' => $controller->acceptOrder($record, $request),
                    'ready' => $controller->markOrderReadyForPickup($record, $request),
                    'served' => $controller->completeOrder($record, $request),
                };
            });
        }
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

        return response()->json(['message' => 'Payment recorded.', 'payment_id' => $payment->id]);
    }

    public function receipt(Request $request, int $bill, DiningRoom $room)
    {
        [$merchant, $branch, $abilities] = $this->context($request);
        abort_unless($abilities['cashier'], 403);
        $bill = DiningBill::with(['rounds.order.cart', 'payment'])->where('partner_id', $merchant->id)
            ->where('partner_location_id', $branch->id)->findOrFail($bill);
        $table = PartnerLocationTable::find($bill->dining_table_id);

        $receiptOrders = collect($bill->receipt ?? $bill->rounds->filter(fn ($round) => $round->order && (int) $round->order->order_status_id !== 8)
            ->map(fn ($round) => ['id' => $round->order_id, 'number' => $round->order->cart?->order_no ?: (string) $round->order_id, 'round' => $round->round, 'items' => $round->items])->values()->all());
        $receiptOrders = $receiptOrders->map(function ($snapshot) use ($bill, $abilities) {
            $order = $bill->rounds->firstWhere('order_id', $snapshot['id'])?->order;
            $ticket = $order ? app(StaffOrderPresenter::class)->ticket($order, $abilities) : null;

            return $snapshot + ['notes' => $ticket['notes'] ?? null, 'type' => $ticket['type'] ?? null,
                'placed_at' => $ticket['placed_at'] ?? null, 'status' => $ticket['status'] ?? 'unknown', 'status_id' => $ticket['status_id'] ?? null,
                'served_at' => $ticket['served_at'] ?? null, 'time_to_serve_seconds' => $ticket['time_to_serve_seconds'] ?? null];
        });

        return response()->json([
            'id' => $bill->id, 'table_name' => $table?->name ?? 'POS order #'.($bill->rounds->first()?->order?->cart?->order_no ?? $bill->id),
            'paid' => $bill->status === 'paid', 'totals' => $bill->totals ?? $room->totals($bill),
            'orders' => $receiptOrders,
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
            if ($record->dining_table_id) {
                PartnerLocationTable::whereKey($record->dining_table_id)->lockForUpdate()->firstOrFail();
            }
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

        return $method->isCash() ? 'Cash' : $method->title;
    }
}
