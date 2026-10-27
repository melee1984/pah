<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Model\Rider\RiderApiLocation;
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
        $user = $request->user();

        $rider = $this->riders->rider($request);
        $wallet = $this->riders->wallet($rider->id);
        $availability = $this->riders->availability($rider->id);
        // $orders = $this->riders->bookings($rider->id); // check order available for rider

        // foreach($orders as $order) {

		// 	$order->summary = $order->cart->cartItemSummary();
		// 	$order->cart->address;
		// 	$order->cart->payment;
		// 	$order->cart->partnerlocation;
		// 	$product_items = $order->cart->cartItemList();    
		// 	$order->cart_total = $order->cart->cartItemTotal();
			
		// 	foreach($product_items as $list) {
		// 	    $list->variance_content = unserialize($list->variance_content);

		// 	    if ($list->item) {
		// 	        $list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
		// 	    }
		// 	}

		// 	$order->status;  
		// 	$order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
		// 	$order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));

		// 	$order->logs = $order->getActionLogs(); // order logs 
        //     $order->action = $order->getRiderAction();
		// }
		

        return response()->json([
            'wallet' => [
                'credits' => $wallet->credit_amount,
            ],
            'availability' => $this->availabilityData($availability),
            'rider' => $user->rider
        ]);
    }

    public function newBookings(Request $request): JsonResponse
    {   
        $user = $request->user();

        $rider = $this->riders->rider($request);
        $orders = $this->riders->newBookings($rider->id); // check order available for rider

        foreach($orders as $order) {

			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$product_items = $order->cart->cartItemList();    
			$order->cart_total = $order->cart->cartItemTotal();
			
			foreach($product_items as $list) {
			    $list->variance_content = unserialize($list->variance_content);

			    if ($list->item) {
			        $list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
			    }
			}

			$order->status;  
			$order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
			$order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));

			$order->logs = $order->getActionLogs(); // order logs 
            $order->action = $order->getRiderAction();
            $order->rider;
		}

        return response()->json([
            'bookings' => $orders,
        ]);

    }   

    public function bookings(Request $request): JsonResponse
    {   
        $user = $request->user();

        $rider = $this->riders->rider($request);
        $orders = $this->riders->bookings($rider->id); // check order available for rider

        foreach($orders as $order) {

			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$product_items = $order->cart->cartItemList();    
			$order->cart_total = $order->cart->cartItemTotal();
			
			foreach($product_items as $list) {
			    $list->variance_content = unserialize($list->variance_content);

			    if ($list->item) {
			        $list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
			    }
			}

			$order->status;  
			$order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
			$order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));

			$order->logs = $order->getActionLogs(); // order logs 
            $order->action = $order->getRiderAction();
		}

        return response()->json([
            'bookings' => $orders,
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
        $hasActiveDelivery = DB::table('rider_api_deliveries')
            ->where('rider_id', $rider->id)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->exists();

        if ($hasActiveDelivery && $validated['state'] !== 'active_delivery') {
            return response()->json([
                'message' => 'Availability cannot change while a delivery is active.',
            ], 409);
        }

        if (! $hasActiveDelivery && $validated['state'] === 'active_delivery') {
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
        $zoneIds = array_values(array_unique($validated['zone_ids']));

        DB::table('rider_api_availability')->where('rider_id', $rider->id)->update([
            'zone_preferences' => json_encode($zoneIds, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Preferred delivery zones updated.',
            'zone_ids' => $zoneIds,
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $availability = $this->riders->availability($this->riders->rider($request)->id);
        $alerts = [];

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
        RiderApiLocation::create([
            'rider_id' => $riderId,
            'delivery_reference' => $location['delivery_id'] ?? null,
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'accuracy_meters' => $location['accuracy_meters'] ?? null,
            'heading' => $location['heading'] ?? null,
            'speed_mps' => $location['speed_mps'] ?? null,
            'recorded_at' => $this->riders->databaseDateTime($location['recorded_at']),
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
