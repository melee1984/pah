<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Partners;
use App\Products;
use Cache;
use App\Model\Cart;
use Session;
use Str;
use DB;
use Auth;
use App\Sector;

class HomeController extends Controller
{
    public function home() {

    	$data = array();
    	$categories = array(
    			array('id' => 1, 'title' => 'Breakfast', 'img' => asset('images/icons/mobile/breakfast.png') ),
				array('id' => 9,'title' => 'Burgers', 'img' => asset('images/icons/mobile/burger.png') ),
				array('id' => 5,'title' => 'Drinks', 'img' => asset('images/icons/mobile/drinks.png') ),
				array('id' => 2,'title' => 'Lunch', 'img' => asset('images/icons/mobile/lunch.png')),
				array('id' => 6,'title' => 'HotDrinks', 'img' => asset('images/icons/mobile/hotdrinks.png')),
				array('id' => 10,'title' => 'Salads', 'img' => asset('images/icons/mobile/salads.png')),
				array('id' => 3,'title' => 'Dinner', 'img' => asset('images/icons/mobile/dinner.png')),
		);
        // 
    	$highlights = array(
                array('id' => 1,'img' => asset('images/highlights/ads1.png')),
                array('id' => 2,'img' => asset('images/highlights/ad2.png')),
                array('id' => 3,'img' => asset('images/highlights/ad3.png')),
        );

    	$featured = array();

    	$data['categories'] = $categories;
    	$data['highlights'] = $highlights;
    	$data['featured'] = $featured;

    	$data['restaurants'] = $featured;

    	return response()->json($data, 200);

    }

    public function listSector(Sector $sector, Request $request) {

        $data = array();

        $session_id = $request->input('session_id');
        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart && $cart->user_lat!="") {

            $data['display'] = 1;
            $restaurants = Partners::select('partners.*' , DB::raw('
                            (select (ST_Distance_Sphere(
                            point(partner_location.longtitude, partner_location.latitude),
                            point('.$cart->user_long.', '.$cart->user_lat.')) * 0.001 / 1000) 
                            from partner_location where partner_location.partner_id = partners.id limit 0,1)  as meter'))
                           ->Join('partner_sector', 'partners.id', '=', 'partner_sector.partner_id')
                            ->Join('sector', 'partner_sector.sector_id', '=', 'sector.id')
                             ->where('partners.active','=',1)
                            ->where('account_type_id','<>',4)
                             ->where('partner_sector.sector_id','=', $sector->id)
                              ->groupBy('id')
                            ->orderBy('store_open', 'desc')
                            ->orderBy('meter', 'asc')
                            ->paginate(10);
                
        }
        else {

                 $data['display'] = 2;
         $restaurants = Partners::select('partners.*')->where('partners.active','=',1)
                    ->with('foodType')
                    ->whereNotNull('verified_at')
                     ->where('partners.active','=',1)
                     ->where('partners.account_type_id','<>',4)
                    ->Join('partner_sector', 'partners.id', '=', 'partner_sector.partner_id')
                    ->Join('sector', 'partner_sector.sector_id', '=', 'sector.id')
                    ->where('sector.id', '=',  $sector->id)
                    ->paginate(10);

        }

      
        foreach($restaurants as $restaurant) {
            $restaurant->short_title = Str::limit($restaurant->restaurant_name, 20);

            // check primary if has an item item // then locate and get the image render 
            // otherwise use the merchant logo 
            $hasItemImage = false;
            foreach($restaurant->products as $image) {
                // 
                // Get the image here from the product library 
                if ($image->img!="") {
                    $restaurant->img = Partners::imageResizeThumb($image, $image->id, false);
                    $hasItemImage = true;
                    break;
                }

            }
            // verify if the img content image but if not then get the merchant logo 
            if (!$hasItemImage) {
                $restaurant = Partners::imageResize($restaurant, 'logo-banner-size', false);    // Get the Image Thumbnail 
            }

        }

        return response()->json($restaurants, 200);


    }


    public function search(Request $request) {

        $data = array();

        $session_id = $request->input('session_id');
        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart && $cart->user_lat!="") {

        $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city' , 'budget_id' , DB::raw('
                            (select (ST_Distance_Sphere(
                            point(partner_location.longtitude, partner_location.latitude),
                            point('.$cart->user_long.', '.$cart->user_lat.')) * 0.001 / 1000) 
                            from partner_location where partner_location.partner_id = partners.id limit 0,1)  as meter'))
                    ->activeRestaurants()
                    ->where('account_type_id','<>',4)
                    ->where('search_string', 'like', '%'.$request->input('s').'%' )
                    ->orwhere('restaurant_name', 'like', '%'.$request->input('s').'%' )
                    ->orwhere('city', 'like', '%'.$request->input('s').'%' )
                    ->orderBy('store_open', 'desc')
                    ->orderBy('meter', 'asc')
                    ->paginate(15);
                
        }
        else {

        // $restaurants = Partners::activeRestaurants();
        // 
        $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city', 'budget_id')
                    ->activeRestaurants()
                     ->where('account_type_id','<>',4)
                     ->where('search_string', 'like', '%'.$request->input('s').'%' )
                    ->orwhere('restaurant_name', 'like', '%'.$request->input('s').'%' )
                    ->orwhere('city', 'like', '%'.$request->input('s').'%' )
                    ->orderBy('store_open', 'desc')
                     ->paginate(15);

        }


        foreach($restaurants as $restaurant) {
            $restaurant->short_title = Str::limit($restaurant->restaurant_name, 20);

            // check primary if has an item item // then locate and get the image render 
            // otherwise use the merchant logo 
            $hasItemImage = false;
            foreach($restaurant->products as $image) {
                // 
                // Get the image here from the product library 
                if ($image->img!="") {
                    $restaurant->img = Partners::imageResizeThumb($image, $image->id, false);
                    $hasItemImage = true;
                    break;
                }

            }
            // verify if the img content image but if not then get the merchant logo 
            if (!$hasItemImage) {
                $restaurant = Partners::imageResize($restaurant, 'logo-banner-size', false);    // Get the Image Thumbnail 
            }

        }
        
        return response()->json($restaurants, 200);

    }

    public function list(Request $request) {   

        $data = array();

        $session_id = $request->input('session_id');
        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart && $cart->user_lat!="") {

        $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city' , 'budget_id' , DB::raw('
                            (select (ST_Distance_Sphere(
                            point(partner_location.longtitude, partner_location.latitude),
                            point('.$cart->user_long.', '.$cart->user_lat.')) * 0.001 / 1000) 
                            from partner_location where partner_location.partner_id = partners.id limit 0,1)  as meter'))
                    ->activeRestaurants()
                    ->where('account_type_id','<>',4)
                    ->orderBy('store_open', 'desc')
                    ->orderBy('meter', 'asc')
                    ->paginate(10);
                
        }
        else {

        // $restaurants = Partners::activeRestaurants();
        // 
        $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city', 'budget_id')
                    ->activeRestaurants()
                     ->where('account_type_id','<>',4)
                    ->orderBy('store_open', 'desc')
                     ->paginate(10);

        }

        foreach($restaurants as $restaurant) {
            $restaurant->short_title = Str::limit($restaurant->restaurant_name, 20);

            // check primary if has an item item // then locate and get the image render 
            // otherwise use the merchant logo 
            $hasItemImage = false;
            foreach($restaurant->products as $image) {
                // 
                // Get the image here from the product library 
                if ($image->img!="") {
                    $restaurant->img = Partners::imageResizeThumb($image, $image->id, false);
                    $hasItemImage = true;
                    break;
                }

            }
            // verify if the img content image but if not then get the merchant logo 
            if (!$hasItemImage) {
                $restaurant = Partners::imageResize($restaurant, 'logo-banner-size', false);    // Get the Image Thumbnail 
            }

        }
        
        return response()->json($restaurants, 200);

    }

    public function restaurant(Partners $partner) {

        $data = array();

        if (!$partner) {
            echo "Invalid Request";
            die();           
        }

        $partner->banner = Partners::imgBanner($partner, false);

        $newCategoryArray = array();

        foreach($partner->category as $category) {
           
            if ($category->products) {
                foreach($category->products as $product) {

                 $product->variants;

                    foreach($product->variants as $variant) {
                        // dd($variant->product_details);
                        foreach($variant->product_details as $detail) {
                           $detail->retail_price = $detail->getPrice();
                        }
                    }

                    // $image->getPriceDisplay();
                    $product->retail_price = $product->getPriceDisplay();

                    $product->imgname = Partners::imageResizeThumb($product, $product->id, false); 
                    
                }
            }

            if (count($category->products) >0) {
                array_push($newCategoryArray, $category);    
            }
            
        }

        $partner->categories = $newCategoryArray;

        // get the primary image 
        $hasItemImage = false;
        foreach($partner->products as $image) {
            // Get the image here from the product library 
            if ($image->img!="") {
                $partner->img = Partners::imageResizeThumb($image, $image->id, false);
                $hasItemImage = true;
                break;
            }
        }

        unset($partner->category);
        unset($partner->products);

        if (!$hasItemImage) {
           $partner->img = Partners::imageResizeThumb($partner, $partner->id, false);   // Get the Image Thumbnail 
        }

        return response()->json($partner, 200);
    }

      

}
