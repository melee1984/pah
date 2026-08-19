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
        $bookings = $this->riders->bookings(); // check order available for rider 

        return response()->json([
            'wallet' => [
                'credits' => $wallet->amount_owed_centavos,
            ],
            'availability' => $this->availabilityData($availability),
            'bookings' => $bookings,
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

    public function saveLocation(Request $request): JsonResponse
    {
        $validated = $this->validateLocation($request);
        $this->insertLocation($this->riders->rider($request)->id, $validated);

        return response()->json([
            'message' => 'Rider location recorded.',
            'recorded_at' => $validated['recorded_at'],
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
