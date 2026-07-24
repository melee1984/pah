<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
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

    public function getTopPics()
    {
        $topPicks = [
            [
                'id' => 'pepperoni-pizza',
                'name' => 'Pepperoni Pizza xxxx',
                'restaurant_name' => 'Pizza House',
                'image_url' => null,
                'rating' => 4.7,
                'prep_time_label' => '25–35 min',
            ],
            [
                'id' => 'carbonara',
                'name' => 'Carbonara xcxcx',
                'restaurant_name' => 'Pasta Lovers',
                'image_url' => null,
                'rating' => 4.6,
                'prep_time_label' => '20–30 min',
            ],
            [
                'id' => 'iced-caramel-macchiato',
                'name' => 'Iced Caramel Macchiato xczxc',
                'restaurant_name' => 'Brewed Daily',
                'image_url' => null,
                'rating' => 4.8,
                'prep_time_label' => '15–20 min',
            ],
        ];

        return response()->json($topPicks);
    }


}
