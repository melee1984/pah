<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\Model\User\UserAddress;
use App\PartnerPromotion;
use App\Partners;
use App\PartnerTopPick;
use App\Services\RestaurantService;
use App\Services\ReverseGeocodingService;
use App\Support\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ResourcesController extends Controller
{
    public function __construct(private readonly ReverseGeocodingService $reverseGeocoder) {}

    public function getCategories()
    {
        return response()->json([
            [
                'id' => 'All',
                'label' => 'All',
                'icon_key' => 'grid_view',
            ],
            [
                'id' => 'Pizza',
                'label' => 'Pizza',
                'icon_key' => 'pizza',
            ],
            [
                'id' => 'Burgers',
                'label' => 'Burgers',
                'icon_key' => 'burger',
            ],
            [
                'id' => 'Healthy',
                'label' => 'Healthy',
                'icon_key' => 'healthy',
            ],
            [
                'id' => 'Filipino',
                'label' => 'Filipino',
                'icon_key' => 'filipino',
            ],
            [
                'id' => 'Drinks',
                'label' => 'Drinks',
                'icon_key' => 'drinks',
            ],
            [
                'id' => 'More',
                'label' => 'More',
                'icon_key' => 'more',
            ],
        ]);
    }

    public function getCuisines()
    {
        return response()->json([
            [
                'id' => 'filipino',
                'label' => 'Filipino',
                'icon_key' => 'filipino',
            ],
            [
                'id' => 'asian',
                'label' => 'Asian',
                'icon_key' => 'asian',
            ],
            [
                'id' => 'american',
                'label' => 'American',
                'icon_key' => 'american',
            ],
            [
                'id' => 'italian',
                'label' => 'Italian',
                'icon_key' => 'italian',
            ],
            [
                'id' => 'desserts',
                'label' => 'Desserts',
                'icon_key' => 'desserts',
            ],
            [
                'id' => 'coffee',
                'label' => 'Coffee',
                'icon_key' => 'coffee',
            ],
        ]);
    }

    public function getPromoBanner()
    {
        $promotions = PartnerPromotion::query()
            ->with('partner:id,restaurant_name,slug')
            ->visible()
            ->whereHas('partner', function ($query) {
                $query->where('active', true);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PartnerPromotion $promotion) => [
                'id' => $promotion->id,
                'partner_id' => $promotion->partner_id,
                'partner' => [
                    'id' => $promotion->partner->id,
                    'name' => $promotion->partner->restaurant_name,
                    'slug' => $promotion->partner->slug,
                ],
                'action' => [
                    'type' => 'merchant',
                    'partner_id' => $promotion->partner_id,
                    'slug' => $promotion->partner->slug,
                ],
                'name' => $promotion->name,
                'title' => $promotion->name,
                'subtitle' => $promotion->subtitle,
                'description' => $promotion->description,
                'cta_label' => $promotion->cta_label,
                'link_url' => $promotion->link_url ?: route('restaurant.view', $promotion->partner->slug),
                'image' => $promotion->image_url,
                'image_url' => $promotion->image_url,
                'sort_order' => $promotion->sort_order,
                'starts_at' => $promotion->starts_at?->toIso8601String(),
                'ends_at' => $promotion->ends_at?->toIso8601String(),
            ])
            ->values();

        return response()->json($promotions);
    }

    public function getTopPicks()
    {
        $topPicks = PartnerTopPick::query()
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now());
            })
            ->whereHas('partner.products', function ($query) {
                $query->where('active', true);
            })
            ->with([
                'partner.products' => function ($query) {
                    $query->where('active', true)
                        ->orderBy('created_at')
                        ->orderBy('id');
                },
            ])
            ->latest()
            ->get()
            ->unique('partner_id')
            ->map(function ($topPick) {
                $partner = $topPick->partner;
                $product = $partner->products->first();

                return [
                    'id' => $partner->id,
                    'partner_id' => $partner->id,
                    'name' => $product->title,
                    'restaurant_name' => $partner->restaurant_name,
                    'image_url' => $product->img
                        ? Partners::imageResizeThumb($product, $product->id)
                        : null,
                    'rating' => null,
                    'prep_time_label' => null,
                ];
            })
            ->values();

        return response()->json($topPicks);
    }

    public function getDashboardData(Request $request)
    {
        $logContext = [
            'session_id' => $request->input('session_id'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'has_authorization' => filled($request->bearerToken()),
            'user_id' => $this->authenticatedUser($request)?->id,
        ];

        Log::notice('Mobile dashboard request received.', $logContext);

        try {
            $validated = $request->validate([
                'current_city_only' => ['sometimes', 'boolean'],
                'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            ]);
        } catch (ValidationException $exception) {
            Log::warning('Mobile dashboard request validation failed.', $logContext + [
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        }

        try {
            $restaurantCity = $this->restaurantCity($request, $validated);
        } catch (ValidationException $exception) {
            Log::warning('Mobile dashboard city resolution failed.', $logContext + [
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        }

        \Log::info('Mobile dashboard city resolved.', $logContext + [
            'resolved_city' => $restaurantCity,
        ]); 

        [$latitude, $longitude] = $this->restaurantCoordinates($request, $validated);

        Log::info('Mobile dashboard merchant pull started.', $logContext + [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'resolved_city' => $restaurantCity,
            'current_city_only' => $request->boolean('current_city_only'),
        ]);

        $categories = $this->getCategories()->getData();
        $cuisines = $this->getCuisines()->getData();
        $promoBanners = $this->getPromoBanner()->getData();
        $topPicks = $this->getTopPicks()->getData();
        $restaurants = RestaurantService::getRestaurants($request, $restaurantCity);

        Log::info('Mobile dashboard merchant pull completed.', $logContext + [
            'resolved_city' => $restaurantCity,
            'restaurant_count' => $restaurants->count(),
            'restaurants' => $restaurants->map(fn ($restaurant) => [
                'partner_id' => $restaurant->id,
                'restaurant_name' => $restaurant->restaurant_name,
                'merchant_city' => $restaurant->getRawOriginal('city'),
                'branch_id' => $restaurant->location_id,
                'branch_city' => $restaurant->location?->city,
                'display_city' => $restaurant->city,
                'distance_km' => $restaurant->distance_km,
            ])->values()->all(),
        ]);

        return response()->json([
            'categories' => $categories,
            'cuisines' => $cuisines,
            'promo_banners' => $promoBanners,
            'top_picks' => $topPicks,
            'restaurants' => $restaurants,
        ]);

    }

    private function restaurantCity(Request $request, array $validated): ?string
    {
        [$latitude, $longitude] = $this->restaurantCoordinates($request, $validated);

        $savedAddress = $this->savedAddressCity($request);

        if ($savedAddress !== null) {
            Log::info('Mobile dashboard is using a saved address city.', [
                'session_id' => $request->input('session_id'),
                'user_id' => $this->authenticatedUser($request)?->id,
                'resolved_city' => $savedAddress['city'],
                'city_source' => $savedAddress['source'],
                'address_id' => $savedAddress['address_id'],
            ]);

            return $savedAddress['city'];
        }

        // Older app versions do not send current_city_only. Saved addresses or
        // delivery coordinates are enough to enable city filtering.
        if (! $request->boolean('current_city_only')
            && ($latitude === null || $longitude === null)) {
            return null;
        }

        $city = $this->reverseGeocoder->city($latitude, $longitude);

        if (filled($city)) {
            return $city;
        }

        $fallback = $this->fallbackRestaurantCity($request, $latitude, $longitude);

        if ($fallback !== null) {
            Log::warning('Mobile dashboard is using a fallback city.', [
                'session_id' => $request->input('session_id'),
                'user_id' => $this->authenticatedUser($request)?->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'resolved_city' => $fallback['city'],
                'city_source' => $fallback['source'],
            ]);

            return $fallback['city'];
        }

        throw ValidationException::withMessages([
            'city' => 'The city could not be determined from the current delivery coordinates or customer profile.',
        ]);
    }

    private function fallbackRestaurantCity(Request $request, $latitude, $longitude): ?array
    {
        $deliveryZone = app(DeliveryZone::class);
        $coordinateArea = $deliveryZone->areaAt($latitude, $longitude);

        if ($coordinateArea !== null) {
            return [
                'city' => $coordinateArea['label'],
                'source' => 'selected_coordinates_delivery_zone',
            ];
        }

        $user = $this->authenticatedUser($request);

        if (! $user) {
            return null;
        }

        $profileCity = trim((string) $user->city);

        return $profileCity !== ''
            ? ['city' => $profileCity, 'source' => 'user_profile']
            : null;
    }

    private function savedAddressCity(Request $request): ?array
    {
        $user = $this->authenticatedUser($request);

        if (! $user || ! Schema::hasTable('user_address')) {
            return null;
        }

        $baseQuery = UserAddress::query()
            ->where('user_id', $user->id)
            ->where('active', true)
            ->where('default', true);

        if (Schema::hasColumn('user_address', 'deleted_at')) {
            $baseQuery->whereNull('deleted_at');
        }

        $selectedAddressId = $request->input('delivery_address_id')
            ?? $request->input('address_id')
            ?? $this->selectedCartAddressId($request, $user->id);

        if ($selectedAddressId) {
            $selectedAddress = (clone $baseQuery)->find($selectedAddressId);
            $selectedCity = $this->addressCityName($selectedAddress);

            if ($selectedCity !== null) {
                return [
                    'city' => $selectedCity,
                    'source' => 'selected_delivery_address',
                    'address_id' => $selectedAddress->id,
                ];
            }
        }

        $defaultAddress = (clone $baseQuery)
            ->where('default', true)
            ->latest('id')
            ->first();
            
        $defaultCity = $this->addressCityName($defaultAddress);

        return $defaultCity === null
            ? null
            : [
                'city' => $defaultCity,
                'source' => 'default_delivery_address',
                'address_id' => $defaultAddress->id,
            ];
    }

    private function selectedCartAddressId(Request $request, int $userId): ?int
    {
        if (! Schema::hasTable('cart')) {
            return null;
        }

        $sessionId = trim((string) $request->input('session_id'));

        if ($sessionId === '') {
            return null;
        }

        return Cart::query()
            ->whereSessionId($sessionId)
            ->whereUserId($userId)
            ->value('address_id');
    }

    private function addressCityName(?UserAddress $address): ?string
    {
        if (! $address) {
            return null;
        }

        if (Schema::hasColumn('user_address', 'city_name')) {
            $cityName = trim((string) $address->city_name);

            if ($cityName !== '') {
                return $cityName;
            }
        }

        if (! Schema::hasTable('cities')) {
            return null;
        }

        $cityName = trim((string) $address->city?->name);

        return $cityName !== '' ? $cityName : null;
    }

    private function authenticatedUser(Request $request)
    {
        return $request->user('api') ?? $request->user();
    }

    private function restaurantCoordinates(Request $request, array $validated): array
    {
        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;

        if ($latitude !== null && $longitude !== null) {
            return [$latitude, $longitude];
        }

        $sessionId = trim((string) $request->input('session_id'));

        if ($sessionId === '') {
            return [null, null];
        }

        $cart = Cart::whereSessionId($sessionId)->first(['user_lat', 'user_long']);

        return [$cart?->user_lat, $cart?->user_long];
    }

    public function updateUserCoordinates(Request $request)
    {
        $user = $request->user();

        \Log::info('Updating user coordinates', [
            'user_id' => $user->id,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
        ]);

        $cart = Cart::whereSessionId($request->session_id)->first();

        if (! $cart) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        $cart->user_lat = $request->input('latitude');
        $cart->user_long = $request->input('longitude');

        $cart->save();

        return response()->json(['message' => 'User coordinates updated successfully.']);
    }
}
