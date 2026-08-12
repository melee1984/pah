<?php

namespace App\Services;

use App\Partners;
use App\Model\DeliveryDistance;
use Illuminate\Support\Str;
use DB;
use Session;
use App\Model\Cart;

class RestaurantService
{
    public static function getRestaurants($request)
    {
       $data = array();

        \Log::info(['message' => 'Fetching restaurants input', 'request' => $request->all()]);
       
        $session_id = $request->session_id ?? $request->session()->getId();
        $cart = Cart::whereSessionId($session_id)->first();

        $userLat = $request->input('latitude') ?: ($cart ? $cart->user_lat : null);
        $userLong = $request->input('longitude') ?: ($cart ? $cart->user_long : null);

        $hasValidUserCoordinates = is_numeric($userLat)
            && is_numeric($userLong)
            && (float) $userLat >= -90
            && (float) $userLat <= 90
            && (float) $userLong >= -180
            && (float) $userLong <= 180;
        $userLat = $hasValidUserCoordinates ? (float) $userLat : 0.0;
        $userLong = $hasValidUserCoordinates ? (float) $userLong : 0.0;

        $restaurants = Partners::select('partners.user_id', 'partners.restaurant_name', 'partners.id', 'partners.img', 'partners.address', 'partners.slug', 'partners.city', 'partners.budget_id', 'partners.account_type_id', 'partners.store_open', 'partner_location.id as location_id', DB::raw('
                                (ST_Distance_Sphere(
                                point(partner_location.longtitude, partner_location.latitude),
                                point('.$userLong.', '.$userLat.')) * 0.001 / 1000) as meter'), DB::raw('
                                (ST_Distance_Sphere(
                                point(partner_location.longtitude, partner_location.latitude),
                                point('.$userLong.', '.$userLat.')) * 0.001) as distance_km'))
                        ->join('partner_location', 'partner_location.partner_id', '=', 'partners.id')
                        ->where('partner_location.active', 1)
                        ->where('partners.account_type_id', '<>', 4)
                        ->where('partners.active', 1)
                        ->whereNotNull('partners.verified_at')
                        ->with('products', 'products.variants', 'category', 'foodType', 'locations')
                        ->orderBy('partners.store_open', 'desc')
                        ->orderBy('distance_km', 'asc')
                        ->limit(15)
                        ->get();
            
            \Log::info([
                'message' => 'Fetched restaurants for the mobile app',
                'cart_id' => $cart->id ?? null,
                'user_lat' => $userLat,
                'user_long' => $userLong,
                'session_id' => $session_id,
            ]);
       
        // display only the active restaurants and not the ghost restaurants
        // $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city', 'budget_id', 'account_type_id')
        //                     ->with('products','products.variants', 'products.category')
        //                     ->where('account_type_id','<>',4)
        //                     ->activeRestaurants()
        //                     ->orderBy('store_open', 'desc')
        //                     ->get();

        //   \Log::info([
        //     'message' => 'Cart not found or user coordinates not set',
        //     'cart_id' => $cart ? $cart->id : null,
        //     'user_lat' => $cart ? $cart->user_lat : null,
        //     'user_long' => $cart ? $cart->user_long : null,
        //     'session_id' => $session_id,
        // ]);
     

        $restaurantIds = $restaurants->pluck('id');
        $restaurantOrigins = $restaurants
            ->mapWithKeys(function ($restaurant) {
                $location = $restaurant->locations->firstWhere('id', $restaurant->location_id);

                if (! $location
                    || ! is_numeric($location->latitude)
                    || ! is_numeric($location->longtitude)) {
                    return [];
                }

                return [
                    $restaurant->location_id => [
                        'latitude' => $location->latitude,
                        'longitude' => $location->longtitude,
                    ],
                ];
            })
            ->all();
        $userCoordinates = [
            'latitude' => $userLat,
            'longitude' => $userLong,
        ];
        $routeComputations = [];

        if ($hasValidUserCoordinates) {
            $routeComputations = config('services.google.distance_matrix_dashboard_enabled', false)
                ? DeliveryDistance::getBatchCoordinateComputations(
                    $restaurantOrigins,
                    $userCoordinates
                )
                : DeliveryDistance::getDashboardCoordinateComputations(
                    $restaurantOrigins,
                    $userCoordinates
                );
        }
        $cuisineTags = self::getCuisineTagsByPartner($restaurantIds);
        $categoryTags = self::getCategoryTagsByPartner($restaurantIds);

        foreach($restaurants as $restaurant) {

            $location = $restaurant->locations->firstWhere('id', $restaurant->location_id);
            $location?->makeHidden(['device_token']);
            $restaurant->setRelation('location', $location);
            $restaurant->setRelation('storeLocation', $location);
            // $restaurant->unsetRelation('locations');

            $restaurant->listing_id = $restaurant->id.'-'.$restaurant->location_id;

            $restaurant->short_title = Str::limit($restaurant->restaurant_name, 20);
            $restaurant->rating = 5.0;
            $restaurant->rating_count = 0;
            $routeComputation = $routeComputations[$restaurant->location_id] ?? null;
            $distanceKilometers = $routeComputation['distance_km']
                ?? (isset($restaurant->distance_km) ? (float) $restaurant->distance_km : null);
            $travelDurationMinutes = $routeComputation['duration_minutes'] ?? null;
            $estimatedDeliveryTime = $travelDurationMinutes !== null
                ? DeliveryDistance::getEstimatedDeliveryTimeFromDurationMinutes(
                    $travelDurationMinutes
                )
                : DeliveryDistance::getEstimatedDeliveryTimeFromDistanceKilometers(
                    $distanceKilometers
                );
            $restaurant->prep_time_min_minutes = $estimatedDeliveryTime['min_minutes'];
            $restaurant->prep_time_max_minutes = $estimatedDeliveryTime['max_minutes'];
            $restaurant->travel_duration_minutes = $travelDurationMinutes;
            $restaurant->distance_km = $distanceKilometers !== null ? round($distanceKilometers, 2) : null;
            $restaurant->delivery_fee = $distanceKilometers !== null
                ? DeliveryDistance::getRateFromDistanceKilometers($distanceKilometers)
                : 0.0;
            $restaurant->cuisine_tags = $cuisineTags->get($restaurant->id, collect())->values();
            $restaurant->category_tags = $categoryTags->get($restaurant->id, collect())->values();

            // check primary if has an item item // then locate and get the image render 
            // otherwise use the merchant logo 
            $hasItemImage = false;
            
            foreach($restaurant->products as $product) {
               
                // Get the image here from the product library 
                if ($product->img!="") {
                    $imagePath = Partners::imageResizeThumb($product, $product->id);
                    $restaurant->img = $imagePath;
                    $restaurant->image_url = $imagePath;
                    $product->image_url  = $imagePath;
                    $hasItemImage = true; // this will get the first image and break the loop
                }

                 $product->getPriceDisplay();
            }

            
            // verify if the img content image but if not then get the merchant logo 
            if (!$hasItemImage) {
                $restaurant = Partners::imageResize($restaurant, 'logo');    // Get the Image Thumbnail 
            }

        }

        return $restaurants;

    }

    private static function getCuisineTagsByPartner($partnerIds)
    {
        if ($partnerIds->isEmpty()) {
            return collect();
        }

        return DB::table('partner_cuisine')
            ->join('library_cuisine', 'partner_cuisine.cuisine_id', '=', 'library_cuisine.id')
            ->whereIn('partner_cuisine.partner_id', $partnerIds)
            ->where('partner_cuisine.active', 1)
            ->where('library_cuisine.active', 1)
            ->select('partner_cuisine.partner_id', 'library_cuisine.name')
            ->get()
            ->groupBy('partner_id')
            ->map(function ($tags) {
                return $tags->pluck('name');
            });
    }

    private static function getCategoryTagsByPartner($partnerIds)
    {
        if ($partnerIds->isEmpty()) {
            return collect();
        }

        return DB::table('partner_sector')
            ->join('sector', 'partner_sector.sector_id', '=', 'sector.id')
            ->whereIn('partner_sector.partner_id', $partnerIds)
            ->where('sector.active', 1)
            ->select('partner_sector.partner_id', 'sector.name')
            ->get()
            ->groupBy('partner_id')
            ->map(function ($tags) {
                return $tags->pluck('name');
            });
    }

}
