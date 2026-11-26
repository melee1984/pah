<?php

namespace App\Http\Controllers\Api\Qr;

use App\Category;
use App\Http\Controllers\Controller;
use App\LibraryStatus;
use App\Model\Cart;
use App\Model\CartItem;
use App\Model\Orders\Orders;
use App\PartnerLocationCheckoutOption;
use App\PaymentMethod;
use App\Products;
use App\QrOrderingCode;
use App\QuickPayCheckout;
use App\Services\QrOrderSubmission;
use App\Services\QuickPayPayments;
use App\Services\QrOrderingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class QrOrderingController extends Controller
{
    public function createSession(
        QrOrderingSession $sessions,
        string $uuid,
        int $branchId
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);

        return response()
            ->json([
                'status' => 1,
                'session' => $sessions->issue($code->uuid, (int) $code->partner_location_id),
            ], 201)
            ->header('Cache-Control', 'no-store, private');
    }

    public function menu(Request $request, string $uuid, int $branchId): JsonResponse
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
            'payment_methods' => PaymentMethod::active()->forMerchantQr($partner->id, $request->boolean('quickpay'))->orderBy('id')->get([
                'id',
                'title',
                'description',
                'class',
                'quickpay_channel',
            ])->each(fn (PaymentMethod $method) => $method->setAttribute('provider', $method->isQuickPay() ? 'quickpay' : null)),
        ]);
    }

    public function addItem(
        Request $request,
        string $uuid,
        int $branchId,
        string $sessionId
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);

        if ($code->diningTable && ! $code->diningTable->is_available) {
            return $this->failure('Ordering is paused for this table. Please ask a member of staff.', 409);
        }

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

        $variantResult = app(\App\Services\MenuSelection::class)->select($product, $validated['variants'] ?? []);

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

                if ($cart) {
                    QuickPayCheckout::assertCartUnlocked($cart->id);
                }
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

                $quantity = $validated['quantity'] ?? 1;
                if ((int) $cart->details()->sum('qty') + $quantity > 100) {
                    throw new \DomainException('A QR cart cannot contain more than 100 total items.');
                }

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
                $cartItem->qty = (int) $cartItem->qty + $quantity;
                $cartItem->save();

                return $cart;
            });
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage(), 409);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
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
        ])->header('Cache-Control', 'no-store, private');
    }

    public function orders(
        string $uuid,
        int $branchId,
        string $sessionId
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);
        $cart = $this->findCart($sessionId, $code);
        $orders = $cart
            ? Orders::query()
                ->where('cart_id', $cart->id)
                ->orderByDesc('submitted_at')
                ->get()
                ->map(fn (Orders $order) => $this->orderPayload($order, $cart, $code))
                ->values()
            : collect();

        return response()
            ->json([
                'status' => 1,
                'session_id' => $sessionId,
                'orders' => $orders,
            ])
            ->header('Cache-Control', 'no-store, private');
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
        return DB::transaction(function () use ($code, $sessionId, $cartItem, $validated) {
            $cart = Cart::where('session_id', $sessionId)->lockForUpdate()->first();
            $cartItem = $cartItem->fresh();

            if (! $cart || ! $this->cartMatchesCode($cart, $code) || ! $cartItem || (int) $cartItem->cart_id !== (int) $cart->id) {
                return $this->failure('Cart item not found.', 404);
            }

            QuickPayCheckout::assertCartUnlocked($cart->id);
            if ($cart->processed_at) {
                return $this->failure('A checked-out cart cannot be changed.', 409);
            }

            $otherItemQuantity = (int) $cart->details()->where('id', '!=', $cartItem->id)->sum('qty');
            if ($otherItemQuantity + $validated['quantity'] > 100) {
                return $this->failure('A QR cart cannot contain more than 100 total items.', 422);
            }

            if ($validated['quantity'] === 0) {
                $cartItem->delete();
            } else {
                $cartItem->update(['qty' => $validated['quantity']]);
            }

            return response()->json([
                'status' => 1,
                'message' => 'Cart updated.',
                'data' => $this->cartPayload($cart->fresh(), $code),
            ]);
        }, 3);
    }

    public function checkout(
        Request $request,
        QuickPayPayments $quickPay,
        QrOrderSubmission $submission,
        string $uuid,
        int $branchId,
        string $sessionId
    ): JsonResponse {
        $code = $this->resolveCode($uuid, $branchId);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'payment_method_id' => ['required', 'integer'],
            'expected_total' => ['sometimes', 'integer', 'min:0'],
            'confirm_rounding_discount' => ['sometimes', 'boolean'],
        ]);

        $method = PaymentMethod::active()->forMerchantQr($code->partner_id, $request->boolean('quickpay'))
            ->find($validated['payment_method_id']);
        if (! $method) {
            throw ValidationException::withMessages(['payment_method_id' => 'The selected payment method is unavailable.']);
        }

        try {
            [$cart, $order, $created, $checkout] = DB::transaction(function () use ($code, $sessionId, $validated, $method, $quickPay, $submission) {
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
                        return [$cart, $order, false, QuickPayCheckout::latestForCart($cart->id)];
                    }

                    throw new \DomainException('This cart has already been checked out.');
                }

                $checkout = QuickPayCheckout::latestForCart($cart->id, lock: true);
                if ($checkout && in_array($checkout->status, QuickPayCheckout::ACTIVE, true)) {
                    abort_unless($checkout->payment_method_id === (int) $method->id, 409, 'A QuickPay payment is already pending for this cart.');

                    return [$cart, null, false, $checkout];
                }

                if ($code->dining_table_id) {
                    $table = \App\PartnerLocationTable::whereKey($code->dining_table_id)->lockForUpdate()->first();
                    if (! $table || ! $table->active || ! $table->is_available) {
                        throw new \DomainException('Ordering is paused for this table. Please ask a member of staff.');
                    }
                }

                if (! $cart->details()->exists()) {
                    throw new \DomainException('Add at least one menu item before checkout.');
                }

                if ($cart->hasUnavailableProductsAtLocation()) {
                    throw new \DomainException(
                        'One or more items are no longer available at this branch. Please review your cart.'
                    );
                }

                if ($method->isQuickPay()) {
                    return [$cart, null, false, $quickPay->startGuest($cart, $code, $method, $validated)];
                }

                return [$cart, $submission->place($cart, $validated), true, null];
            }, 3);
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage(), 409);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('Unable to submit the order. Please try again.', 500);
        }

        if ($checkout && (! $order || $checkout->status === 'paid')) {
            return $this->paymentResponse($quickPay->reconcile($checkout), $cart);
        }

        return $this->checkoutResponse($cart, $order, $created);
    }

    public function quickPayStatus(QuickPayPayments $quickPay, string $uuid, int $branchId, string $sessionId): JsonResponse
    {
        $cart = Cart::where('session_id', $sessionId)
            ->where('partner_location_address_id', $branchId)
            ->where('order_source', Cart::ORDER_SOURCE_QR)->first();
        abort_unless($cart && ($checkout = QuickPayCheckout::latestForCart($cart->id)), 404, 'No QuickPay checkout was started.');

        return $this->paymentResponse($quickPay->reconcile($checkout), $cart);
    }

    private function paymentResponse(QuickPayCheckout $checkout, Cart $cart): JsonResponse
    {
        if ($checkout->status === 'paid') {
            $cart->refresh();

            return $this->checkoutResponse($cart, $cart->order, false, $checkout);
        }

        return response()->json(['status' => 1, 'payment' => $checkout->payload()], $checkout->status === 'pending' ? 202 : 200)
            ->header('Cache-Control', 'no-store, private');
    }

    private function checkoutResponse(Cart $cart, Orders $order, bool $created, ?QuickPayCheckout $checkout = null): JsonResponse
    {
        $table = $cart->diningTable;
        $summary = $cart->cartItemSummary();
        if ($checkout) {
            $summary['total'] = number_format($checkout->amount_cents / 100, 2);
            $summary['rounding_discount'] = number_format($checkout->rounding_discount_cents / 100, 2);
        }
        return response()->json([
            'status' => 1,
            'message' => $created ? 'Order submitted successfully.' : 'Order was already submitted.',
            ...($checkout ? ['payment' => $checkout->payload()] : []),
            'order' => [
                'id' => $order->id,
                'customer_name' => $cart->fullname,
                'order_no' => $cart->order_no,
                'submitted_at' => $order->submitted_at,
                'fulfillment_type' => $cart->fulfillment_type,
                'order_source' => $cart->order_source,
                'table' => $table ? [
                    'id' => $table->id,
                    'name' => $table->name,
                ] : null,
                'summary' => $summary,
            ],
        ], $created ? 201 : 200)->header('Cache-Control', 'no-store, private');
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
            || ! $code->partner->qr_ordering_enabled
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

    private function productPayload(Products $product): array
    {
        $originalPrice = (float) $product->getRawOriginal('price');
        if ($product->user->merchant->addup) {
            $originalPrice += (float) $product->price_comm;
        }

        $activePrice = (float) $product->getPrice();
        $promoPrice = $activePrice < $originalPrice ? $activePrice : null;

        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'title' => $product->title,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'price' => number_format($activePrice, 2, '.', ''),
            'original_price' => number_format($originalPrice, 2, '.', ''),
            'promo_price' => $promoPrice === null ? null : number_format($promoPrice, 2, '.', ''),
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
        $checkout = QuickPayCheckout::latestForCart($cart->id);

        return [
            'session_id' => $cart->session_id,
            'checked_out' => (bool) $cart->processed_at,
            'quickpay' => $checkout && in_array($checkout->status, [...QuickPayCheckout::ACTIVE, 'paid'], true) ? $checkout->payload() : null,
            'checkout_total_cents' => (int) round($cart->cartItemTotal() * 100),
            'restaurant_id' => $code->partner_id,
            'branch_id' => $code->partner_location_id,
            'order_source' => Cart::ORDER_SOURCE_QR,
            'table' => $code->diningTable ? [
                'id' => $code->diningTable->id,
                'name' => $code->diningTable->name,
            ] : null,
            'customer' => $cart->processed_at ? [
                'full_name' => $cart->fullname,
                'mobile' => $cart->mobile,
            ] : null,
            'items' => $cart->details->map(function (CartItem $item) {
                $variants = $item->variance_content
                    ? unserialize($item->variance_content, ['allowed_classes' => false])
                    : [];

                return [
                    'id' => $item->id,
                    'product_id' => $item->item_id,
                    'title' => $item->item?->title ?? 'Menu item',
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

    private function orderPayload(Orders $order, Cart $cart, QrOrderingCode $code): array
    {
        $cartPayload = $this->cartPayload($cart, $code);
        $checkout = $cartPayload['quickpay'];
        if ($checkout && $checkout['status'] === 'paid') {
            $cartPayload['summary']['total'] = number_format($checkout['amount_cents'] / 100, 2);
            $cartPayload['summary']['rounding_discount'] = number_format($checkout['rounding_discount_cents'] / 100, 2);
        }
        $statusId = (int) ($order->order_status_id ?: $order->status_id);

        return [
            'id' => $order->id,
            'order_no' => $cart->order_no ?: (string) $order->id,
            'submitted_at' => $order->submitted_at,
            'customer_name' => $cart->fullname,
            'status' => [
                'id' => $statusId,
                'label' => $this->customerOrderStatus($statusId),
                'is_final' => in_array($statusId, [
                    LibraryStatus::STATUS_DELIVERED,
                    LibraryStatus::STATUS_CANCELLED,
                    LibraryStatus::STATUS_COMPLETED,
                ], true),
            ],
            'table' => $cartPayload['table'],
            'payment_status' => $checkout && $checkout['status'] === 'paid' ? 'paid' : null,
            'payment_method' => $cart->payment ? [
                'id' => $cart->payment->id,
                'title' => $cart->payment->title,
            ] : null,
            'items' => $cartPayload['items'],
            'summary' => $cartPayload['summary'],
        ];
    }

    private function customerOrderStatus(int $statusId): string
    {
        return match ($statusId) {
            LibraryStatus::STATUS_ORDER_PLACED => 'Received',
            LibraryStatus::STATUS_ORDER_ACCEPTED => 'Accepted',
            LibraryStatus::STATUS_PROCESSING => 'Preparing',
            LibraryStatus::STATUS_READY_FOR_PICKUP => 'Ready to serve',
            LibraryStatus::STATUS_DELIVERED, LibraryStatus::STATUS_COMPLETED => 'Served',
            LibraryStatus::STATUS_CANCELLED => 'Cancelled',
            default => 'Status unavailable',
        };
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
            'customer' => null,
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
