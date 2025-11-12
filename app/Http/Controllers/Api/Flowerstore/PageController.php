<?php

namespace App\Http\Controllers\Api\Flowerstore;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Partners;
use Illuminate\Support\Str;

use DB;
use Session;
use App\Model\Cart;

class PageController extends Controller
{
     public function list() 
   	{	
   		$data = array();

      $session_id = Session::getId();
      $cart = Cart::whereSessionId($session_id)->first();
      
      if ($cart && $cart->user_lat!="") {

          $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city' , 'budget_id' , 'account_type_id', DB::raw('
                                (select (ST_Distance_Sphere(
                                point(partner_location.longtitude, partner_location.latitude),
                                point('.$cart->user_long.', '.$cart->user_lat.')) * 0.001 / 1000) 
                                from partner_location where partner_location.partner_id = partners.id limit 0,1)  as meter'))
                        ->where('account_type_id','=',4)
                        ->activeRestaurants()
                        ->orderBy('store_open', 'desc')
                        ->orderBy('meter', 'asc')
                        ->get();
                    
      }
      else {
        
        // $restaurants = Partners::activeRestaurants();
        // 
          $restaurants = Partners::select('user_id', 'restaurant_name', 'id', 'img', 'address', 'slug', 'address', 'city', 'budget_id', 'account_type_id')
                   
                        ->where('account_type_id','=',4)
                        ->activeRestaurants()
                        ->orderBy('store_open', 'desc')
                        ->get();
          
      }
   		
        foreach($restaurants as $restaurant) {

              $restaurant->short_title = Str::limit($restaurant->restaurant_name, 20);

              // check primary if has an item item // then locate and get the image render 
              // otherwise use the merchant logo 
              $hasItemImage = false;
              foreach($restaurant->products as $image) {

                  // Get the image here from the product library 
                  if ($image->img!="") {
                      $restaurant->img = Partners::imageResizeThumb($image, $image->id);
                      $hasItemImage = true;
                      break;
                  }
              }

              // verify if the img content image but if not then get the merchant logo 
              if (!$hasItemImage) {
                  $restaurant = Partners::imageResize($restaurant, 'logo');    // Get the Image Thumbnail 
              }

    }

	   	return response()->json($restaurants, 200);
      
   	}
}
