<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\PartnerPromotion;
use App\Partners;
use App\PartnerTopPick;
use App\Services\RestaurantService;
use Illuminate\Http\Request;

class ResourcesController extends Controller
{
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
                'label' => 'Pizza xxx',
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
        $categories = $this->getCategories()->getData();
        $cuisines = $this->getCuisines()->getData();
        $promoBanners = $this->getPromoBanner()->getData();
        $topPicks = $this->getTopPicks()->getData();

        return response()->json([
            'categories' => $categories,
            'cuisines' => $cuisines,
            'promo_banners' => $promoBanners,
            'top_picks' => $topPicks,
            'restaurants' => RestaurantService::getRestaurants($request), // Call the RestaurantService to get restaurants
        ]);

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
