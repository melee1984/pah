<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Rider\Rider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderCommissionReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'rider_id' => ['nullable', 'integer', 'exists:rider,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::createFromFormat(
            'Y-m-d',
            $validated['from'] ?? now()->startOfMonth()->toDateString(),
        )->startOfDay();
        $to = Carbon::createFromFormat(
            'Y-m-d',
            $validated['to'] ?? now()->toDateString(),
        )->endOfDay();
        $selectedRiderId = isset($validated['rider_id']) ? (int) $validated['rider_id'] : null;

        $baseQuery = DB::table('rider_api_wallet_transactions as transactions')
            ->join('rider as riders', 'riders.id', '=', 'transactions.rider_id')
            ->leftJoin('rider_api_deliveries as deliveries', function ($join) {
                $join->on('deliveries.reference', '=', 'transactions.related_reference')
                    ->where('transactions.related_type', '=', 'delivery');
            })
            ->where('transactions.type', 'pahatud_commission')
            ->whereBetween('transactions.occurred_at', [$from, $to])
            ->when($selectedRiderId, fn ($query) => $query->where('transactions.rider_id', $selectedRiderId));

        $totalCentavos = abs((int) (clone $baseQuery)->sum('transactions.amount_centavos'));
        $commissionCount = (clone $baseQuery)->count();
        $commissions = $baseQuery
            ->select([
                'transactions.reference',
                'transactions.rider_id',
                'transactions.amount_centavos',
                'transactions.balance_after_centavos',
                'transactions.occurred_at',
                'riders.name as rider_name',
                'deliveries.reference as delivery_reference',
                'deliveries.legacy_order_id',
                'deliveries.legacy_booking_id',
                'deliveries.earnings_centavos',
                'deliveries.commission_percentage',
            ])
            ->orderByDesc('transactions.occurred_at')
            ->orderByDesc('transactions.id')
            ->paginate(25)
            ->withQueryString();

        $riders = Rider::query()->orderBy('name')->get(['id', 'name']);
        $selectedRider = $selectedRiderId
            ? $riders->firstWhere('id', $selectedRiderId)
            : null;
        $walletBalance = $selectedRiderId
            ? (float) (DB::table('rider_api_wallets')->where('rider_id', $selectedRiderId)->value('credit_amount') ?? 0)
            : null;

        return view('dashboard.pages.report.rider-commissions', [
            'commissions' => $commissions,
            'riders' => $riders,
            'selectedRider' => $selectedRider,
            'selectedRiderId' => $selectedRiderId,
            'from' => $from,
            'to' => $to,
            'metrics' => [
                'total_centavos' => $totalCentavos,
                'count' => $commissionCount,
                'average_centavos' => $commissionCount > 0 ? (int) round($totalCentavos / $commissionCount) : 0,
                'wallet_balance' => $walletBalance,
            ],
        ]);
    }
}
