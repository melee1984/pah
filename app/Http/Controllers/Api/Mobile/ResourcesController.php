<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Partners;
use App\PartnerTopPick;
use App\Services\RestaurantService;

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
        return response()->json([
            [
                'image' => 'promo-banner-1.jpg',
                'title' => 'Special Offer',
                'subtitle' => 'On orders over ₱299',
                'description' => 'Get 50% off on your first order!',
                'cta_label' => 'Order Now',
            ],
            [
                'image' => 'promo-banner-2.jpg',
                'title' => 'New Menu',
                'subtitle' => 'On orders over ₱299',
                'description' => 'Check out our new delicious items!',
                'cta_label' => 'Explore Menu',
            ],
        ]);
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
            'restaurants' => RestaurantService::getRestaurants(), // Call the RestaurantService to get restaurants
        ]);

    }

    public function updateUserCoordinates(Request $request)
    {
        $user = $request->user();

        $cart = Cart::whereSessionId($request->session()->getId())->first();
        
        if (!$cart) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        $cart->latitude = $request->input('latitude');
        $cart->longitude = $request->input('longitude');
        $cart->save();

            return response()->json(['message' => 'User coordinates updated successfully.']);
    }
}
