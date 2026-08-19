<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Model\Bookings\Bookings;
use App\Model\Orders\Orders;
use App\Services\RiderApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class DeliveryController extends Controller
{
    private const TERMINAL_STATES = ['delivered', 'cancelled', 'failed'];

    private const EVENT_TYPES = [
        'accepted',
        'going_to_merchant',
        'arrived_at_merchant',
        'order_not_ready',
        'waiting_started',
        'pickup_verified',
        'picked_up',
        'going_to_customer',
        'arrived_at_customer',
        'customer_unreachable_started',
        'customer_unreachable_resolved',
        'cod_collected',
        'customer_verified',
        'proof_captured',
        'delivered',
        'cancelled',
        'failed',
    ];

    public function __construct(private readonly RiderApiService $riders) {}

    public function currentOffer(Request $request): JsonResponse
    {
        $offer = DB::table('rider_api_offers')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        return response()->json([
            'offer' => $offer ? $this->offerData($offer) : null,
        ]);
    }

    public function offer(Request $request, string $offer): JsonResponse
    {
        return response()->json([
            'offer' => $this->offerData($this->ownedOffer($request, $offer)),
        ]);
    }

    public function acceptOffer(Request $request, string $offer): JsonResponse
    {
        $rider = $this->riders->rider($request);

        $delivery = DB::transaction(function () use ($rider, $offer) {
            $record = DB::table('rider_api_offers')
                ->where('rider_id', $rider->id)
                ->where('reference', $offer)
                ->lockForUpdate()
                ->first();
            abort_if(! $record, 404);

            if ($record->status !== 'pending' || now()->greaterThanOrEqualTo($record->expires_at)) {
                abort(409, 'This delivery offer is no longer available.');
            }

            $activeExists = DB::table('rider_api_deliveries')
                ->where('rider_id', $rider->id)
                ->whereNotIn('current_state', self::TERMINAL_STATES)
                ->where('id', '!=', $record->delivery_id)
                ->exists();
            abort_if($activeExists, 409, 'Finish the active delivery before accepting another offer.');

            $delivery = DB::table('rider_api_deliveries')->where('id', $record->delivery_id)->first();
            abort_if(
                $delivery->rider_id && (int) $delivery->rider_id !== (int) $rider->id,
                409,
                'This delivery was accepted by another rider.',
            );
            $wallet = $this->riders->wallet($rider->id);
            if (
                $delivery->cod_centavos > 0
                && $wallet->daily_cod_limit_centavos > 0
                && ($wallet->amount_owed_centavos + $delivery->cod_centavos) > $wallet->daily_cod_limit_centavos
            ) {
                abort(409, 'The COD cash limit must be remitted before accepting this offer.');
            }

            DB::table('rider_api_offers')->where('id', $record->id)->update([
                'status' => 'accepted',
                'responded_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('rider_api_offers')
                ->where('delivery_id', $record->delivery_id)
                ->where('id', '!=', $record->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);
            DB::table('rider_api_delivery_events')->insert([
                'delivery_id' => $record->delivery_id,
                'event_id' => (string) Str::uuid(),
                'type' => 'accepted',
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $delivery->rider_id = $rider->id;
            $this->syncLegacyDelivery($delivery, 'accepted');
            DB::table('rider_api_deliveries')->where('id', $record->delivery_id)->update([
                'rider_id' => $rider->id,
                'current_state' => 'accepted',
                'accepted_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('rider_api_availability')->updateOrInsert(
                ['rider_id' => $rider->id],
                [
                    'state' => 'active_delivery',
                    'heartbeat_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            foreach (['merchant', 'customer'] as $type) {
                $exists = DB::table('rider_api_conversations')
                    ->where('rider_id', $rider->id)
                    ->where('type', $type)
                    ->where('delivery_reference', $delivery->reference)
                    ->exists();
                if (! $exists) {
                    DB::table('rider_api_conversations')->insert([
                        'reference' => (string) Str::uuid(),
                        'rider_id' => $rider->id,
                        'type' => $type,
                        'delivery_reference' => $delivery->reference,
                        'subject' => $type === 'merchant'
                            ? $delivery->merchant_name
                            : 'Delivery customer',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return DB::table('rider_api_deliveries')->where('id', $record->delivery_id)->first();
        });

        return response()->json([
            'message' => 'Delivery offer accepted.',
            'delivery' => $this->deliveryData($delivery),
        ]);
    }

    public function declineOffer(Request $request, string $offer): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $record = $this->ownedOffer($request, $offer);

        if ($record->status !== 'pending') {
            return response()->json(['message' => 'This offer has already been answered.'], 409);
        }

        DB::table('rider_api_offers')->where('id', $record->id)->update([
            'status' => 'declined',
            'decline_reason' => $validated['reason'] ?? null,
            'responded_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Delivery offer declined.']);
    }

    public function active(Request $request): JsonResponse
    {
        $delivery = DB::table('rider_api_deliveries')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->whereNotIn('current_state', self::TERMINAL_STATES)
            ->latest('updated_at')
            ->first();

        return response()->json([
            'delivery' => $delivery ? $this->deliveryData($delivery) : null,
        ]);
    }

    public function show(Request $request, string $delivery): JsonResponse
    {
        $record = $this->ownedDelivery($request, $delivery);

        return response()->json([
            'delivery' => $this->deliveryData($record),
            'timeline' => $this->timeline($record->id),
            'proofs' => $this->proofs($record->id),
        ]);
    }

    public function route(Request $request, string $delivery): JsonResponse
    {
        $record = $this->ownedDelivery($request, $delivery);
        $pickupLeg = in_array($record->current_state, [
            'accepted',
            'going_to_merchant',
            'arrived_at_merchant',
            'order_not_ready',
            'waiting_started',
            'pickup_verified',
        ], true);

        return response()->json([
            'delivery_id' => $record->reference,
            'leg' => $pickupLeg ? 'to_pickup' : 'to_dropoff',
            'origin' => $this->latestRiderCoordinate($record->rider_id),
            'destination' => $pickupLeg ? [
                'latitude' => $record->pickup_latitude,
                'longitude' => $record->pickup_longitude,
                'address' => $record->pickup_address,
            ] : [
                'latitude' => $record->dropoff_latitude,
                'longitude' => $record->dropoff_longitude,
                'address' => $record->dropoff_address,
            ],
        ]);
    }

    public function event(Request $request, string $delivery): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'uuid'],
            'type' => ['required', Rule::in(self::EVENT_TYPES)],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metadata' => ['nullable', 'array'],
        ]);
        $record = $this->ownedDelivery($request, $delivery);
        $existing = DB::table('rider_api_delivery_events')
            ->where('event_id', $validated['event_id'])
            ->first();

        if ($existing) {
            abort_if((int) $existing->delivery_id !== (int) $record->id, 409, 'The event ID belongs to another delivery.');

            return response()->json([
                'message' => 'Delivery event already recorded.',
                'event' => $this->eventData($existing),
                'idempotent_replay' => true,
            ]);
        }

        if (! $this->transitionAllowed($record->current_state, $validated['type'])) {
            return response()->json([
                'message' => "The {$validated['type']} event is not allowed after {$record->current_state}.",
                'current_state' => $record->current_state,
                'allowed_events' => $this->allowedEvents($record->current_state),
            ], 409);
        }

        $event = DB::transaction(function () use ($record, $validated) {
            $eventId = DB::table('rider_api_delivery_events')->insertGetId([
                'delivery_id' => $record->id,
                'event_id' => $validated['event_id'],
                'type' => $validated['type'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'metadata' => isset($validated['metadata'])
                    ? json_encode($validated['metadata'], JSON_THROW_ON_ERROR)
                    : null,
                'occurred_at' => $validated['occurred_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $updates = [
                'current_state' => $validated['type'],
                'updated_at' => now(),
            ];

            if ($validated['type'] === 'delivered') {
                $updates['completed_at'] = $validated['occurred_at'];
                $this->creditDeliveryEarnings($record);
                DB::table('rider_api_availability')
                    ->where('rider_id', $record->rider_id)
                    ->update(['state' => 'available', 'updated_at' => now()]);
            } elseif (in_array($validated['type'], ['cancelled', 'failed'], true)) {
                $updates['completed_at'] = $validated['occurred_at'];
                DB::table('rider_api_availability')
                    ->where('rider_id', $record->rider_id)
                    ->update(['state' => 'available', 'updated_at' => now()]);
            }

            DB::table('rider_api_deliveries')->where('id', $record->id)->update($updates);
            $this->syncLegacyDelivery($record, $validated['type']);

            return DB::table('rider_api_delivery_events')->where('id', $eventId)->first();
        });

        return response()->json([
            'message' => 'Delivery event recorded.',
            'event' => $this->eventData($event),
            'current_state' => $validated['type'],
        ], 201);
    }

    public function verifyPickup(Request $request, string $delivery): JsonResponse
    {
        return $this->verifyDeliveryCode($request, $delivery, 'pickup');
    }

    public function verifyCustomer(Request $request, string $delivery): JsonResponse
    {
        return $this->verifyDeliveryCode($request, $delivery, 'customer');
    }

    public function confirmCod(Request $request, string $delivery): JsonResponse
    {
        $validated = $request->validate([
            'amount_centavos' => ['required', 'integer', 'min:0'],
            'collected_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        $record = $this->ownedDelivery($request, $delivery);

        if ((int) $record->cod_centavos !== (int) $validated['amount_centavos']) {
            return response()->json([
                'message' => 'Collected COD does not match the delivery COD amount.',
                'expected_centavos' => (int) $record->cod_centavos,
            ], 409);
        }

        if (! $this->transitionAllowed($record->current_state, 'cod_collected')) {
            return response()->json(['message' => 'COD cannot be confirmed in the current delivery state.'], 409);
        }

        DB::transaction(function () use ($record, $validated) {
            $wallet = $this->riders->wallet($record->rider_id);
            DB::table('rider_api_wallets')->where('id', $wallet->id)->update([
                'cash_collected_centavos' => $wallet->cash_collected_centavos + $validated['amount_centavos'],
                'amount_owed_centavos' => $wallet->amount_owed_centavos + $validated['amount_centavos'],
                'updated_at' => now(),
            ]);
            DB::table('rider_api_deliveries')->where('id', $record->id)->update([
                'current_state' => 'cod_collected',
                'updated_at' => now(),
            ]);
            DB::table('rider_api_delivery_events')->insert([
                'delivery_id' => $record->id,
                'event_id' => (string) Str::uuid(),
                'type' => 'cod_collected',
                'metadata' => json_encode(['amount_centavos' => $validated['amount_centavos']], JSON_THROW_ON_ERROR),
                'occurred_at' => $validated['collected_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Collected COD confirmed.',
            'amount_centavos' => (int) $validated['amount_centavos'],
        ]);
    }

    public function requestProofUpload(Request $request, string $delivery): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in(['photo', 'pin', 'qr', 'signature'])],
        ]);
        $record = $this->ownedDelivery($request, $delivery);
        $plainToken = Str::random(80);
        $reference = (string) Str::uuid();

        DB::table('rider_api_proof_uploads')->insert([
            'reference' => $reference,
            'delivery_id' => $record->id,
            'upload_token_hash' => hash('sha256', $plainToken),
            'method' => $validated['method'],
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'upload_id' => $reference,
            'upload_token' => $plainToken,
            'upload_url' => url("/api/v1/rider/deliveries/{$record->reference}/proof"),
            'expires_at' => now()->addMinutes(10)->toISOString(),
        ], 201);
    }

    public function attachProof(Request $request, string $delivery): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in(['photo', 'pin', 'qr', 'signature'])],
            'upload_id' => ['nullable', 'uuid'],
            'upload_token' => ['nullable', 'string'],
            'file' => ['required_if:method,photo,signature', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'metadata' => ['nullable', 'array'],
        ]);
        $record = $this->ownedDelivery($request, $delivery);

        if (isset($validated['upload_id'])) {
            $upload = DB::table('rider_api_proof_uploads')
                ->where('reference', $validated['upload_id'])
                ->where('delivery_id', $record->id)
                ->first();
            if (
                ! $upload
                || $upload->used_at
                || now()->greaterThan($upload->expires_at)
                || ! isset($validated['upload_token'])
                || ! hash_equals($upload->upload_token_hash, hash('sha256', $validated['upload_token']))
            ) {
                return response()->json(['message' => 'Proof upload authorization is invalid or expired.'], 422);
            }
        }

        $reference = (string) Str::uuid();
        $path = null;
        if ($request->hasFile('file')) {
            $path = $this->storePrivateFile(
                $request->file('file'),
                "rider-delivery-proofs/{$record->reference}",
            );
        }

        try {
            DB::transaction(function () use ($record, $validated, $reference, $path) {
                DB::table('rider_api_delivery_proofs')->insert([
                    'reference' => $reference,
                    'delivery_id' => $record->id,
                    'method' => $validated['method'],
                    'path' => $path,
                    'metadata' => isset($validated['metadata'])
                        ? json_encode($validated['metadata'], JSON_THROW_ON_ERROR)
                        : null,
                    'processing_status' => 'complete',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (isset($validated['upload_id'])) {
                    DB::table('rider_api_proof_uploads')
                        ->where('reference', $validated['upload_id'])
                        ->update(['used_at' => now(), 'updated_at' => now()]);
                }
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json([
            'message' => 'Proof of delivery attached.',
            'proof' => [
                'id' => $reference,
                'method' => $validated['method'],
                'processing_status' => 'complete',
            ],
        ], 201);
    }

    public function proofStatus(Request $request, string $delivery): JsonResponse
    {
        $record = $this->ownedDelivery($request, $delivery);

        return response()->json(['proofs' => $this->proofs($record->id)]);
    }

    public function issue(Request $request, string $delivery): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:2000'],
        ]);
        $record = $this->ownedDelivery($request, $delivery);
        $reference = (string) Str::uuid();

        DB::table('rider_api_delivery_issues')->insert([
            'reference' => $reference,
            'delivery_id' => $record->id,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Delivery issue reported.',
            'issue' => ['id' => $reference, 'status' => 'open'],
        ], 201);
    }

    public function call(Request $request, string $delivery): JsonResponse
    {
        $validated = $request->validate([
            'party' => ['required', Rule::in(['customer', 'merchant'])],
        ]);
        $record = $this->ownedDelivery($request, $delivery);
        abort_if(in_array($record->current_state, self::TERMINAL_STATES, true), 409, 'Calling is unavailable after delivery completion.');
        $reference = (string) Str::uuid();
        $expiresAt = now()->addMinutes(15);

        DB::table('rider_api_delivery_calls')->insert([
            'reference' => $reference,
            'delivery_id' => $record->id,
            'party' => $validated['party'],
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'call_id' => $reference,
            'relay_number' => config('services.rider_call_relay.number'),
            'expires_at' => $expiresAt->toISOString(),
        ], 201);
    }

    public function shareTrip(Request $request, string $delivery): JsonResponse
    {
        $record = $this->ownedDelivery($request, $delivery);
        abort_if(in_array($record->current_state, self::TERMINAL_STATES, true), 409, 'Trip sharing is unavailable after delivery completion.');
        $plainToken = Str::random(48);
        $reference = (string) Str::uuid();
        $expiresAt = now()->addHours(4);

        DB::table('rider_api_share_links')->insert([
            'reference' => $reference,
            'delivery_id' => $record->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'share_id' => $reference,
            'share_url' => url("/trip/{$plainToken}"),
            'expires_at' => $expiresAt->toISOString(),
        ], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'completed', 'cancelled', 'failed'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $riderId = $this->riders->rider($request)->id;
        $orders = Orders::query()
            ->with(['cart', 'status'])
            ->whereNotNull('store_accepted_at')
            ->where(function ($query) use ($riderId) {
                $query->where(function ($query) use ($riderId) {
                    $query->whereNull('accepted_at')
                        ->where(function ($query) use ($riderId) {
                            $query->whereNull('rider_id')->orWhere('rider_id', $riderId);
                        });
                })->orWhere(function ($query) use ($riderId) {
                    $query->whereNotNull('accepted_at')
                        ->where(function ($query) use ($riderId) {
                            $query->where('rider_id', $riderId)
                                ->orWhere('accepted_by_rider_id', $riderId);
                        });
                });
            });
        $bookings = Bookings::query()
            ->with(['dropoff', 'pickup', 'status'])
            ->where(function ($query) use ($riderId) {
                $query->where('rider_id', $riderId)
                    ->orWhere(function ($query) use ($riderId) {
                        $query->whereNotNull('accepted_at')
                            ->where('accepted_by_rider_id', $riderId);
                    });
            });

        $this->applyLegacyOrderFilters($orders, $bookings, $validated);

        $items = $orders->get()
            ->map(fn (Orders $order) => $this->legacyOrderData($order))
            ->merge($bookings->get()->map(fn (Bookings $booking) => $this->legacyBookingData($booking)))
            ->sortByDesc('sort_date')
            ->take($validated['limit'] ?? 20)
            ->map(function (array $item) {
                unset($item['sort_date']);

                return $item;
            })
            ->values();

        return response()->json([
            'orders' => $items,
            'next_cursor' => null,
        ]);
    }

    private function applyLegacyOrderFilters($orders, $bookings, array $validated): void
    {
        match ($validated['status'] ?? null) {
            'active' => [$orders->whereNotIn('status_id', [7, 8]), $bookings->whereNotIn('status_id', [6, 7])],
            'completed' => [$orders->where('status_id', 7), $bookings->where('status_id', 6)],
            'cancelled' => [$orders->where('status_id', 8), $bookings->where('status_id', 7)],
            'failed' => [$orders->whereRaw('1 = 0'), $bookings->whereRaw('1 = 0')],
            default => null,
        };

        if (isset($validated['date_from'])) {
            $orders->whereDate('submitted_at', '>=', $validated['date_from']);
            $bookings->whereDate('created_at', '>=', $validated['date_from']);
        }
        if (isset($validated['date_to'])) {
            $orders->whereDate('submitted_at', '<=', $validated['date_to']);
            $bookings->whereDate('created_at', '<=', $validated['date_to']);
        }
        if (isset($validated['search'])) {
            $search = $validated['search'];
            $orders->whereHas('cart', fn ($query) => $query->where('order_no', 'like', "%{$search}%"));
            $bookings->where('job_order', 'like', "%{$search}%");
        }
    }

    private function legacyOrderData(Orders $order): array
    {
        $cart = $order->cart;

        if ($cart) {
            $cart->loadMissing(['address', 'payment', 'partnerlocation', 'details.item']);
        }

        return [
            ...$order->toArray(),
            'type' => 'order',
            'option' => 1,
            'job_order_format' => 'JO '.($cart?->order_no ?? $order->id),
            'summary' => $cart?->cartItemSummary(),
            'cart_total' => $cart?->cartItemTotal(),
            'logs' => $order->getActionLogs(),
            'action' => $order->getAction(),
            'submitted_at_' => $order->submitted_at
                ? date('d-m-Y G:ia', strtotime($order->submitted_at))
                : null,
            'formated_submitted_at_' => $order->submitted_at
                ? date('D, d M h:ia', strtotime($order->submitted_at))
                : null,
            'sort_date' => $order->submitted_at ?? $order->created_at,
        ];
    }

    private function legacyBookingData(Bookings $booking): array
    {
        return [
            ...$booking->toArray(),
            'type' => 'booking',
            'option' => 2,
            'job_order_format' => 'JO '.$booking->job_order,
            'created_at_format' => $booking->created_at
                ? date('D, d M h:ia', strtotime($booking->created_at))
                : null,
            'booking_date_and_time_format' => date('F d, Y', strtotime($booking->booking_date)).' @ '.date('h:ia', strtotime($booking->booking_time)),
            'delivery_rate_format' => number_format($booking->delivery_rate, 2),
            'logs' => $booking->getActionLogs(),
            'sort_date' => $booking->created_at,
        ];
    }

    public function order(Request $request, string $order): JsonResponse
    {
        return $this->show($request, $order);
    }

    private function verifyDeliveryCode(Request $request, string $delivery, string $type): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);
        $record = $this->ownedDelivery($request, $delivery);
        $column = $type === 'pickup' ? 'pickup_code_hash' : 'customer_code_hash';
        $event = $type === 'pickup' ? 'pickup_verified' : 'customer_verified';

        if (! $record->{$column}) {
            return response()->json(['message' => ucfirst($type).' verification is not configured for this delivery.'], 409);
        }
        if (! hash_equals($record->{$column}, hash('sha256', $validated['code']))) {
            return response()->json(['message' => 'The verification code is incorrect.'], 422);
        }
        if (! $this->transitionAllowed($record->current_state, $event)) {
            return response()->json(['message' => ucfirst($type).' verification is not allowed in the current state.'], 409);
        }

        DB::transaction(function () use ($record, $event) {
            DB::table('rider_api_deliveries')->where('id', $record->id)->update([
                'current_state' => $event,
                'updated_at' => now(),
            ]);
            DB::table('rider_api_delivery_events')->insert([
                'delivery_id' => $record->id,
                'event_id' => (string) Str::uuid(),
                'type' => $event,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => ucfirst($type).' verification confirmed.']);
    }

    private function ownedOffer(Request $request, string $reference): object
    {
        $offer = DB::table('rider_api_offers')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->where('reference', $reference)
            ->first();
        abort_if(! $offer, 404);

        return $offer;
    }

    private function ownedDelivery(Request $request, string $reference): object
    {
        $delivery = DB::table('rider_api_deliveries')
            ->where('rider_id', $this->riders->rider($request)->id)
            ->where('reference', $reference)
            ->first();
        abort_if(! $delivery, 404);

        return $delivery;
    }

    /**
     * @return array<string, mixed>
     */
    private function offerData(object $offer): array
    {
        $delivery = DB::table('rider_api_deliveries')->where('id', $offer->delivery_id)->first();

        return [
            'id' => $offer->reference,
            'status' => $offer->status,
            'merchant_name' => $delivery->merchant_name,
            'pickup_area' => $delivery->pickup_area,
            'dropoff_area' => $delivery->dropoff_area,
            'distance_meters' => $delivery->distance_meters,
            'eta_seconds' => $delivery->eta_seconds,
            'earnings_centavos' => (int) $delivery->earnings_centavos,
            'cod_centavos' => (int) $delivery->cod_centavos,
            'order_count' => (int) $delivery->order_count,
            'is_batched' => (bool) $delivery->is_batched,
            'expires_at' => $offer->expires_at,
            'server_time' => now()->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deliveryData(object $delivery): array
    {
        return [
            ...$this->deliverySummary($delivery),
            'pickup' => [
                'area' => $delivery->pickup_area,
                'address' => $delivery->pickup_address,
                'latitude' => $delivery->pickup_latitude,
                'longitude' => $delivery->pickup_longitude,
            ],
            'dropoff' => [
                'area' => $delivery->dropoff_area,
                'address' => $delivery->dropoff_address,
                'latitude' => $delivery->dropoff_latitude,
                'longitude' => $delivery->dropoff_longitude,
            ],
            'customer' => [
                'name' => $delivery->customer_name,
                'mobile' => $this->riders->maskPhone($delivery->customer_mobile),
            ],
            'legacy_order_id' => $delivery->legacy_order_id,
            'legacy_booking_id' => $delivery->legacy_booking_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deliverySummary(object $delivery): array
    {
        return [
            'id' => $delivery->reference,
            'state' => $delivery->current_state,
            'merchant_name' => $delivery->merchant_name,
            'pickup_area' => $delivery->pickup_area,
            'dropoff_area' => $delivery->dropoff_area,
            'earnings_centavos' => (int) $delivery->earnings_centavos,
            'cod_centavos' => (int) $delivery->cod_centavos,
            'accepted_at' => $delivery->accepted_at,
            'completed_at' => $delivery->completed_at,
            'created_at' => $delivery->created_at,
        ];
    }

    private function transitionAllowed(string $currentState, string $event): bool
    {
        return in_array($event, $this->allowedEvents($currentState), true);
    }

    /**
     * @return list<string>
     */
    private function allowedEvents(string $state): array
    {
        $normal = match ($state) {
            'offered' => ['accepted'],
            'accepted' => ['going_to_merchant'],
            'going_to_merchant' => ['arrived_at_merchant'],
            'arrived_at_merchant' => ['order_not_ready', 'waiting_started', 'pickup_verified'],
            'order_not_ready' => ['waiting_started', 'pickup_verified'],
            'waiting_started' => ['pickup_verified'],
            'pickup_verified' => ['picked_up'],
            'picked_up' => ['going_to_customer'],
            'going_to_customer' => ['arrived_at_customer'],
            'arrived_at_customer' => [
                'customer_unreachable_started',
                'cod_collected',
                'customer_verified',
                'proof_captured',
            ],
            'customer_unreachable_started' => ['customer_unreachable_resolved'],
            'customer_unreachable_resolved' => ['cod_collected', 'customer_verified'],
            'cod_collected' => ['customer_verified', 'proof_captured'],
            'customer_verified' => ['proof_captured', 'delivered'],
            'proof_captured' => ['delivered'],
            default => [],
        };

        return in_array($state, self::TERMINAL_STATES, true)
            ? []
            : [...$normal, 'cancelled', 'failed'];
    }

    private function creditDeliveryEarnings(object $delivery): void
    {
        $alreadyCredited = DB::table('rider_api_wallet_transactions')
            ->where('related_type', 'delivery')
            ->where('related_reference', $delivery->reference)
            ->where('type', 'earning')
            ->exists();
        if ($alreadyCredited) {
            return;
        }

        $wallet = $this->riders->wallet($delivery->rider_id);
        $newBalance = $wallet->available_centavos + $delivery->earnings_centavos;
        DB::table('rider_api_wallets')->where('id', $wallet->id)->update([
            'available_centavos' => $newBalance,
            'updated_at' => now(),
        ]);
        DB::table('rider_api_wallet_transactions')->insert([
            'reference' => (string) Str::uuid(),
            'rider_id' => $delivery->rider_id,
            'type' => 'earning',
            'amount_centavos' => $delivery->earnings_centavos,
            'balance_after_centavos' => $newBalance,
            'description' => 'Delivery earnings',
            'related_type' => 'delivery',
            'related_reference' => $delivery->reference,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function timeline(int $deliveryId): array
    {
        return DB::table('rider_api_delivery_events')
            ->where('delivery_id', $deliveryId)
            ->orderBy('occurred_at')
            ->get()
            ->map(fn (object $event) => $this->eventData($event))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function eventData(object $event): array
    {
        return [
            'event_id' => $event->event_id,
            'type' => $event->type,
            'occurred_at' => $event->occurred_at,
            'metadata' => $event->metadata
                ? json_decode($event->metadata, true, 512, JSON_THROW_ON_ERROR)
                : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function proofs(int $deliveryId): array
    {
        return DB::table('rider_api_delivery_proofs')
            ->where('delivery_id', $deliveryId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (object $proof) => [
                'id' => $proof->reference,
                'method' => $proof->method,
                'processing_status' => $proof->processing_status,
                'created_at' => $proof->created_at,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestRiderCoordinate(int $riderId): ?array
    {
        $location = DB::table('rider_api_locations')
            ->where('rider_id', $riderId)
            ->latest('recorded_at')
            ->first();

        return $location ? [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'recorded_at' => $location->recorded_at,
        ] : null;
    }

    private function storePrivateFile(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'local');

        if (! $path) {
            throw new RuntimeException('The private delivery file could not be stored.');
        }

        return $path;
    }

    private function syncLegacyDelivery(object $delivery, string $event): void
    {
        if ($delivery->legacy_order_id && Schema::hasTable('order')) {
            $updates = match ($event) {
                'accepted' => [
                    'accepted_by_rider_id' => $delivery->rider_id,
                    'accepted_at' => now(),
                ],
                'picked_up' => ['status_id' => 5],
                'delivered' => ['status_id' => 7, 'delivered_at' => now()],
                default => [],
            };
            if ($updates !== []) {
                DB::table('order')->where('id', $delivery->legacy_order_id)->update($updates);
            }
        }

        if ($delivery->legacy_booking_id && Schema::hasTable('bookings')) {
            $updates = match ($event) {
                'accepted' => [
                    'accepted_by_rider_id' => $delivery->rider_id,
                    'accepted_at' => now(),
                ],
                'picked_up' => ['status_id' => 4],
                'delivered' => ['status_id' => 6],
                default => [],
            };
            if ($updates !== []) {
                DB::table('bookings')->where('id', $delivery->legacy_booking_id)->update($updates);
            }
        }
    }
}
