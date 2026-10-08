<?php

namespace App\Http\Controllers\Api\Mobile\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Session;
use Validator;

use App\Model\Orders\Orders;
use App\Model\Orders\OrderProcess;
use App\Model\Cart;
use App\PartnerLocation;
use Carbon\Carbon;
use App\Products;
use App\PushNotification;
use App\Jobs\SendCustomerOrderAcceptedPush;
use App\Services\MerchantOrderReceipt;
use App\Services\RiderOfferDispatcher;
use App\Services\RiderDispatchStatus;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\LibraryStatus;
use App\Model\Bookings\BookingStatus;

class OrderController extends Controller
{	

	 /** Display Orders by store  */
     public function bookings(Request $request) {

		$user = $request->user();

    	$orders = Orders::wherePartnerId($user->merchant->id)
						->with('rider')
    					->whereNull('store_accepted_at')
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->get();

		foreach($orders as $order) {

			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$order->cart->diningTable;
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

			$order->logs = $order->getActionLogs();

		}
		

    	return response()->json($orders, 200);

    }
    public function getAcceptedBooking(Request $request) {

    	$user = $request->user();

    	$orders = Orders::wherePartnerId($user->merchant->id)
    					->whereNotNull('store_accepted_at')
    					->whereAcceptedByStoreId($user->merchant->id)
    					->whereDate('submitted_at', Carbon::today())
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->get();
						
		foreach($orders as $order) {
			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$order->cart->diningTable;
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
			$order->logs = $order->getActionLogs();
		}
    	return response()->json($orders, 200);
    }

    public function getAcceptedBookingByDate(Request $request) {

    	if ($request->input('day_date')=="") {
    		$day = date('Y-m-d');
    	}
    	else {
    		$day = date('Y-m-d', strtotime($request->input('day_date')));
    	}

    	$user = $request->user();

    	$orders = Orders::wherePartnerId($user->merchant->id)
    					->whereNotNull('store_accepted_at')
    					->whereAcceptedByStoreId($user->merchant->id)
    					->whereDate('submitted_at', $day)
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->get();

		foreach($orders as $order) {
			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$order->cart->diningTable;
			$product_items = $order->cart->cartItemList();    
			$order->cart_total = $order->cart->cartItemTotal();
			foreach($product_items as $list) {
			    $list->variance_content = unserialize($list->variance_content);

			    if ($list->item) {
			        $list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
			    }
			}

			$order->status;  
			$order->submitted_at_ = date("d-m-Y h:ia", strtotime($order->submitted_at));
            $order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));
			$order->logs = $order->getActionLogs();
		}
    	return response()->json($orders, 200);
    }


	public function orders(Request $request, RiderDispatchStatus $dispatchStatus) {
	
		$data = array();	
		$validated = $request->validate([
			'merchant_location_id' => ['nullable', 'integer', 'min:1'],
		]);
		$merchantLocationId = $validated['merchant_location_id'] ?? null;
		
		\Log::info('Fetching Order from which Restaurant: ' . $request->user()->merchant->id);

		$orders = Orders::wherePartnerId($request->user()->merchant->id)
					->when($merchantLocationId, function ($query) use ($merchantLocationId) {
						$query->whereHas('cart', function ($cartQuery) use ($merchantLocationId) {
							$cartQuery->where('partner_location_address_id', $merchantLocationId);
						});
					})
					->with('rider')
					->orderby('submitted_at','desc')
					->get();

					
		
		$dispatchStatuses = $dispatchStatus->forOrders($orders);

		foreach($orders as $order) {
			$order->rider_dispatch = $dispatchStatuses->get($order->id);
			$order->cart->diningTable;

			if (!$order->cart->option_id) {
				$order->summary = $order->cart->cartItemSummary();
				$product_items = $order->cart->cartItemList();    
				$order->cart_total = $order->cart->cartItemTotal();
				foreach($product_items as $list) {
					$list->variance_content = unserialize($list->variance_content);

					if ($list->item) {
						$list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
					}
				}

				$order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
				$order->formated_submitted_at_ = date("D, d M G:ia", strtotime($order->submitted_at));
				$order->cart->address;
				$order->status;
				$order->orderStatus;
				$order->user;
				$order->logs = $order->getActionLogs();
				$order->action = $order->getAction();
			}
		}
		
		$data['orders'] = $orders;

    	return response()->json($data, 200);

	}

    public function show(Orders $order, Request $request)
    {
        $merchant = $request->user()?->merchant;

        abort_unless($merchant, 403, 'Merchant account not found.');

        $order = Orders::query()
            ->with([
                'cart.details.item',
                'cart.partner',
                'partner',
                'orderStatus',
                'status',
            ])
            ->whereKey($order->getKey())
            ->where('partner_id', $merchant->id)
            ->firstOrFail();

        abort_unless($order->cart, 404);

        foreach ($order->cart->details as $detail) {
            $variants = $this->decodeCartItemVariants($detail->variance_content);

            $detail->variance_content = $variants;
            $detail->setAttribute('variants', $variants);
        }

        $order->setAttribute('summary', $order->cart->cartItemSummary());
        $order->setAttribute('cart_total', $order->cart->cartItemTotal());
        $order->setAttribute('action', $order->getAction());

        return response()->json(['order' => $order]);
    }

    private function decodeCartItemVariants(mixed $variants): array
    {
        if (is_array($variants)) {
            return $variants;
        }

        if (! is_string($variants) || $variants === '') {
            return [];
        }

        $decoded = unserialize($variants, ['allowed_classes' => false]);

        return is_array($decoded) ? $decoded : [];
    }

    public function riderOffers(Orders $order, Request $request)
    {
        $merchant = $request->user()?->merchant;
        if (! $merchant) {
            return response()->json(['message' => 'Merchant account not found.'], 403);
        }

        $order = Orders::query()
            ->whereKey($order->getKey())
            ->where('partner_id', $merchant->id)
            ->firstOrFail();

        $delivery = DB::table('rider_api_deliveries')
            ->where('legacy_order_id', $order->id)
            ->first();

        if (! $delivery) {
            return response()->json([
                'order_id' => $order->id,
                'dispatch_status' => $order->store_accepted_at ? 'not_dispatched' : 'awaiting_merchant_acceptance',
                'delivery_id' => null,
                'assigned_rider' => null,
                'offers' => [],
            ]);
        }

        $offers = DB::table('rider_api_offers as offer')
            ->join('rider', 'rider.id', '=', 'offer.rider_id')
            ->leftJoin('rider_api_availability as availability', 'availability.rider_id', '=', 'rider.id')
            ->where('offer.delivery_id', $delivery->id)
            ->orderBy('offer.id')
            ->get([
                'offer.reference', 'offer.rider_id', 'rider.name as rider_name',
                'availability.state as availability_state', 'offer.status',
                'offer.expires_at', 'offer.responded_at', 'offer.created_at',
            ])
            ->map(fn (object $offer, int $index) => [
                'rank' => $index + 1,
                'offer_id' => $offer->reference,
                'rider_id' => (int) $offer->rider_id,
                'rider_name' => $offer->rider_name,
                'availability' => $offer->availability_state,
                'status' => $offer->status === 'pending' && now()->greaterThanOrEqualTo($offer->expires_at)
                    ? 'expired' : $offer->status,
                'expires_at' => $offer->expires_at,
                'responded_at' => $offer->responded_at,
                'offered_at' => $offer->created_at,
            ]);

        $assignedRider = $delivery->rider_id
            ? DB::table('rider')->where('id', $delivery->rider_id)->first(['id', 'name'])
            : null;
        $hasPendingOffer = $offers->contains(fn (array $offer) => $offer['status'] === 'pending');

        return response()->json([
            'order_id' => $order->id,
            'dispatch_status' => $assignedRider ? 'assigned' : ($delivery->current_state !== 'offered'
                ? $delivery->current_state : ($hasPendingOffer ? 'offered' : 'no_active_offers')),
            'delivery_id' => $delivery->reference,
            'assigned_rider' => $assignedRider ? [
                'id' => (int) $assignedRider->id,
                'name' => $assignedRider->name,
            ] : null,
            'offers' => $offers,
        ]);
    }

    public function acceptOrder(Orders $order, Request $request)
    {
        if ($request->input('action') === 'ready-for-pickup') {
            return $this->markOrderReadyForPickup($order, $request);
        }

        if ($request->input('action') === 'complete') {
            return $this->completeOrder($order, $request);
        }

        if ($request->input('action') === 'cancel') {
            return $this->cancelOrder($order, $request);
        }

        $user = $request->user();
        $merchant = $user?->merchant;

        if (! $merchant) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant account not found.',
            ], 403);
        }

        $result = DB::transaction(function () use ($merchant, $order, $user, $request) {
            $order = Orders::query()
                ->whereKey($order->getKey())
                ->where('partner_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Order not found.',
                ], 404)];
            }

            // Check if the order has already been accepted by the store
            // If the order has already been accepted, return a response indicating that the order was already accepted
            // 
            if ($order->store_accepted_at && (int) $order->accepted_by_store_id === (int) $request->store_location_id) {
                return [
                    'order' => $order,
                    'already_accepted' => true,
                ];
            }

            if ($order->store_accepted_at || $order->accepted_by_store_id) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Order has already been accepted.',
                ], 409)];
            }

            if (! $order->submitted_at || (int) $order->order_status_id !== LibraryStatus::STATUS_ORDER_PLACED) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Only pending submitted orders can be accepted.',
                ], 409)];
            }

            $order->accepted_by_store_id = $order->cart->partner_location_address_id; // This should update the accepted_by_store_id to the partner location address id instead of the merchant id
            $order->store_accepted_at = now();
            $requiresDelivery = $order->cart->requiresDelivery();
            $isPickup = $order->cart->fulfillment_type === Cart::FULFILLMENT_PICKUP;
            $usesProcessing = $requiresDelivery || $isPickup;
            $order->booking_status_id = $requiresDelivery
                ? BookingStatus::STATUS_BOOKING_PLACED
                : null;
            $order->order_status_id = $usesProcessing
                ? LibraryStatus::STATUS_PROCESSING
                : LibraryStatus::STATUS_ORDER_ACCEPTED;
            $order->save();

            $acceptedStatuses = $usesProcessing
                ? [LibraryStatus::STATUS_ORDER_ACCEPTED, LibraryStatus::STATUS_PROCESSING]
                : [LibraryStatus::STATUS_ORDER_ACCEPTED];

            foreach ($acceptedStatuses as $statusId) {
                OrderProcess::updateOrCreate([
                    'status_id' => $statusId,
                    'order_id' => $order->id,
                ], [
                    'user_id' => $user->id,
                ]);
            }

            return [
                'order' => $order,
                'already_accepted' => false,
            ];
        });

        if (isset($result['response'])) {
            return $result['response'];
        }

        $riderDeliveryReference = null;
        $pickupCode = null;
        if ($result['order']->cart->requiresDelivery()) {
            try {
                $dispatcher = app(RiderOfferDispatcher::class);
                $riderDeliveryReference = $dispatcher->dispatchOrder($result['order']);
                if ($riderDeliveryReference) {
                    $pickupCode = $dispatcher->pickupCode((int) $result['order']->id);
                }
            } catch (\Throwable $exception) {
                Log::error('Order accepted, but rider dispatch failed.', [
                    'order_id' => $result['order']->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if (! $result['already_accepted']) {
            try {
                SendCustomerOrderAcceptedPush::dispatch((int) $result['order']->id)->afterCommit();
            } catch (\Throwable $exception) {
                Log::warning('Order accepted, but the customer notification could not be queued.', [
                    'order_id' => $result['order']->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => $result['already_accepted']
                ? 'Order was already accepted.'
                : 'Order accepted successfully.',
            'order_id' => $result['order']->id,
            'order_status_id' => $result['order']->order_status_id,
            'store_accepted_at' => $result['order']->store_accepted_at,
            'action' => $result['order']->getAction(),
            'print_job' => app(MerchantOrderReceipt::class)->printJob($result['order']),
            'rider_delivery_id' => $riderDeliveryReference,
            'pickup_code' => $pickupCode,
        ]);
    }

    public function reprintOrder(Orders $order, Request $request)
    {
        $merchant = $request->user()?->merchant;

        if (! $merchant) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant account not found.',
            ], 403);
        }

        $order = Orders::query()
            ->with('cart')
            ->whereKey($order->getKey())
            ->where('partner_id', $merchant->id)
            ->first();

        if (! $order) {
            return response()->json([
                'status' => 0,
                'message' => 'Order not found.',
            ], 404);
        }

        if (! $order->store_accepted_at) {
            return response()->json([
                'status' => 0,
                'message' => 'Only accepted orders can be reprinted.',
            ], 409);
        }

        $storeLocationId = $request->input('store_location_id');
        if ($storeLocationId && (int) $storeLocationId !== (int) $order->cart?->partner_location_address_id) {
            return response()->json([
                'status' => 0,
                'message' => 'Order does not belong to the selected store location.',
            ], 403);
        }

        $printJob = app(MerchantOrderReceipt::class)->printJob($order);
        $printJob['id'] = 'order-'.$order->id.'-reprint';

        return response()->json([
            'status' => 1,
            'message' => 'Receipt ready to reprint.',
            'print_job' => $printJob,
        ]);
    }

    public function markOrderReadyForPickup(Orders $order, Request $request)
    {
        $user = $request->user();
        $merchant = $user?->merchant;

        if (! $merchant) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant account not found.',
            ], 403);
        }

        $result = DB::transaction(function () use ($merchant, $order, $request, $user) {
            $order = Orders::query()
                ->whereKey($order->getKey())
                ->where('partner_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Order not found.',
                ], 404)];
            }

            $requiresDelivery = $order->cart->requiresDelivery();
            $isDineIn = $order->cart->fulfillment_type === Cart::FULFILLMENT_DINE_IN;
            $completedStatus = $isDineIn
                ? LibraryStatus::STATUS_COMPLETED
                : LibraryStatus::STATUS_READY_FOR_PICKUP;

            if ((int) $order->order_status_id === $completedStatus
                && $order->store_accepted_at
                && (int) $order->accepted_by_store_id === (int) $request->store_location_id) {
                if ($isDineIn && ! $order->delivered_at) {
                    $order->delivered_at = now();
                    $order->save();
                }

                return [
                    'order' => $order,
                    'already_ready' => true,
                ];
            }

            $allowedStatuses = $isDineIn
                ? [LibraryStatus::STATUS_ORDER_ACCEPTED, LibraryStatus::STATUS_PROCESSING]
                : [LibraryStatus::STATUS_PROCESSING];

            if (! $order->store_accepted_at
                || (int) $order->accepted_by_store_id !== (int) $request->store_location_id
                || ! in_array((int) $order->order_status_id, $allowedStatuses, true)) {

                \Log::info(['Order not ready for pickup' => [
                    'order_id' => $order->id,
                    'store_accepted_at' => $order->store_accepted_at,
                    'accepted_by_store_id' => $order->accepted_by_store_id,
                    'request_store_location_id' => $request->store_location_id,
                    'status_id' => $order->status_id,
                ]]);

                return ['response' => response()->json([
                    'status' => 0,
                    'message' => $isDineIn
                        ? 'Only accepted orders can be completed.'
                        : 'Only processing orders can be marked ready for pickup.',
                ], 409)];
            }

            $order->order_status_id = $completedStatus;
            $order->booking_status_id = $requiresDelivery
                ? BookingStatus::STATUS_BOOKING_PLACED
                : null;
            if ($isDineIn) {
                $order->delivered_at = $order->delivered_at ?: now();
            }
            $order->save();

            OrderProcess::updateOrCreate([
                'status_id' => $completedStatus,
                'order_id' => $order->id,
            ], [
                'user_id' => $user->id,
            ]);

            return [
                'order' => $order,
                'already_ready' => false,
                'is_dine_in' => $isDineIn,
            ];
        });

        if (isset($result['response'])) {
            return $result['response'];
        }

        return response()->json([
            'status' => 1,
            'message' => $result['already_ready']
                ? ($result['order']->cart->fulfillment_type === Cart::FULFILLMENT_DINE_IN
                    ? 'Order was already completed.'
                    : 'Order was already ready for pickup.')
                : ($result['is_dine_in']
                    ? 'Order completed successfully.'
                    : 'Order is ready for pickup.'),
            'order_id' => $result['order']->id,
            'order_status_id' => $result['order']->order_status_id,
            'action' => $result['order']->getAction(),
        ]);
    }

    public function completeOrder(Orders $order, Request $request)
    {
        $user = $request->user();
        $merchant = $user?->merchant;

        if (! $merchant) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant account not found.',
            ], 403);
        }

        $result = DB::transaction(function () use ($merchant, $order, $request, $user) {
            $order = Orders::query()
                ->whereKey($order->getKey())
                ->where('partner_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Order not found.',
                ], 404)];
            }

            if ($order->cart->fulfillment_type !== Cart::FULFILLMENT_PICKUP) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Only pickup orders can be completed by the merchant.',
                ], 409)];
            }

            if ((int) $order->order_status_id === LibraryStatus::STATUS_COMPLETED
                && $order->store_accepted_at
                && (int) $order->accepted_by_store_id === (int) $request->store_location_id) {
                if (! $order->delivered_at) {
                    $order->delivered_at = now();
                    $order->save();
                }

                return [
                    'order' => $order,
                    'already_completed' => true,
                ];
            }

            if (! $order->store_accepted_at
                || (int) $order->accepted_by_store_id !== (int) $request->store_location_id
                || (int) $order->order_status_id !== LibraryStatus::STATUS_READY_FOR_PICKUP) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Only pickup orders that are ready for pickup can be completed.',
                ], 409)];
            }

            $order->order_status_id = LibraryStatus::STATUS_COMPLETED;
            $order->booking_status_id = null;
            $order->delivered_at = $order->delivered_at ?: now();
            $order->save();

            OrderProcess::updateOrCreate([
                'status_id' => LibraryStatus::STATUS_COMPLETED,
                'order_id' => $order->id,
            ], [
                'user_id' => $user->id,
            ]);

            return [
                'order' => $order,
                'already_completed' => false,
            ];
        });

        if (isset($result['response'])) {
            return $result['response'];
        }

        return response()->json([
            'status' => 1,
            'message' => $result['already_completed']
                ? 'Order was already completed.'
                : 'Order completed successfully.',
            'order_id' => $result['order']->id,
            'order_status_id' => $result['order']->order_status_id,
            'action' => $result['order']->getAction(),
        ]);
    }

    public function cancelOrder(Orders $order, Request $request)
    {
        $user = $request->user();
        $merchant = $user?->merchant;

        if (! $merchant) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant account not found.',
            ], 403);
        }

        $result = DB::transaction(function () use ($merchant, $order, $user) {
            $order = Orders::query()
                ->whereKey($order->getKey())
                ->where('partner_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Order not found.',
                ], 404)];
            }

            if ((int) $order->order_status_id === LibraryStatus::STATUS_CANCELLED) {
                return [
                    'order' => $order,
                    'already_cancelled' => true,
                ];
            }

            if (! in_array((int) $order->order_status_id, [
                LibraryStatus::STATUS_ORDER_PLACED,
                LibraryStatus::STATUS_ORDER_ACCEPTED,
                LibraryStatus::STATUS_PROCESSING,
                LibraryStatus::STATUS_READY_FOR_PICKUP,
            ], true)) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Orders already picked up or completed cannot be cancelled.',
                ], 409)];
            }

            $order->order_status_id = LibraryStatus::STATUS_CANCELLED;
            $order->booking_status_id = $order->cart->requiresDelivery()
                ? BookingStatus::STATUS_BOOKING_CANCELLED
                : null;
            $order->save();

            OrderProcess::updateOrCreate([
                'status_id' => LibraryStatus::STATUS_CANCELLED,
                'order_id' => $order->id,
            ], [
                'user_id' => $user->id,
            ]);

            return [
                'order' => $order,
                'already_cancelled' => false,
            ];
        });

        if (isset($result['response'])) {
            return $result['response'];
        }

        if (! $result['already_cancelled']) {
            app(RiderOfferDispatcher::class)->cancelOrder((int) $result['order']->id);
            try {
                PushNotification::sendPushOrder($result['order']);
            } catch (\Throwable $exception) {
                Log::warning('Order cancelled, but the customer notification failed.', [
                    'order_id' => $result['order']->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => $result['already_cancelled']
                ? 'Order was already cancelled.'
                : 'Order cancelled successfully.',
            'order_id' => $result['order']->id,
            'order_status_id' => $result['order']->order_status_id,
            'action' => $result['order']->getAction(),
        ]);
    }

    public function acceptBooking(Orders $order, $action, Request $request) {
		
		$data = array();

		if ($order) {

			$user = $request->user();

			if ($action == "accept") {

				$order->accepted_by_store_id = $user->merchant->id;
				$order->store_accepted_at = now();
				$order->order_status_id = LibraryStatus::STATUS_ORDER_ACCEPTED;
				$status = $order->save();

				if ($status) {

					OrderProcess::updateOrCreate([
	                    'status_id' => LibraryStatus::STATUS_ORDER_ACCEPTED, // item pickup  
	                    'order_id' => $order->id,
	                    'user_id' => $user->accepted_by_store_id,
	                ]);

					OrderProcess::updateOrCreate([
	                    'status_id' => LibraryStatus::STATUS_PROCESSING, // Processing   
	                    'order_id' => $order->id,
	                    'user_id' => $user->accepted_by_store_id,
	                ]);

	                PushNotification::sendPushOrder($order);

				}

				$data['status'] = 1;


			} else if ($action == "readyforpickup") {

				$order->order_status_id = LibraryStatus::STATUS_READY_FOR_PICKUP;
				$order->save();

				OrderProcess::updateOrCreate([
                    'status_id' => LibraryStatus::STATUS_READY_FOR_PICKUP, // item pickup  
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);

                // PushNotification::sendPushOrder($order);

                $data['status'] = 1;

			}
		}
		else {

			$data['status'] = 0;
			$data['message'] = "Unable to find booking. Please try again";
		}

        return response()->json($data, 200);

    }

    public function saveTokenDeviceStore(Request $request)
    {
        $request->merge(['device_token_store' => $request->input('token')]);

        return $this->savePartnerDeviceToken($request);
    }

    public function savePartnerDeviceToken(Request $request)
    {
        $validated = $request->validate([
            'merchant_location_id' => ['required', 'integer', 'min:1'],
            'device_token_store' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! $user?->merchant) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant account not found.',
            ], 403);
        }

        $location = PartnerLocation::query()
            ->where('partner_id', $user->merchant->id)
            ->find($validated['merchant_location_id']);

        if (! $location) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant location not found.',
            ], 404);
        }

        $location->device_token = $validated['device_token_store'];
        $location->save();

        return response()->json([
            'status' => 1,
            'message' => 'Merchant location device token saved successfully.',
            'location_id' => $location->id,
        ]);
    }

    public function updateDeviceId(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->device_id = $validated['device_id'];
        $user->save();

        return response()->json([
            'status' => 1,
            'message' => 'Device ID updated successfully.',
            'device_id' => $user->device_id,
        ]);
    }

    public function updateLocationDeviceToken(PartnerLocation $partnerLocation, Request $request)
    {
		$merchant = $request->user()->merchant;

        if (! $merchant || (int) $partnerLocation->partner_id !== (int) $merchant->id) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant location not found.',
            ], 404);
        }

        $validated = $request->validate([
            'device_token' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_with:longtitude', 'numeric', 'between:-90,90'],
            'longtitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);

        if ($request->filled('device_token')) {
            $partnerLocation->device_token = $validated['device_token'];
        }

        if ($request->filled('latitude') && $request->filled('longtitude')) {
            $partnerLocation->latitude = $validated['latitude'];
            $partnerLocation->longtitude = $validated['longtitude'];
        }

        $partnerLocation->save();

        return response()->json([
            'status' => 1,
            'message' => 'Merchant location device token updated successfully.',
            'location_id' => $partnerLocation->id,
            'device_token' => $partnerLocation->device_token,
        ]);
    }

	public function toggleStoreOnline(Request $request)
	{
		$merchant = Auth::user()->merchant;

		\Log::info('Toggle store online request for merchant ID: ' . $merchant->id, ['current_status' => $merchant->store_open]);

		if (! $merchant) {
			return response()->json([
				'status' => 0,
				'message' => 'Merchant not found.',
			], 404);
		}

		$merchant->store_open = ! (bool) $merchant->store_open;
    	$merchant->save();
		$merchant->refresh();

		return response()->json([
			'status' => 1,
			'message' => $merchant->store_open
				? 'Store is now online.'
				: 'Store is now offline.',
			'store_online' => $merchant->store_open,
		]);
	}

	public function products(Request $request) 
	{	
		$user = $request->user();
		$merchant = $user->merchant;

		$merchant->load(['products' => function ($query) {
			$query->with('category');
		}]);
		
		$products = $merchant->products->map(function ($product) {

			$product->variance_content = unserialize($product->variance_content);
			$product->price = number_format($product->getPrice(), 2);
			$product->image_url = $product->getProductImage();

			return $product;
		});

		return response()->json([
			'status' => 1,
			'products' => $products,
			'categories' => $user->categories()->get(),
		]);

	}	

	public function updateProductStatus(Request $request) 
	{
		$merchant = Auth::user()->merchant;

		$productId = $request->input('product_id');
		$newStatus = $request->input('status');

		if (! $productId || ! in_array($newStatus, [0, 1])) {
			return response()->json([
				'status' => 0,
				'message' => 'Invalid product ID or status.',
			], 400);
		}

		$product = $merchant->products()->find($productId);

		if (! $product) {
			return response()->json([
				'status' => 0,
				'message' => 'Product not found.',
			], 404);
		}

		$product->active = $newStatus;
		$product->save();

		return response()->json([
			'status' => 1,
			'message' => 'Product status updated successfully.',
			'product' => $product,
		]);

	}

}
