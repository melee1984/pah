<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Services\RiderApiService;
use App\Services\RiderOfferDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RiderController extends Controller
{
    public function __construct(private readonly RiderApiService $riders) {}

    public function status(Request $request): JsonResponse
    {
        $rider = $this->riders->rider($request)->fresh();

        return response()->json([
            'status' => $this->statusData((bool) $rider->is_active),
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        return $this->changeStatus($request, (bool) $validated['is_active']);
    }

    public function walletBalance(Request $request): JsonResponse
    {
        $wallet = $this->riders->wallet($this->riders->rider($request)->id);
        $balance = $wallet->credit_amount;

        return response()->json([
            'balance' => [
                'credit_amount' => $balance,
                'formatted' => '₱'.number_format($balance / 100, 2),
                'currency' => 'PHP',
            ],
        ]);
    }

    public function todayOverview(Request $request): JsonResponse
    {
        $riderId = $this->riders->rider($request)->id;
        $start = today()->startOfDay();
        $end = today()->endOfDay();

        $totalDeliveries = DB::table('rider_api_deliveries')
            ->where('rider_id', $riderId)
            ->whereBetween('accepted_at', [$start, $end])
            ->count();
        $completedDeliveries = DB::table('rider_api_deliveries')
            ->where('rider_id', $riderId)
            ->where('current_state', 'delivered')
            ->whereBetween('completed_at', [$start, $end])
            ->count();
        $earnings = (int) DB::table('rider_api_wallet_transactions')
            ->where('rider_id', $riderId)
            ->where('type', 'earning')
            ->whereBetween('occurred_at', [$start, $end])
            ->sum('amount_centavos');

        return response()->json([
            'overview' => [
                'date' => today()->toDateString(),
                'total_deliveries' => $totalDeliveries,
                'completed_deliveries' => $completedDeliveries,
                'total_earnings_centavos' => $earnings,
                'total_earnings' => $earnings / 100,
                'formatted_total_earnings' => '₱'.number_format($earnings / 100, 2),
                'currency' => 'PHP',
            ],
        ]);
    }

    public function activityLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', Rule::in(['time_in', 'time_out'])],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = DB::table('rider_api_activity_logs')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id');

        if (isset($validated['date'])) {
            $query->whereDate('recorded_at', $validated['date']);
        }
        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $paginator = $query->cursorPaginate($validated['limit'] ?? 20);

        return response()->json([
            'activity_logs' => collect($paginator->items())->map(fn (object $log) => [
                'id' => (string) $log->id,
                'type' => $log->type,
                'recorded_at' => Carbon::parse($log->recorded_at)->toISOString(),
            ])->values(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
        ]);
    }

    public function recordActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['time_in', 'time_out'])],
        ]);

        return $this->changeStatus($request, $validated['type'] === 'time_in');
    }

    private function changeStatus(Request $request, bool $isActive): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $hasActiveDelivery = DB::table('rider_api_deliveries')
            ->where('rider_id', $rider->id)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->exists();

        if (! $isActive && $hasActiveDelivery) {
            return response()->json([
                'message' => 'The rider cannot go offline while a delivery is active.',
            ], 409);
        }

        $changed = DB::transaction(function () use ($rider, $isActive, $hasActiveDelivery) {
            $current = DB::table('rider')->where('id', $rider->id)->lockForUpdate()->first();

            $this->riders->availability($rider->id);
            DB::table('rider_api_availability')->where('rider_id', $rider->id)->update([
                'state' => $isActive
                    ? ($hasActiveDelivery ? 'active_delivery' : 'available')
                    : 'offline',
                'heartbeat_at' => $isActive ? now() : null,
                'updated_at' => now(),
            ]);

            if ((bool) $current->is_active === $isActive) {
                return false;
            }

            DB::table('rider')->where('id', $rider->id)->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
            DB::table('rider_api_activity_logs')->insert([
                'rider_id' => $rider->id,
                'type' => $isActive ? 'time_in' : 'time_out',
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });

        if ($isActive && ! $hasActiveDelivery) {
            app(RiderOfferDispatcher::class)->dispatchPendingForRider($rider->id);
        }

        return response()->json([
            'message' => $changed
                ? ($isActive ? 'Rider is now online.' : 'Rider is now offline.')
                : 'Rider status is unchanged.',
            'status' => $this->statusData($isActive),
            'activity_recorded' => $changed,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function statusData(bool $isActive): array
    {
        return [
            'is_active' => $isActive,
            'label' => $isActive ? 'online' : 'offline',
            'indicator' => [
                'color' => $isActive ? 'green' : 'gray',
                'hex' => $isActive ? '#22C55E' : '#9CA3AF',
            ],
        ];
    }
}
