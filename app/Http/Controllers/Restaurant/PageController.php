<?php

namespace App\Http\Controllers\Restaurant;

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

class PageController extends Controller
{
	/**
	 * [show description]
	 * @return [type] [description]
	 */
    public function show() {

    // $user_long = "125.5986093";
    // $user_lat = "7.1590395";

    // // $restuarants = DB::table('partner_location')
    // //          ->select('id','partner_id', 'latitude', 'longtitude','address_1', DB::raw('
    // //          (select ST_Distance_Sphere(
    // //           point(partner_location.longtitude, partner_location.latitude),
    // //           point('.$user_long.', '.$user_lat.')) * 0.001 / 1000)  as meter'))
    // //          ->whereActive(1)
    // // //          ->orderby('meter', 'asc')
    // //          ->first();

        

    //     // $restuarants = Partners::select('id','restaurant_name')
    //     //                 ->whereHas('locations', function($query) {
    //     //                     return $query->
    //     //                 })
    //     //                 ->get();
    

    // $restuarants = Partners::select('id','restaurant_name', 
    //                 DB::raw('
    //                         (select (ST_Distance_Sphere(
    //                         point(partner_location.longtitude, partner_location.latitude),
    //                         point('.$user_long.', '.$user_lat.')) * 0.001 / 1000) 
    //                         from partner_location where partner_location.partner_id = partners.id limit 0,1)  as meter'))
    //                 ->whereActive(1)
    //                 ->orderBy('meter', 'asc')
    //                 ->get();

    //     dd($restuarants);

    //     die();

        return view('pages.restaurant.list');
    }
    /**
     * search page 
     * @return [type] [description]
     */
    public function search(Request $request) {

        $restaurants = Partners::whereActive(1)
                    ->whereStoreOpen(1)
                    ->with('foodType')
                    ->whereNotNull('verified_at')
                    ->where('search_string', 'like', '%'.$request->input('s').'%' )
                    ->orwhere('restaurant_name', 'like', '%'.$request->input('s').'%' )
                    ->orwhere('city', 'like', '%'.$request->input('s').'%' )
                    ->get();

        foreach($restaurants as $restaurant) {
            // $restaurant->short_title = Str::limit($restaurant->restaurant_name, 20);
            // $restaurant = Partners::imgCheck($restaurant, true);    
            // 
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

        return view('pages.restaurant.search', compact('restaurants'));
    }

    /**
     * [searchTag description]
     * @param  Request $request       [description]
     * @param  [type]  $search_string [description]
     * @return [type]                 [description]
     */
    public function searchTag(Request $request, $search_string) {

        $restaurants = Partners::select('partners.*')->where('partners.active','=',1)
                    ->with('foodType')
                    ->whereNotNull('verified_at')
                    ->Join('partner_sector', 'partners.id', '=', 'partner_sector.partner_id')
                    ->Join('sector', 'partner_sector.sector_id', '=', 'sector.id')
                    ->where('sector.name', 'like', '%'.$search_string.'%' )
                    ->get();
     
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
   

        return view('pages.restaurant.search', compact('restaurants'));

    }

    /**
     * [view description]
     * @param  Partners $partner [description]
     * @return [type]            [description]
     */
    public function view(Partners $partner) {

        
        $partner->foodType; 
        $partner->category;
        $addingForThisMerchant = "true";

        $session_id = Session::getId();
        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart) {
            if ($cart->partner_id=="") {

            } else if ($cart->partner_id != $partner->id) {
                $addingForThisMerchant = "false";
            }
        }

        foreach($partner->category as $category) {
            if ($category->products) {
                foreach($category->products as $product) {
                    $product->variants;
                    foreach($product->variants as $variant) {
                        // dd($variant->product_details);
                        foreach($variant->product_details as $detail) {
                           $detail->price = $detail->getPrice();
                        }
                    }
                    // $product->price = $product->getPrice();
                    $product->getPriceDisplay();
                    Products::productImageCheck($product, false); 
                    Products::amountConvertion($product);   
                }
            }
        }

        return view('pages.restaurant.detailed', compact('partner', 'addingForThisMerchant'));
    }	

}
