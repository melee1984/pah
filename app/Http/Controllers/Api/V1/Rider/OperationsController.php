<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Services\RiderApiService;
use App\Services\RiderOfferDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    public function __construct(private readonly RiderApiService $riders) {}

    public function dashboard(Request $request): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $wallet = $this->riders->wallet($rider->id);
        $availability = $this->riders->availability($rider->id);
        $activeDelivery = DB::table('rider_api_deliveries')
            ->where('rider_id', $rider->id)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->latest('updated_at')
            ->first();
        $earningsToday = (int) DB::table('rider_api_wallet_transactions')
            ->where('rider_id', $rider->id)
            ->where('type', 'earning')
            ->whereDate('occurred_at', today())
            ->sum('amount_centavos');

        return response()->json([
            'availability' => $this->availabilityData($availability),
            'shift' => [
                'earnings_centavos' => $earningsToday,
                'completed_deliveries' => DB::table('rider_api_deliveries')
                    ->where('rider_id', $rider->id)
                    ->where('current_state', 'delivered')
                    ->whereDate('completed_at', today())
                    ->count(),
            ],
            'wallet' => [
                'available_centavos' => (int) $wallet->available_centavos,
                'pending_centavos' => (int) $wallet->pending_centavos,
                'cash_collected_centavos' => (int) $wallet->cash_collected_centavos,
                'amount_owed_centavos' => (int) $wallet->amount_owed_centavos,
            ],
            'cod_warning' => (int) $wallet->daily_cod_limit_centavos > 0
                && (int) $wallet->amount_owed_centavos >= (int) $wallet->daily_cod_limit_centavos,
            'active_delivery' => $activeDelivery ? [
                'id' => $activeDelivery->reference,
                'state' => $activeDelivery->current_state,
                'merchant_name' => $activeDelivery->merchant_name,
                'dropoff_area' => $activeDelivery->dropoff_area,
            ] : null,
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        return response()->json([
            'availability' => $this->availabilityData(
                $this->riders->availability($this->riders->rider($request)->id),
            ),
        ]);
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', Rule::in(RiderApiService::AVAILABILITY_STATES)],
        ]);
        $rider = $this->riders->rider($request);
        $activeDelivery = DB::table('rider_api_deliveries')
            ->where('rider_id', $rider->id)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->exists();

        if ($activeDelivery && $validated['state'] !== 'active_delivery') {
            return response()->json([
                'message' => 'Availability cannot change while a delivery is active.',
            ], 409);
        }

        if (! $activeDelivery && $validated['state'] === 'active_delivery') {
            return response()->json([
                'message' => 'The active_delivery state requires an active delivery.',
            ], 409);
        }

        $this->riders->availability($rider->id);
        DB::table('rider_api_availability')->where('rider_id', $rider->id)->update([
            'state' => $validated['state'],
            'heartbeat_at' => now(),
            'updated_at' => now(),
        ]);
        $this->syncOnlineStatus($rider->id, $validated['state'] !== 'offline');
        if ($validated['state'] === 'available') {
            app(RiderOfferDispatcher::class)->dispatchPendingForRider($rider->id);
        }

        return $this->availability($request);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['nullable', Rule::in(RiderApiService::AVAILABILITY_STATES)],
            'battery_percent' => ['nullable', 'integer', 'between:0,100'],
            'network_type' => ['nullable', 'string', 'max:30'],
        ]);
        $rider = $this->riders->rider($request);
        $availability = $this->riders->availability($rider->id);
        $state = $validated['state'] ?? $availability->state;

        DB::table('rider_api_availability')->where('rider_id', $rider->id)->update([
            'state' => $state,
            'heartbeat_at' => now(),
            'updated_at' => now(),
        ]);
        $this->syncOnlineStatus($rider->id, $state !== 'offline');
        if ($state === 'available') {
            app(RiderOfferDispatcher::class)->dispatchPendingForRider($rider->id);
        }

        return response()->json([
            'message' => 'Availability heartbeat recorded.',
            'server_time' => now()->toISOString(),
            'next_heartbeat_seconds' => 30,
        ]);
    }

    public function schedule(Request $request): JsonResponse
    {
        $availability = $this->riders->availability($this->riders->rider($request)->id);

        return response()->json([
            'schedule' => $this->decode($availability->schedule, []),
        ]);
    }

    public function updateSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule' => ['required', 'array', 'max:7'],
            'schedule.*.day' => ['required', Rule::in([
                'monday', 'tuesday', 'wednesday', 'thursday',
                'friday', 'saturday', 'sunday',
            ])],
            'schedule.*.enabled' => ['required', 'boolean'],
            'schedule.*.start' => ['nullable', 'date_format:H:i'],
            'schedule.*.end' => ['nullable', 'date_format:H:i'],
        ]);
        $rider = $this->riders->rider($request);
        $this->riders->availability($rider->id);

        DB::table('rider_api_availability')->where('rider_id', $rider->id)->update([
            'schedule' => json_encode($validated['schedule'], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Availability schedule updated.',
            'schedule' => $validated['schedule'],
        ]);
    }

    public function zones(): JsonResponse
    {
        $zones = DB::table('rider_api_zones')
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (object $zone) => [
                'id' => $zone->reference,
                'name' => $zone->name,
                'boundary' => $this->decode($zone->boundary),
            ]);

        return response()->json(['zones' => $zones]);
    }

    public function updateZonePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_ids' => ['required', 'array'],
            'zone_ids.*' => ['uuid', Rule::exists('rider_api_zones', 'reference')->where('active', true)],
        ]);
        $rider = $this->riders->rider($request);
        $this->riders->availability($rider->id);

        DB::table('rider_api_availability')->where('rider_id', $rider->id)->update([
            'zone_preferences' => json_encode(array_values(array_unique($validated['zone_ids'])), JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Preferred delivery zones updated.',
            'zone_ids' => array_values(array_unique($validated['zone_ids'])),
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $rider = $this->riders->rider($request);
        $wallet = $this->riders->wallet($rider->id);
        $availability = $this->riders->availability($rider->id);
        $alerts = [];

        if (
            $wallet->daily_cod_limit_centavos > 0
            && $wallet->amount_owed_centavos >= $wallet->daily_cod_limit_centavos
        ) {
            $alerts[] = [
                'type' => 'cod_limit',
                'severity' => 'blocking',
                'message' => 'Remit collected cash before accepting more COD deliveries.',
            ];
        }

        if (
            in_array($availability->state, ['available', 'searching', 'active_delivery'], true)
            && $availability->heartbeat_at
            && now()->diffInMinutes($availability->heartbeat_at) >= 5
        ) {
            $alerts[] = [
                'type' => 'connectivity',
                'severity' => 'warning',
                'message' => 'Rider heartbeat is overdue.',
            ];
        }

        return response()->json(['alerts' => $alerts]);
    }

    public function saveLocation(Request $request): JsonResponse
    {
        $validated = $this->validateLocation($request);
        $this->insertLocation($this->riders->rider($request)->id, $validated);

        return response()->json([
            'message' => 'Rider location recorded.',
            'recorded_at' => $validated['recorded_at'],
        ], 202);
    }

    public function saveLocationBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locations' => ['required', 'array', 'between:1,100'],
            'locations.*.delivery_id' => ['nullable', 'uuid'],
            'locations.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'locations.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'locations.*.accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'locations.*.heading' => ['nullable', 'numeric', 'between:0,360'],
            'locations.*.speed_mps' => ['nullable', 'numeric', 'min:0'],
            'locations.*.recorded_at' => ['required', 'date'],
        ]);
        $riderId = $this->riders->rider($request)->id;

        DB::transaction(function () use ($riderId, $validated) {
            foreach ($validated['locations'] as $location) {
                $this->insertLocation($riderId, $location);
            }
        });

        return response()->json([
            'message' => 'Queued rider locations recorded.',
            'accepted_count' => count($validated['locations']),
        ], 202);
    }

    public function locationConfig(): JsonResponse
    {
        return response()->json([
            'foreground_interval_seconds' => 15,
            'background_interval_seconds' => 30,
            'minimum_accuracy_meters' => 50,
            'batch_limit' => 100,
            'background_location_required_while_online' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'delivery_id' => ['nullable', 'uuid'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'speed_mps' => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['required', 'date'],
        ]);
    }

    /**
     * Exact coordinates are intentionally persisted without application logging.
     *
     * @param  array<string, mixed>  $location
     */
    private function insertLocation(int $riderId, array $location): void
    {
        DB::table('rider_api_locations')->insert([
            'rider_id' => $riderId,
            'delivery_reference' => $location['delivery_id'] ?? null,
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'accuracy_meters' => $location['accuracy_meters'] ?? null,
            'heading' => $location['heading'] ?? null,
            'speed_mps' => $location['speed_mps'] ?? null,
            'recorded_at' => $location['recorded_at'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function availabilityData(object $availability): array
    {
        return [
            'state' => $availability->state,
            'heartbeat_at' => $availability->heartbeat_at,
            'schedule' => $this->decode($availability->schedule, []),
            'preferred_zone_ids' => $this->decode($availability->zone_preferences, []),
        ];
    }

    private function decode(?string $json, mixed $default = null): mixed
    {
        return $json ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : $default;
    }

    private function syncOnlineStatus(int $riderId, bool $isActive): void
    {
        $current = (bool) DB::table('rider')->where('id', $riderId)->value('is_active');

        if ($current === $isActive) {
            return;
        }

        DB::transaction(function () use ($riderId, $isActive) {
            DB::table('rider')->where('id', $riderId)->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
            DB::table('rider_api_activity_logs')->insert([
                'rider_id' => $riderId,
                'type' => $isActive ? 'time_in' : 'time_out',
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
