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

}
