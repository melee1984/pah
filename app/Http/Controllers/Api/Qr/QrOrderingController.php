<?php

namespace App\Http\Controllers\Api\Qr;

use App\Category;
use App\Events\SendPushNotificationEvent;
use App\Http\Controllers\Controller;
use App\LibraryStatus;
use App\Model\Cart;
use App\Model\CartItem;
use App\Model\Orders\OrderProcess;
use App\Model\Orders\Orders;
use App\PartnerLocationCheckoutOption;
use App\PaymentMethod;
use App\Products;
use App\QrOrderingCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class QrOrderingController extends Controller
{
    public function menu(string $uuid, int $branchId): JsonResponse
    {
        $code = $this->resolveCode($uuid, $branchId);
        $partner = $code->partner;

        $categories = Category::query()
            ->where('user_id', $partner->user_id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'identifier', 'description']);

        $products = Products::query()
            ->where('user_id', $partner->user_id)
            ->availableAtLocation($branchId)
            ->with([
                'user.merchant',
                'variants' => fn ($query) => $query->where('active', true)->orderBy('sorting'),
                'variants.product_details' => fn ($query) => $query->where('active', true),
            ])
            ->orderBy('category_id')
            ->orderBy('title')
            ->get()
            ->map(fn (Products $product) => $this->productPayload($product))
            ->values();

        return response()->json([
            'status' => 1,
            'qr' => [
                'uuid' => $code->uuid,
                'label' => $code->label,
            ],
            'restaurant' => [
                'id' => $partner->id,
                'name' => $partner->restaurant_name,
                'description' => $partner->description,
                'mobile' => $partner->mobile,
                'logo_url' => $partner->image('logo'),
                'banner_url' => $partner->image('banner'),
                'is_open' => (bool) $partner->store_open,
            ],
            'branch' => [
                'id' => $code->location->id,
                'address_1' => $code->location->address_1,
                'address_2' => $code->location->address_2,
                'city' => $code->location->city,
                'mobile' => $code->location->mobile,
            ],
            'table' => $code->diningTable ? [
                'id' => $code->diningTable->id,
                'name' => $code->diningTable->name,
                'capacity' => $code->diningTable->capacity,
            ] : null,
            'categories' => $categories,
            'menu' => $products,
            'payment_methods' => PaymentMethod::active()->orderBy('id')->get([
                'id',
                'title',
                'description',
                'class',
            ]),
        ]);
    }

    public function addItem(
        Request $request,
        string $uuid,
        int $branchId,
        string $sessionId
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);

        if (! $code->partner->store_open) {
            return $this->failure('This restaurant is currently closed.', 409);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'is_not_available' => [
                'sometimes',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (CartItem::resolveUnavailableItemAction($value) === null) {
                        $fail('The selected unavailable-item action is invalid.');
                    }
                },
            ],
            'variants' => ['sometimes', 'array'],
            'variants.*.variant_id' => ['required', 'integer'],
            'variants.*.product_detail_id' => ['required', 'integer'],
        ]);

        $product = Products::query()
            ->whereKey($validated['product_id'])
            ->where('user_id', $code->partner->user_id)
            ->availableAtLocation($branchId)
            ->with([
                'user.merchant',
                'variants' => fn ($query) => $query->where('active', true),
                'variants.product_details' => fn ($query) => $query->where('active', true),
            ])
            ->first();

        if (! $product) {
            return $this->failure('The selected menu item is unavailable.', 422);
        }

        $variantResult = $this->selectedVariants($product, $validated['variants'] ?? []);

        if (isset($variantResult['error'])) {
            return $this->failure($variantResult['error'], 422);
        }

        try {
            $cart = DB::transaction(function () use (
                $code,
                $product,
                $request,
                $sessionId,
                $validated,
                $variantResult
            ) {
                $cart = Cart::query()
                    ->where('session_id', $sessionId)
                    ->lockForUpdate()
                    ->first();

                if ($cart?->processed_at) {
                    throw new \DomainException('This cart has already been checked out. Start a new cart.');
                }

                if ($cart && ($cart->details()->exists()) && (
                    (int) $cart->partner_id !== (int) $code->partner_id
                    || (int) $cart->partner_location_address_id !== (int) $code->partner_location_id
                    || (int) $cart->dining_table_id !== (int) $code->dining_table_id
                )) {
                    throw new \DomainException('This cart belongs to a different QR ordering location.');
                }

                if (! $cart) {
                    $cart = new Cart;
                    $cart->session_id = $sessionId;
                }

                $cart->partner_id = $code->partner_id;
                $cart->partner_location_address_id = $code->partner_location_id;
                $cart->fulfillment_type = Cart::FULFILLMENT_DINE_IN;
                $cart->dining_table_id = $code->dining_table_id;
                $cart->order_source = Cart::ORDER_SOURCE_QR;
                $cart->qr_ordering_code_id = $code->id;
                $cart->delivery_fee = 0;
                $cart->distance_rate = 0;
                $cart->ip_address = $request->ip();
                $cart->active = false;
                $cart->save();

                $serializedVariants = serialize($variantResult['content']);
                $attributes = [
                    'cart_id' => $cart->id,
                    'item_id' => $product->id,
                ];

                if ($product->variants->isNotEmpty()) {
                    $attributes['variance_content'] = $serializedVariants;
                }

                $cartItem = CartItem::firstOrNew($attributes);
                $cartItem->price = $product->getPrice(true);
                $cartItem->variance_content = $serializedVariants;
                $cartItem->variance_total = $variantResult['total'];
                $cartItem->price_comm_total = $product->getPriceComm();
                $cartItem->variance_total_comm_total = $variantResult['commission_total'];
                $cartItem->discount_amount = $product->getDiscountAmount();
                $cartItem->is_not_available = CartItem::resolveUnavailableItemAction(
                    $validated['is_not_available'] ?? CartItem::UNAVAILABLE_ACTION_REMOVE_ITEM
                );
                $cartItem->instruction = $validated['instruction'] ?? null;
                $cartItem->qty = (int) $cartItem->qty + ($validated['quantity'] ?? 1);
                $cartItem->save();

                return $cart;
            });
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage(), 409);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('Unable to update the cart. Please try again.', 500);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Item added to cart.',
            'data' => $this->cartPayload($cart->fresh(), $code),
        ], 201);
    }

    public function summary(
        string $uuid,
        int $branchId,
        string $sessionId
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);
        $cart = $this->findCart($sessionId, $code);

        return response()->json([
            'status' => 1,
            'data' => $cart ? $this->cartPayload($cart, $code) : $this->emptyCartPayload($code),
        ]);
    }

    public function updateItem(
        Request $request,
        string $uuid,
        int $branchId,
        string $sessionId,
        CartItem $cartItem
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);

        if (! $code->partner->store_open) {
            return $this->failure('This restaurant is currently closed.', 409);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $cart = $this->findCart($sessionId, $code);

        if (! $cart || (int) $cartItem->cart_id !== (int) $cart->id) {
            return $this->failure('Cart item not found.', 404);
        }

        if ($cart->processed_at) {
            return $this->failure('A checked-out cart cannot be changed.', 409);
        }

        if ($validated['quantity'] === 0) {
            $cartItem->delete();
        } else {
            $cartItem->update(['qty' => $validated['quantity']]);
        }

        return response()->json([
            'status' => 1,
            'message' => $validated['quantity'] === 0 ? 'Item removed from cart.' : 'Cart updated.',
            'data' => $this->cartPayload($cart->fresh(), $code),
        ]);
    }

    public function checkout(
        Request $request,
        string $uuid,
        int $branchId,
        string $sessionId
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_method', 'id')->where('active', true),
            ],
        ]);

        try {
            [$cart, $order, $created] = DB::transaction(function () use ($code, $sessionId, $validated) {
                $cart = Cart::query()
                    ->where('session_id', $sessionId)
                    ->lockForUpdate()
                    ->first();

                if (! $cart || ! $this->cartMatchesCode($cart, $code)) {
                    throw new \DomainException('Cart not found for this QR ordering location.');
                }

                if ($cart->processed_at) {
                    $order = Orders::query()->where('cart_id', $cart->id)->first();

                    if ($order) {
                        return [$cart, $order, false];
                    }

                    throw new \DomainException('This cart has already been checked out.');
                }

                if (! $cart->details()->exists()) {
                    throw new \DomainException('Add at least one menu item before checkout.');
                }

                if ($cart->hasUnavailableProductsAtLocation()) {
                    throw new \DomainException(
                        'One or more items are no longer available at this branch. Please review your cart.'
                    );
                }

                $cart->fullname = $validated['full_name'];
                $cart->mobile = $validated['mobile'];
                $cart->email = $validated['email'] ?? null;
                $cart->payment_id = $validated['payment_method_id'];
                $cart->processed_at = now();
                $cart->submitted_at = now();
                $cart->delivery_date = now()->format('M d, Y');
                $cart->delivery_time = 'ASAP';
                $cart->fulfillment_type = Cart::FULFILLMENT_DINE_IN;
                $cart->dining_table_id = $code->dining_table_id;
                $cart->order_source = Cart::ORDER_SOURCE_QR;
                $cart->qr_ordering_code_id = $code->id;
                $cart->delivery_fee = 0;
                $cart->distance_rate = 0;
                $cart->order_no = $cart->generateOrderNo();
                $cart->active = true;
                $cart->save();

                $order = Orders::query()->create([
                    'user_id' => null,
                    'cart_id' => $cart->id,
                    'submitted_at' => now(),
                    'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
                    'partner_id' => $cart->partner_id,
                ]);

                OrderProcess::query()->firstOrCreate([
                    'status_id' => LibraryStatus::STATUS_ORDER_PLACED,
                    'order_id' => $order->id,
                ]);

                return [$cart, $order, true];
            });
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage(), 409);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('Unable to submit the order. Please try again.', 500);
        }

        if ($created) {
            event(new SendPushNotificationEvent($order));
        }

        return response()->json([
            'status' => 1,
            'message' => $created ? 'Order submitted successfully.' : 'Order was already submitted.',
            'order' => [
                'id' => $order->id,
                'order_no' => $cart->order_no,
                'submitted_at' => $order->submitted_at,
                'fulfillment_type' => $cart->fulfillment_type,
                'order_source' => $cart->order_source,
                'table' => $code->diningTable ? [
                    'id' => $code->diningTable->id,
                    'name' => $code->diningTable->name,
                ] : null,
                'summary' => $cart->cartItemSummary(),
            ],
        ], $created ? 201 : 200);
    }

    private function resolveCode(string $uuid, int $branchId): QrOrderingCode
    {
        $code = QrOrderingCode::query()
            ->usable()
            ->where('uuid', $uuid)
            ->where('partner_location_id', $branchId)
            ->with(['partner', 'location', 'diningTable'])
            ->first();

        abort_if(
            ! $code
            || ! $code->partner
            || ! $code->partner->active
            || ! $code->location
            || ! $code->location->active
            || (int) $code->location->partner_id !== (int) $code->partner_id
            || ($code->dining_table_id && (
                ! $code->diningTable
                || ! $code->diningTable->active
                || (int) $code->diningTable->partner_location_id !== (int) $code->location->id
            )),
            404,
            'QR ordering code not found or unavailable.'
        );

        abort_unless(
            PartnerLocationCheckoutOption::enabledForLocation(
                $code->partner_location_id,
                Cart::FULFILLMENT_DINE_IN
            ),
            409,
            'Dine-in ordering is not enabled for this branch.'
        );

        return $code;
    }

    private function selectedVariants(Products $product, array $selections): array
    {
        $content = [];
        $total = 0.0;
        $commissionTotal = 0.0;
        $selectedHeaders = [];
        $selectedDetails = [];

        foreach ($selections as $selection) {
            $header = $product->variants->firstWhere('id', (int) $selection['variant_id']);
            $detail = $header?->product_details->firstWhere(
                'id',
                (int) $selection['product_detail_id']
            );

            if (! $header || ! $detail) {
                return ['error' => 'One or more selected variants are unavailable for this item.'];
            }

            if (isset($selectedDetails[$detail->id])) {
                return ['error' => 'The same variant option cannot be selected more than once.'];
            }

            if (! $header->is_multiple && isset($selectedHeaders[$header->id])) {
                return ['error' => "Only one option may be selected for {$header->title}."];
            }

            $selectedHeaders[$header->id] = true;
            $selectedDetails[$detail->id] = true;
            $price = (float) $detail->getPrice();
            $commission = (float) $detail->getPriceComm();
            $content[] = [
                'variant_id' => $header->id,
                'variant_detail_id' => $detail->id,
                'title' => trim($detail->title.' '.$header->title),
                'price' => number_format($price, 2, '.', ''),
            ];
            $total += $price;
            $commissionTotal += $commission;
        }

        $missingRequired = $product->variants
            ->where('is_required', true)
            ->reject(fn ($header) => isset($selectedHeaders[$header->id]))
            ->first();

        if ($missingRequired) {
            return ['error' => "Please select an option for {$missingRequired->title}."];
        }

        usort($content, fn ($left, $right) => [
            $left['variant_id'],
            $left['variant_detail_id'],
        ] <=> [$right['variant_id'], $right['variant_detail_id']]);

        return [
            'content' => $content,
            'total' => $total,
            'commission_total' => $commissionTotal,
        ];
    }

    private function productPayload(Products $product): array
    {
        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'title' => $product->title,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'price' => number_format((float) $product->getPrice(), 2, '.', ''),
            'image_url' => $product->getProductImage(),
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'title' => $variant->title,
                'is_required' => (bool) $variant->is_required,
                'is_multiple' => (bool) $variant->is_multiple,
                'options' => $variant->product_details->map(fn ($detail) => [
                    'id' => $detail->id,
                    'title' => $detail->title,
                    'price' => number_format((float) $detail->getPrice(), 2, '.', ''),
                ])->values(),
            ])->values(),
        ];
    }

    private function findCart(string $sessionId, QrOrderingCode $code): ?Cart
    {
        $cart = Cart::query()->where('session_id', $sessionId)->first();

        return $cart && $this->cartMatchesCode($cart, $code) ? $cart : null;
    }

    private function cartMatchesCode(Cart $cart, QrOrderingCode $code): bool
    {
        return (int) $cart->partner_id === (int) $code->partner_id
            && (int) $cart->partner_location_address_id === (int) $code->partner_location_id
            && (int) $cart->dining_table_id === (int) $code->dining_table_id
            && $cart->fulfillment_type === Cart::FULFILLMENT_DINE_IN
            && $cart->order_source === Cart::ORDER_SOURCE_QR
            && (int) $cart->qr_ordering_code_id === (int) $code->id;
    }

    private function cartPayload(Cart $cart, QrOrderingCode $code): array
    {
        $cart->load(['details.item', 'payment']);

        return [
            'session_id' => $cart->session_id,
            'checked_out' => (bool) $cart->processed_at,
            'restaurant_id' => $code->partner_id,
            'branch_id' => $code->partner_location_id,
            'order_source' => Cart::ORDER_SOURCE_QR,
            'table' => $code->diningTable ? [
                'id' => $code->diningTable->id,
                'name' => $code->diningTable->name,
            ] : null,
            'items' => $cart->details->map(function (CartItem $item) {
                $variants = $item->variance_content
                    ? unserialize($item->variance_content, ['allowed_classes' => false])
                    : [];

                return [
                    'id' => $item->id,
                    'product_id' => $item->item_id,
                    'title' => $item->item?->title,
                    'quantity' => (int) $item->qty,
                    'unit_price' => number_format(
                        (float) $item->price + (float) $item->variance_total,
                        2,
                        '.',
                        ''
                    ),
                    'instruction' => $item->instruction,
                    'is_not_available' => CartItem::UNAVAILABLE_ITEM_ACTIONS[$item->is_not_available]
                        ?? CartItem::UNAVAILABLE_ITEM_ACTIONS[CartItem::UNAVAILABLE_ACTION_REMOVE_ITEM],
                    'variants' => is_array($variants) ? $variants : [],
                ];
            })->values(),
            'summary' => $cart->cartItemSummary(),
        ];
    }

    private function emptyCartPayload(QrOrderingCode $code): array
    {
        return [
            'session_id' => null,
            'checked_out' => false,
            'restaurant_id' => $code->partner_id,
            'branch_id' => $code->partner_location_id,
            'order_source' => Cart::ORDER_SOURCE_QR,
            'table' => $code->diningTable ? [
                'id' => $code->diningTable->id,
                'name' => $code->diningTable->name,
            ] : null,
            'items' => [],
            'summary' => [
                'sub_total' => '0.00',
                'delivery_fee' => '0.00',
                'convenience_fee' => '0.00',
                'vat_amount' => '0.00',
                'discount' => '0.00',
                'fulfillment_discount' => '0.00',
                'total' => '0.00',
                'qty' => 0,
                'total_comm' => '0.00',
                'currency' => '₱',
            ],
        ];
    }

    private function failure(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => $message,
        ], $status);
    }
}
