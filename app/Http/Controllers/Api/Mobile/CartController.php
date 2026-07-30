<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\Model\CartItem;
use App\Partners;
use App\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;
use Validator;

class CartController extends Controller
{
    /**
     * Add cart Item
     * return success response
     */
    public function addCart(Request $request, $action = false)
    {
        // Sample request:
        // {
        //     "restaurant_id": 13,
        //     "partner_id": 13,
        //     "product_id": 15,
        //     "quantity": 1,
        //     "is_not_available": 0,
        //     "latitude": 37.4219983,
        //     "longitude": -122.084,
        //     "variants": [
        //         {"variant_id": 43, "product_detail_id": 31},
        //         {"variant_id": 44, "product_detail_id": 33},
        //         {"variant_id": 253, "product_detail_id": 696},
        //         {"variant_id": 253, "product_detail_id": 697},
        //         {"variant_id": 254, "product_detail_id": 699}
        //     ]
        // }

        if ($request->action === 'new') {
            $action = 'new';
        }
        
        if ($request->has('is_not_available')) {
            $unavailableItemAction = CartItem::resolveUnavailableItemAction(
                $request->input('is_not_available')
            );

            if ($unavailableItemAction === null) {
                return response()->json([
                    'status' => 0,
                    'message' => 'The is not available field must be remove_item, call_me, replace_similar, 0, 1, or 2.',
                    'errors' => [
                        'is_not_available' => [
                            'The selected is not available value is invalid.',
                        ],
                    ],
                ], 200);
            }

            $request->merge([
                'is_not_available' => $unavailableItemAction,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'session_id' => ['nullable', 'string', 'max:255'],
            'restaurant_id' => ['nullable', 'integer'],
            'partner_id' => ['nullable', 'integer'],
            'product_id' => ['required', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'is_not_available' => [
                'sometimes',
                'integer',
                Rule::in(array_keys(CartItem::UNAVAILABLE_ITEM_ACTIONS)),
            ],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'variants' => ['sometimes', 'array'],
            'variants.*.variant_id' => ['required', 'integer'],
            'variants.*.product_detail_id' => ['required', 'integer'],
            'instruction' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 200);
        }

        $item = Products::with(['user.merchant', 'variants.product_details'])
            ->find($request->integer('product_id'));

        if (! $item || ! $item->user || ! $item->user->merchant) {
            return response()->json([
                'status' => 0,
                'message' => 'Item not found',
            ], 200);
        }

        $partnerId = (int) $item->user->merchant->id;

        foreach (['partner_id', 'restaurant_id'] as $partnerField) {
            if ($request->filled($partnerField) && $request->integer($partnerField) !== $partnerId) {
                return response()->json([
                    'status' => 0,
                    'message' => 'The selected product does not belong to this restaurant.',
                ], 200);
            }
        }

        $variantContent = [];
        $variantTotal = 0;
        $variantCommissionTotal = 0;
        $selectedSingleVariants = [];

        foreach ($request->input('variants', []) as $selectedVariant) {
            $variantHeader = $item->variants->firstWhere('id', (int) $selectedVariant['variant_id']);
            $variantDetail = $variantHeader?->product_details
                ->firstWhere('id', (int) $selectedVariant['product_detail_id']);

            if (! $variantHeader || ! $variantDetail) {
                return response()->json([
                    'status' => 0,
                    'message' => 'One or more selected variants do not belong to this product.',
                ], 200);
            }

            if (! $variantHeader->is_multiple && isset($selectedSingleVariants[$variantHeader->id])) {
                return response()->json([
                    'status' => 0,
                    'message' => "Only one option may be selected for {$variantHeader->title}.",
                ], 200);
            }

            $selectedSingleVariants[$variantHeader->id] = true;
            $variantContent[] = [
                'variant_id' => $variantHeader->id,
                'variant_detail_id' => $variantDetail->id,
                'title' => $variantDetail->title.' '.$variantHeader->title,
                'price' => $variantDetail->getPrice(),
            ];
            $variantTotal += (float) $variantDetail->getPrice();
            $variantCommissionTotal += (float) $variantDetail->getPriceComm();
        }

        usort($variantContent, function ($left, $right) {
            return [$left['variant_id'], $left['variant_detail_id']]
                <=> [$right['variant_id'], $right['variant_detail_id']];
        });

        $user = $request->user('api') ?: $request->user();
        $sessionId = $request->input('session_id');

        if (! $sessionId && $user) {
            $sessionId = Cart::where('user_id', $user->id)
                ->whereNull('processed_at')
                ->latest('created_at')
                ->value('session_id');
        }

        $sessionId = $sessionId ?: (string) Str::uuid();

        if (Cart::where('session_id', $sessionId)->whereNotNull('processed_at')->exists()) {
            $sessionId = (string) Str::uuid();
        }

        $quantity = $request->integer('quantity', 1);

        try {
            $cart = DB::transaction(function () use (
                $action,
                $item,
                $partnerId,
                $quantity,
                $request,
                $sessionId,
                $user,
                $variantContent,
                $variantCommissionTotal,
                $variantTotal
            ) {
                $cart = Cart::where('session_id', $sessionId)
                    ->latest('created_at')
                    ->lockForUpdate()
                    ->first();

                if ($cart
                    && $cart->partner_id
                    && (int) $cart->partner_id !== $partnerId
                    && $cart->details()->exists()
                    && $action !== 'new') {
                    throw new \DomainException('Adding an item from another restaurant requires a new cart.');
                }

                if (! $cart) {
                    $cart = new Cart;
                    $cart->session_id = $sessionId;
                }   

                if ($action === 'new' && $cart->exists) {
                    $cart->details()->delete();
                    $cart->delivery_fee = 0;
                    $cart->distance_rate = 0;
                    $cart->duration = 0;
                    $cart->origin = null;
                    $cart->destination = null;
                }

                if ((blank($cart->user_long) || blank($cart->user_lat))
                    && (! $request->filled('longitude') || ! $request->filled('latitude'))) {
                    throw new \InvalidArgumentException("Sorry, you haven't pinned your current location.");
                }

                if ($request->filled('longitude') && $request->filled('latitude')) {
                    $cart->user_long = $request->input('longitude');
                    $cart->user_lat = $request->input('latitude');
                }

                Log::info('CartController@addCart: Saving cart', [
                    'session_id' => $cart->session_id,
                    'user_id' => $user?->id,
                    'partner_id' => $partnerId,
                    'user_long' => $cart->user_long,
                    'user_lat' => $cart->user_lat,
                ]);

                $cart->ip_address = $request->ip();
                $cart->partner_id = $partnerId;
                // makuha na man kung kinsa ang iyaha location but possible na multiple location sya. need to recheck that. 
                $cart->partner_location_address_id = $cart->partner->location->id ?? null; 
                $cart->active = $user ? 1 : 0;

                if ($user) {
                    $cart->user_id = $user->id;
                    $cart->fullname = trim($user->firstname.' '.$user->lastname);
                    $cart->mobile = $user->mobile;
                    $cart->email = $user->email;
                }

                $cart->save();

                $serializedVariants = serialize($variantContent);
                $cartItemAttributes = [
                    'cart_id' => $cart->id,
                    'item_id' => $item->id,
                ];

                if ($item->variants->isNotEmpty()) {
                    $cartItemAttributes['variance_content'] = $serializedVariants;
                }

                $cartItem = CartItem::firstOrNew($cartItemAttributes);

                $cartItem->price = $item->getPrice(true);
                $cartItem->variance_content = $serializedVariants;
                $cartItem->variance_total = $variantTotal;
                $cartItem->is_not_available = $request->integer(
                    'is_not_available',
                    CartItem::UNAVAILABLE_ACTION_REMOVE_ITEM
                );
                $cartItem->instruction = $request->input('instruction');
                $cartItem->price_comm_total = $item->getPriceComm();
                $cartItem->variance_total_comm_total = $variantCommissionTotal;
                $cartItem->discount_amount = $item->getDiscountAmount();
                $cartItem->qty = (int) $cartItem->qty + $quantity;
                
                $cartItem->save();

                return $cart;
            });
        } catch (\DomainException $exception) {
            return response()->json([
                'status' => 0,
                'message' => $exception->getMessage(),
                'requires_new_cart' => true,
            ], 200);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'status' => 0,
                'message' => $exception->getMessage(),
                'pop' => 'map',
            ], 200);
        } catch (Throwable $exception) {
            \Log::error('Unable to add mobile cart item.', [
                'exception' => $exception,
                'product_id' => $item->id,
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Unable to update the cart. Please try again.',
            ], 200);
        }

        if (! $cart->delivery_fee) {
            try {
                // Calculate the delivery fee for the cart
                // This will update the cart's delivery_fee, distance_rate, and duration fields
                $cart->deliveryRate();
                // 
            } catch (Throwable $exception) {
                \Log::warning('Unable to calculate the mobile cart delivery fee.', [
                    'exception' => $exception,
                    'cart_id' => $cart->id,
                ]);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => 'Successfully updated cart',
            'session_id' => $sessionId,
            'data' => $this->cart($sessionId),
        ], 200);
    }

    public function getCart(Request $request)
    {

        $data['data'] = $this->cart($request->input('session_id'));

        return response()->json($data, 200);

    }

    public function cart($session_id)
    {

        $data = [];
        $product_items = [];
        $summary = [];

        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart) {

            $cart->partnerlocation;

            $product_items = $cart->cartItemList();
            // Just to clear delivery fee
            //
            if (count($product_items) <= 0) {
                // Update Delivery Fee equal to zero to balance
                $cart->delivery_fee = 0;
                $cart->save();
            }

            $summary = $cart->cartItemSummary();

            // foreach($summary $l) {
            //     $list->
            // }

            if ($cart->partner) {
                $cart->partner->photo = Partners::imgBanner($cart->partner, false);
            }

            try {
                // I have created new fucntion name Cart:: cartItemSummary to unserialize the variance content
                // I will just put on hold while i am doing the others

                foreach ($product_items as $list) {
                    //
                    $list->variance_content = unserialize($list->variance_content);
                    //
                    // Added this line to display the total price + the variance total
                    //
                    $list->price = number_format((float) $list->price + (float) $list->variance_total, 2);

                    if ($list->item) {
                        // I can call this because I am using hasOne
                        $list->item->price = $list->item->getPrice();

                        if ($list->item->img) {
                            $list->item->imgPhoto = Partners::imageResizeThumb($list->item, $list->item->id);
                        }
                    }
                }

            } catch (Throwable $e) {
                // Error::log($e);
            }
        }

        $data['cart'] = $cart;
        $data['summary'] = $summary;

        return $data;
    }

    public function modifyCartItem(Request $request, CartItem $cartItem, $status)
    {

        $data = [];
        switch ($status) {
            case 'add':
                $cartItem->qty = $cartItem->qty + 1;
                $cartItem->save();
                $data['status'] = 1;
                break;
            case 'minus':

                $cartItem->qty = $cartItem->qty - 1;
                $cartItem->save();
                $data['status'] = 1;
                break;
            case 'delete':
                $cartItem->delete();
                $data['status'] = 1;
                break;
            default:
                // code...
                break;
        }

        $data['data'] = $this->cart($request->input('session_id'));

        return response()->json($data, 200);

    }
}
