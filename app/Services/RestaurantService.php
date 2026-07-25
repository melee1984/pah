<?php

namespace App\Services;

use App\Partners;
use Illuminate\Support\Str;
use DB;
use Session;
use App\Model\Cart;

class RestaurantService
{
    public static function getRestaurants()
    {
       $data = array();

        $session_id = Session::getId();
        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart && $cart->user_lat!="") {

            $userLat = (float) $cart->user_lat;
            $userLong = (float) $cart->user_long;

            $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city' , 'budget_id' , 'account_type_id', DB::raw('
                                    (select (ST_Distance_Sphere(
                                    point(partner_location.longtitude, partner_location.latitude),
                                    point('.$userLong.', '.$userLat.')) * 0.001 / 1000) 
                                    from partner_location where partner_location.partner_id = partners.id limit 0,1)  as meter'), DB::raw('
                                    (select (ST_Distance_Sphere(
                                    point(partner_location.longtitude, partner_location.latitude),
                                    point('.$userLong.', '.$userLat.')) * 0.001) 
                                    from partner_location where partner_location.partner_id = partners.id limit 0,1)  as distance_km'))
                            ->where('account_type_id','<>',4)
                            ->with('products','products.variants', 'category')
                            ->activeRestaurants()
                            ->orderBy('store_open', 'desc')
                            ->orderBy('distance_km', 'asc')
                            ->get();
                    
        }
        else {

        // $restaurants = Partners::activeRestaurants();
        // display only the active restaurants and not the ghost restaurants
        $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city', 'budget_id', 'account_type_id')
                            ->with('products','products.variants', 'products.category')
                            ->where('account_type_id','<>',4)
                            ->activeRestaurants()
                            ->orderBy('store_open', 'desc')
                            ->get();

        }

        $restaurantIds = $restaurants->pluck('id');
        $cuisineTags = self::getCuisineTagsByPartner($restaurantIds);
        $categoryTags = self::getCategoryTagsByPartner($restaurantIds);

        foreach($restaurants as $restaurant) {

            $restaurant->short_title = Str::limit($restaurant->restaurant_name, 20);
            $restaurant->rating = null;
            $restaurant->rating_count = 0;
            $restaurant->prep_time_min_minutes = null;
            $restaurant->prep_time_max_minutes = null;
            $restaurant->distance_km = isset($restaurant->distance_km) ? round((float) $restaurant->distance_km, 2) : null;
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
                    break;
                }
              
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