<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\LibraryStatus;
use App\Model\Orders\Orders;
use App\Partners;
use App\StatementAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StatementAccountController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'merchant' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_range' => ['nullable', 'string', 'max:100'],
        ]);

        [$rangeStart, $rangeEnd] = $this->dateRange($filters['date_range'] ?? null);

        $completedAt = DB::raw('COALESCE(delivered_at, updated_at, submitted_at)');
        $orders = Orders::query()
            ->with(['cart', 'partner', 'orderStatus'])
            ->whereNotNull('submitted_at')
            ->whereIn('order_status_id', LibraryStatus::COMPLETED_STATUSES)
            ->whereDoesntHave('statementItem')
            ->when($filters['merchant'] ?? null, fn ($query, $merchant) => $query->where('partner_id', $merchant))
            ->when($rangeStart, fn ($query) => $query->whereBetween($completedAt, [$rangeStart, $rangeEnd]))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('id', $search)
                        ->orWhereHas('cart', fn ($cart) => $cart->where('order_no', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc($completedAt)
            ->paginate(50)
            ->withQueryString();

        $orders->getCollection()->each(function (Orders $order) {
            $order->statement_completed_at = $order->delivered_at ?: $order->updated_at ?: $order->submitted_at;
            $order->statement_summary = $order->cart?->cartItemSummary() ?? [];
        });

        $merchants = Partners::query()->orderBy('restaurant_name')->get(['id', 'restaurant_name']);
        $statements = StatementAccount::query()
            ->with('partner:id,restaurant_name')
            ->latest()
            ->paginate(15, ['*'], 'statements_page')
            ->withQueryString();

        return view('dashboard.pages.statements.index', compact('filters', 'merchants', 'orders', 'statements'));
    }

    private function dateRange(?string $dateRange): array
    {
        if (blank($dateRange)) {
            return [null, null];
        }

        $dates = explode(' - ', $dateRange, 2);

        if (count($dates) !== 2) {
            throw ValidationException::withMessages([
                'date_range' => 'Please select a valid completed date range.',
            ]);
        }

        try {
            $startInput = trim($dates[0]);
            $endInput = trim($dates[1]);
            $start = Carbon::createFromFormat('m/d/Y', $startInput)->startOfDay();
            $end = Carbon::createFromFormat('m/d/Y', $endInput)->endOfDay();

            if ($start->format('m/d/Y') !== $startInput || $end->format('m/d/Y') !== $endInput) {
                throw new \InvalidArgumentException('Invalid date range.');
            }
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'date_range' => 'Please select a valid completed date range.',
            ]);
        }

        if ($start->greaterThan($end)) {
            throw ValidationException::withMessages([
                'date_range' => 'The completed date range must end on or after its start date.',
            ]);
        }

        return [$start, $end];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => ['required', 'integer', Rule::exists('partners', 'id')],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $statement = DB::transaction(function () use ($validated) {
            $orders = Orders::query()
                ->with(['cart', 'orderStatus'])
                ->whereIn('id', $validated['order_ids'])
                ->where('partner_id', $validated['partner_id'])
                ->whereNotNull('submitted_at')
                ->whereIn('order_status_id', LibraryStatus::COMPLETED_STATUSES)
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== count($validated['order_ids']) || $orders->contains(fn ($order) => ! $order->cart)) {
                throw ValidationException::withMessages([
                    'order_ids' => 'Every selected order must belong to this merchant and be completed or delivered.',
                ]);
            }

            $alreadyProcessed = DB::table('statement_account_items')
                ->whereIn('order_id', $orders->pluck('id'))
                ->exists();

            if ($alreadyProcessed) {
                throw ValidationException::withMessages([
                    'order_ids' => 'One or more selected orders have already been included in a statement. Refresh and try again.',
                ]);
            }

            $lines = $orders->map(function (Orders $order) {
                $summary = $order->cart->cartItemSummary();
                $amount = fn ($key) => (float) str_replace(',', '', (string) ($summary[$key] ?? 0));
                $total = $amount('total');
                $commission = $amount('total_comm');

                return [
                    'order_id' => $order->id,
                    'order_number' => $order->cart->order_no,
                    'completed_at' => $order->delivered_at ?: $order->updated_at ?: $order->submitted_at,
                    'fulfillment_type' => $order->cart->fulfillment_type ?: 'delivery',
                    'order_status' => $order->orderStatus?->title ?: 'Completed',
                    'quantity' => (int) ($summary['qty'] ?? 0),
                    'subtotal_amount' => $amount('sub_total'),
                    'convenience_fee_amount' => $amount('convenience_fee'),
                    'vat_amount' => $amount('vat_amount'),
                    'delivery_fee_amount' => $amount('delivery_fee'),
                    'discount_amount' => $amount('discount'),
                    'total_amount' => $total,
                    'commission_amount' => $commission,
                    'merchant_net_amount' => $total - $commission,
                ];
            });

            $statement = StatementAccount::create([
                'reference' => 'SOA-TMP-'.str()->random(20),
                'partner_id' => $validated['partner_id'],
                'created_by' => Auth::id(),
                'status' => StatementAccount::STATUS_DRAFT,
                'order_count' => $lines->count(),
                'period_start' => $lines->min('completed_at'),
                'period_end' => $lines->max('completed_at'),
                'subtotal_amount' => $lines->sum('subtotal_amount'),
                'convenience_fee_amount' => $lines->sum('convenience_fee_amount'),
                'vat_amount' => $lines->sum('vat_amount'),
                'delivery_fee_amount' => $lines->sum('delivery_fee_amount'),
                'discount_amount' => $lines->sum('discount_amount'),
                'total_amount' => $lines->sum('total_amount'),
                'commission_amount' => $lines->sum('commission_amount'),
                'merchant_net_amount' => $lines->sum('merchant_net_amount'),
                'notes' => $validated['notes'] ?? null,
                'issued_at' => null,
            ]);

            $statement->update(['reference' => 'SOA-'.now()->format('Ym').'-'.str_pad((string) $statement->id, 6, '0', STR_PAD_LEFT)]);
            $statement->items()->createMany($lines->all());

            return $statement;
        });

        return redirect()->route('dashboard.statements.show', $statement)
            ->with('success', 'Draft '.$statement->reference.' was created. Review it carefully before publishing it to the merchant.');
    }

    public function show(StatementAccount $statement)
    {
        $statement->load(['partner', 'items', 'creator']);

        return view('dashboard.pages.statements.show', compact('statement'));
    }

    public function publish(StatementAccount $statement)
    {
        DB::transaction(function () use ($statement) {
            $lockedStatement = StatementAccount::query()->lockForUpdate()->findOrFail($statement->id);

            if (! $lockedStatement->isDraft()) {
                throw ValidationException::withMessages([
                    'statement' => 'Only a draft statement can be published.',
                ]);
            }

            $lockedStatement->update([
                'status' => StatementAccount::STATUS_PUBLISHED,
                'issued_at' => now(),
            ]);
        });

        return redirect()->route('dashboard.statements.show', $statement)
            ->with('success', 'Statement '.$statement->reference.' was published to the merchant and is now permanently locked.');
    }

    public function destroy(StatementAccount $statement)
    {
        DB::transaction(function () use ($statement) {
            $lockedStatement = StatementAccount::query()->lockForUpdate()->findOrFail($statement->id);

            if (! $lockedStatement->isDraft()) {
                throw ValidationException::withMessages([
                    'statement' => 'Published statements cannot be deleted or reverted.',
                ]);
            }

            $lockedStatement->delete();
        });

        return redirect()->route('dashboard.statements.index')
            ->with('success', 'The draft statement was deleted. Its orders are available for statement processing again.');
    }
}
