<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Session;
use App\Model\Cart;
use App\Partners;
use App\Model\CartItem;
use App\Products;
use App\LibraryItemStatus;
use Auth;
use App\Model\User\UserAddress;
use App\PaymentMethod;
use Validator;
use App\Model\CartUserAddress;

use App\Model\Orders\Orders;
use App\Model\Orders\OrderProcess;

use App\Model\DeliveryDistance;
use App\SmsNotification;
use App\ProductItemDetails;
use App\ProductItemHeader;


class CartController extends Controller
{
   /**
     * Add cart Item 
     * return success response 
     */
    public function addCart(Request $request, $action = false) {



    	$data = array();
        $data['status'] = 1;
        $isNew = false;

        $sesssion_id = "";

        // Get the Products Item
        $item = Products::find($request->input('item_id'));
        $user = $request->user();

        if ($item) {

            $cart = Cart::whereSessionId($request->input('session_id'))
                            ->orderBy('created_at', 'desc')
                            ->first();
            try {

                if ($cart != null) {
                    if ((!$cart->user_long) && (!$cart->user_lat)) {
                        if (($request->input('long') =="") && ($request->input('lat')=="")) {
                            $data['status'] = 0;
                            $data['pop'] = "map";
                            $data['message'] = "Sorry, You haven't pin your current location.";
                            return response()->json($data, 200);
                        }
                        else {  
                            $cart->user_long = $request->input('long');
                            $cart->user_lat =  $request->input('lat');
                            $cart->user_id = Auth::User()->id;
                            $cart->save();
                        }
                    }    
                }
                else {
                   
                	// Create new Cart for this session
                	$cart = Cart::updateOrCreate(
    				            ['session_id' => $request->input('session_id'),
    				            'ip_address' => $_SERVER['REMOTE_ADDR'],
                                'user_long' =>  $request->input('long'),
                                'user_lat' =>  $request->input('lat')]  
    				            , 
    				            ['session_id' => $request->input('session_id'), 
    				            'ip_address' => $_SERVER['REMOTE_ADDR'], 
    				            'user_long' =>  $request->input('long'),
    				            'user_lat' =>  $request->input('lat'),
                                
    				        ]);
        
                }

            } catch (Exception $e) {
                $data['status'] = 0;
                $data['message'] = "Sorry, You haven't pin your current location.";
                $data['pop'] = "map";
                return response()->json($data, 200);

            }   

            // Removing Details here
            if ($action=="new") {
                
                foreach($cart->details as $list) {
                    $list->delete();
                }
                // Updating the delivery information when new partner id to recalculate 
                $cart->delivery_fee = 0;
                $cart->delivery_fee = 0;
                $cart->distance_rate = 0;
                $cart->duration = 0;
                $cart->origin = 0;
                $cart->destination = 0;
                $cart->save();
            }   
            
            if (!$cart) {

                if (Auth::check()) {
                    $cart = new Cart;
                    $cart->session_id = $request->input('session_id');
                    $cart->user_id = $user->id;
                    $cart->fullname = $user->firstname . " " .$user->lastname;
                    $cart->mobile = $user->mobile;
                    $cart->email = $user->email;
                    $cart->partner_id = $item->user->merchant->id;
                    $cart->ip_address  = $_SERVER['REMOTE_ADDR'];
                    $cart->active = 1;
                    $cart->save();
                }
                else {

                    $cart = Cart::updateOrCreate(
                        ['session_id' => Session::getId(), 'ip_address' => $_SERVER['REMOTE_ADDR'], 'active' => 0],
                        ['session_id' => Session::getId(), 'ip_address' => $_SERVER['REMOTE_ADDR'], 'active' => 0, 'partner_id' => $item->user->merchant->id]);
                }

            }
            else {

                if (Auth::check()) {
                    $cart->user_id = $user->id;
                    $cart->session_id = $request->input('session_id');
                    $cart->ip_address  = $_SERVER['REMOTE_ADDR'];
                    $cart->partner_id = $item->user->merchant->id;
                    
                    $cart->fullname = $user->firstname . " " .$user->lastname;
                    $cart->mobile = $user->mobile;
                    $cart->email = $user->email;

                    $cart->active = 1;
                    $cart->save();
                }
                else {

                    $cart = Cart::updateOrCreate(
                        ['session_id' => $request->input('session_id'), 'ip_address' => $_SERVER['REMOTE_ADDR'], 'active' => 0],
                        ['session_id' => $request->input('session_id'), 'ip_address' => $_SERVER['REMOTE_ADDR'], 'active' => 0, 'partner_id' => $item->user->merchant->id]);
                }

            }

            if ($cart) {

                 $data_variance = array();
                 $variant_total = 0;
                 $variant_total_comm = 0;     

                if ($item->variants) {

                	 if ($request->has('variants')) {
                	 	foreach($request->input('variants') as $variantIds) {
                	 		$ids = explode("|", $variantIds[0]);

                	 		$productItemHeader = ProductItemHeader::find($ids[0]);

                	 		if ($productItemHeader) {

                	 				$productItemDetails = ProductItemDetails::find($ids[1]);

                                    array_push($data_variance, array('variant_id' => $productItemHeader->id,
                                                'variant_detail_id' => $productItemDetails->id,
                                                'title' => $productItemDetails->title ." ". $productItemHeader->title,
                                                'price' => $productItemDetails->getPrice(),
                                            ));    

                                    $variant_total = $variant_total + $productItemDetails->getPrice();    
                                    $variant_total_comm = $variant_total_comm + $productItemDetails->getPriceComm();    
                                                
                	 		}

                	 	}

                	 }

                    $cartItem = CartItem::updateOrCreate(['cart_id'=>$cart->id, 'item_id' => $item->id, 'variance_content' => serialize($data_variance)], 
	                                ['cart_id'=>$cart->id, 
	                                    'item_id' => $item->id, 
	                                    'price' => $item->getPrice(true), 
	                                    'variance_content' => serialize($data_variance),
	                                    'variance_total' => $variant_total,
	                                    'is_not_available'  => $request->input('is_not_available'),
	                                    'instruction' => $request->input('instruction'),
	                                    'price_comm_total' => $item->getPriceComm(),
	                                    'variance_total_comm_total' => $variant_total_comm,
                                        'discount_amount' => $item->getDiscountAmount(),
	                                ]); 

                     // Adding detail quantity
                    $cartItem->qty = $cartItem->qty+1;
                    $cartItem->save();

                    // Validate Delivery Fee; 
                    if (($cart->delivery_fee=="")  || ($cart->delivery_fee==0) ){
                        $cart->deliveryRate();
                    }

                }
                else {

                    // Variant Included
                    $cartItem = CartItem::updateOrCreate(['cart_id'=>$cart->id, 'item_id' => $item->id],
                                    ['cart_id'=>$cart->id, 
                                    'item_id' => $item->id, 
                                    'price' => $item->getPrice(true),
                                    'price_comm_total' => $item->getPriceComm(),
                                    'discount_amount' => $item->getDiscountAmount(),
                                    ]
                    ); 

                    $cartItem->qty = $cartItem->qty+1;
                    $cartItem->save();
                }
            }

            $data['message'] = "Successfully updated cart";
            $data['data'] =  $this->cart($request->input('session_id'));
            // Validate Distance if empty 
            //
        }
        else {
          
            $data['status'] = 0;
            $data['message'] = "Item not found";    
        }
		return response()->json($data, 200);
    		
    }

    public function getCart(Request $request) {

      $data['data'] =  $this->cart($request->input('session_id'));

      return response()->json($data, 200);

    }

    public function cart($session_id) {

        $data = array();
        $product_items = array();
        $summary = array();

        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart) {
            
            $cart->partnerlocation;

            $product_items = $cart->cartItemList();    
            // Just to clear delivery fee 
            // 
            if (count($product_items)<=0) {
                // Update Delivery Fee equal to zero to balance 
                $cart->delivery_fee = 0;
                $cart->save();
            }

            $summary =  $cart->cartItemSummary();

            // foreach($summary $l) {
            //     $list->
            // }

            if ($cart->partner) {
                $cart->partner->photo = Partners::imgBanner($cart->partner, false);    
            }
            

            try {
                // I have created new fucntion name Cart:: cartItemSummary to unserialize the variance content 
                // I will just put on hold while i am doing the others 
                
                foreach($product_items as $list) {
                    //
                    $list->variance_content = unserialize($list->variance_content);
                    //
                    // Added this line to display the total price + the variance total 
                    // 
                    $list->price = number_format((float)$list->price + (float)$list->variance_total,2);
                    
                    if ($list->item) {
                        // I can call this because I am using hasOne 
                       $list->item->price = $list->item->getPrice();
                       
                       if ($list->item->img) {
                            $list->item->imgPhoto = Partners::imageResizeThumb($list->item, $list->item->id); 
                       }
                    }
               }
                  
            } catch (Exception $e) {
                // Error::log($e);
            }
        }
        
        $data['cart'] =  $cart;
        $data['summary'] = $summary;
       

        return $data;

    }

     public function modifyCartItem(Request $request, CartItem $cartItem, $status) {

        $data = array();
        switch ($status) {
            case 'add':
                $cartItem->qty = $cartItem->qty+1;
                $cartItem->save();
                $data['status'] = 1;
                break;
            case 'minus':

                $cartItem->qty = $cartItem->qty-1;
                $cartItem->save();
                $data['status'] = 1;
                break;
            case 'delete':
                $cartItem->delete();
                $data['status'] = 1;
                break;
            default:
            # code...
            break;
        }

        $data['data'] =  $this->cart($request->input('session_id'));

        return response()->json($data, 200);

    }

}
