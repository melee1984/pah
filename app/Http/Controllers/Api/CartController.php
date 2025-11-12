<?php

namespace App\Http\Controllers\Api;

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
use App\PushNotification;
use App\Events\SendPushNotificationEvent;

class CartController extends Controller
{
    //
    public function index() {
        
        $session_id = Session::getId();
        $data = array();
        $product_items = array();
        $summary = array();

        $cart = Cart::whereSessionId($session_id)->first();

        $cart->partnerlocation;

        if ($cart) {

            $product_items = $cart->cartItemList();    

            // Just to clear delivery fee 
            
            if (count($product_items)<=0) {
                // Update Delivery Fee equal to zero to balance 
                $cart->delivery_fee = 0;
                $cart->save();
            }

            $summary =  $cart->cartItemSummary();
            $cart->partner;

            try {
                // I have created new fucntion name Cart:: cartItemSummary to unserialize the variance content 
                // I will just put on hold while i am doing the others 
                foreach($product_items as $list) {

                    $list->variance_content = unserialize($list->variance_content);

                       if ($list->item) {
                            // I can call this because I am using hasOne 
                           
                            $list->item->price = number_format($list->item->getPrice(true) + number_format($list->variance_total,2),2);
                         
                        }
               }
                  
            } catch (Exception $e) {
                // Error::log($e);
            }
        }

        $data['cart'] =  $cart;
        $data['summary'] = $summary;

		return response()->json($data, 200);
    }

    public function checkout() {

        $session_id = Session::getId();

        $cart = Cart::whereSessionId($session_id)->first();

        $data = array();
        $product_items = array();
        $cart_summary = array();
        $delivery_timings = array();
        $user = array();

        if ($cart) {

            $delivery_timings = $cart->getTimingsSchedule();
            $product_items = $cart->cartItemList();  
            $cart_summary =  $cart->cartItemSummary();
            $cart->partner = Partners::imgCheck($cart->partner);

            $user = Auth::user();
            
            $user->addresses;
            $cart->partnerlocation;

            try {
                foreach($product_items as $list) {
                    $list->variance_content = unserialize($list->variance_content);
                       if ($list->item) {
                        // I can call this because I am using hasOne 
                        // $list->item->price = $list->item->getPrice(true) + $list->variance_total;
                        $list->item->price = number_format($list->item->getPrice(true) + number_format($list->variance_total,2),2);

                    }
                }
                    
            } catch (Exception $e) {
                
            }  

            $data['timings'] = DeliveryDistance::getCalendarDelivery($cart->partner);


        }

        $data['summary'] = $cart_summary;
        $data['customer'] = $user;
        $data['cart'] = $cart;
        $data['payment'] = PaymentMethod::active();
        $data['delivery_time'] = $delivery_timings;

        return response()->json($data, 200);

    }

    /**
     * Add cart Item 
     * return success response 
     */
    public function addCart(Request $request, $action = false) {

    	$data = array();
        $data['status'] = 1;
        $isNew = false;

        // Get the Products Item
        $item = Products::find($request->input('item_id'));

        if ($item) {
            $cart = Cart::whereSessionId(Session::getID())->first();
            try {
                if ($cart != null) {
                    if ((!$cart->user_long) && (!$cart->user_lat)) {
                        $data['status'] = 0;
                        $data['message'] = "Sorry, You haven't pin your current location.";
                        $data['pop'] = "Map";
                       return response()->json($data, 200);
                    }    
                }
                else {
                    $data['status'] = 0;
                    $data['message'] = "Cart does not exist please refresh your page. Thank you";
                    $data['pop'] = "Map";
                    return response()->json($data, 200);
                }
            } catch (Exception $e) {
                $data['status'] = 0;
                $data['message'] = "Sorry, You haven't pin your current location.";
                $data['pop'] = "Map";
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

            // validate if the cart valid and validate if the merchant id is the same account. 
            // second validation 
            // if ($cart) {
            //     if ($cart->partner_id != $item->user->merchant->id) {
            //         $data['message'] = "Adding different merchant will reset all item in the cart.";
            //         $data['status'] = 0;
            //         return response()->json($data, 200);
            //     }
            // }
            
            if (!$cart) {

                if (Auth::check()) {
                    $cart = new Cart;
                    $cart->session_id = Session::getId();
                    $cart->user_id = Auth::User()->id;
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
                    $cart->user_id = Auth::User()->id;
                    $cart->session_id = Session::getId();
                    $cart->ip_address  = $_SERVER['REMOTE_ADDR'];
                    $cart->partner_id = $item->user->merchant->id;
                    $cart->active = 1;
                    $cart->save();
                }
                else {

                    $cart = Cart::updateOrCreate(
                        ['session_id' => Session::getId(), 'ip_address' => $_SERVER['REMOTE_ADDR'], 'active' => 0],
                        ['session_id' => Session::getId(), 'ip_address' => $_SERVER['REMOTE_ADDR'], 'active' => 0, 'partner_id' => $item->user->merchant->id]);
                }

            }

            if ($cart) {

                 $data_variance = array();
                 $variant_total = 0;
                 $variant_total_comm = 0;     

                if ($item->variants) {
                    // I will be validating this later on to make sure all passed data is not empty ?? 
                    // Getting the variant information selected 
                    foreach($item->variants as $variantItem) {
                        foreach($variantItem->product_details as $variant) {

                           if ($variantItem->is_multiple) {
                                if ($request->has('variant_'.$variantItem->id.$variant->id)) {
                                    if ($request->input('variant_'.$variantItem->id.$variant->id) == $variantItem->id . "|" . $variant->id) {

                                        array_push($data_variance, array('variant_id' => $variantItem->id,
                                                    'variant_detail_id' => $variant->id,
                                                    'title' => $variantItem->title ." ". $variant->title,
                                                    'price' => $variant->getPrice(),
                                                ));    

                                                $variant_total = $variant_total + $variant->getPrice();    
                                                $variant_total_comm = $variant_total_comm + $variant->getPriceComm();      

                                    }
                                    
                                }    
                            }
                            else {
                                if ($request->has('variant_'.$variantItem->id)) {
                                    if ($request->input('variant_'.$variantItem->id) == $variantItem->id . "|" . $variant->id) {
                                        array_push($data_variance, array('variant_id' => $variantItem->id,
                                                    'variant_detail_id' => $variant->id,
                                                    'title' =>  $variantItem->title ." ". $variant->title,
                                                    'price' => $variant->getPrice()));  

                                                    $variant_total = $variant_total + $variant->getPrice();      
                                                    $variant_total_comm = $variant_total_comm + $variant->getPriceComm();      
                                    }
                                }    
                            }

                        }
                    }

                    $cartItem = CartItem::updateOrCreate(['cart_id'=>$cart->id, 'item_id' => $item->id, 'variance_content' => serialize($data_variance)], 
                                [   'cart_id'=>$cart->id, 
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
                    // Adding detail quantity
                    $cartItem->qty = $cartItem->qty+1;
                    $cartItem->save();
                }
            }

            $data['message'] = "Successfully updated cart";

            // Validate Distance if empty 
            //
        }
        else {
            $data['status'] = 0;
            $data['message'] = "Item not found";    
        }
		return response()->json($data, 200);
    		
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

        return response()->json($data, 200);

    }

    /**
     * [updateAddress description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updatePaymentGateway(Request $request) {
        
        $rules = [
            'payment'=>'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          // Validation failed
          return response()->json([
            'status' => 0,
            'message' => "Missing information required"
          ]);
        } 
        else {
            $session_id = Session::getId();
            $cart = Cart::whereSessionId($session_id)->first();
            $cart->payment_id = $request->input('payment');
            $status = $cart->save();

            if ($status) {
                $data['message'] = "Successfully added Payment Gateway";
                $data['status'] = 1;
                $data['payment_id'] =  $cart->payment_id;
            }
            else {
                $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
                $data['status'] = 0;    
            }
        }
        return response()->json($data, 200);
    }

    public function updateAddress(Request $request) {
            
        $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
        $data['status'] = 0;  

        $rules = [
            'address'=>'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          // Validation failed
          return response()->json([
            'status' => 0,
            'message' => "Missing information required"
          ]);
        } 
        else {

            $userAddress = UserAddress::find($request->input('address'));

            if ($userAddress) {
                $session_id = Session::getId();
                $cart = Cart::whereSessionId($session_id)->first();

                $cart->address_id = $userAddress->id;

                $status = $cart->save();

                if ($status) {
                    // Updating rate 
                    $cart->address_id = $userAddress->id;
                    $cart->user_long = $userAddress->long;
                    $cart->user_lat = $userAddress->lat;

                    $cart->save();

                    // reloadingrate after wards
                    $cart->deliveryRate();

                    $data['message'] = "Successfully selected address";
                    $data['status'] = 1;
                    $data['cart'] =  $cart;
                }

            }
            else {

                $data['message'] = "Unable to save record the selected user address does not exist";
                $data['status'] = 0;
            }
        }
        return response()->json($data, 200);
    }

    
    public function useraddress() {
        
        $data = array();

        $session_id = Session::getId();
        $cart = Cart::whereSessionId($session_id)->first();

        if ($cart) {
            $data['selectedid'] = $cart->address_id;        
        }
        
        return response()->json($data, 200);

    }

    public function process(Request $request) {

         $rules = [
            'smsCode'=>'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            // Validation failed
          return response()->json([
            'status' => 0,
            'message' => "OTP Password is require. Please check your registered mobile no. Thank you."
          ]);
        }
        else {

            $session_id = Session::getId();

            $cart = Cart::whereSessionId($session_id)
                            ->whereUserId(Auth::User()->id)->first();

            if ($request->input('smsCode') != $cart->sms_code) {
                $data['message'] = "Invalid OTP Password. Please try again";
                $data['status'] = 0;
            }
            else {

                try {   
                    
                    if ($request->input('delivery_date') == "Today") {
                        $deliveryDate = Date('M d, Y');
                    }
                    else {
                        $deliveryDate = $request->input('delivery_date');
                    }

                    $cart->processed_at = now();
                    $cart->mobile = Auth::User()->mobile;
                    $cart->fullname = Auth::User()->firstname . " " . Auth::User()->lastname;; 
                    $cart->email = Auth::User()->email;
                    $cart->delivery_date =  $deliveryDate;
                    $cart->delivery_time = $request->input('delivery_time');
                    $cart->order_no = $cart->generateOrderNo();
                    $cart->sms_code = "";
                    $cart->active = 1;

                    $status = $cart->save();

                    if ($status) {

                        $cart_id = $cart->id;

                        // copy selected address 
                        $userAddress = UserAddress::find($cart->address_id);

                        $cartUserAddress = CartUserAddress::updateOrCreate([
                            'cart_id' => $cart_id, 
                            'user_id' => $cart->user_id,],
                           array(
                            'cart_id' => $cart_id, 
                            'user_id' => $cart->user_id, 
                            'address_1' => $userAddress->address_1,  
                            'address_2' => $userAddress->address_2,  
                            'zip_code' => $userAddress->zip_code,  
                            'mobile' => $userAddress->mobile,  
                            'landmark' => $userAddress->landmark,   
                            'country_id'=> $userAddress->country_id,   
                            'province_id'=> $userAddress->province_id,   
                            'city_id' => $userAddress->city_id,  
                            'barangay_id'=> $userAddress->barangay_id,   
                            'lat' => $userAddress->lat,  
                            'long' => $userAddress->long,  
                           )
                        );

                        try {
                            
                            $order = Orders::updateOrCreate([
                                    'user_id' => $cart->user_id,
                                    'cart_id' => $cart_id,
                                    'status_id' => 1,
                                ], 
                                array(
                                    'user_id' => $cart->user_id,
                                    'cart_id' => $cart_id,
                                    'submitted_at' => now(),
                                    'status_id' => 1,
                                    'partner_id' => $cart->partner_id
                            ));

                            if ($order) {
                                
                                OrderProcess::updateOrCreate([
                                    'status_id' => 1, // on progress 
                                    'order_id' => $order->id
                                ]);

                                event (new SendPushNotificationEvent($order));
                            }

                        } catch (Exception $e) {
                            \Log('Error on saving Order ' . $cart_id );
                        }
                        // regenerate session 
                        $request->session()->regenerate();
                        // redirect url 
                        $data['redirectTo'] = route('checkout.success', $cart);
                        // message 
                        $data['message'] = "Successfully processed order";
                        // with status 
                        $data['status'] = 1;

                    }
                    else {
                        $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
                        $data['status'] = 0;    
                    }

                    
                } catch (Exception $e) {
                    
                    $data['message'] = $e->getMessage() . PHP_EOL;
                    $data['status'] = 0;

                }

                
            }
        }
        
        return response()->json($data, 200);
    }

    public function smsSending(Request $request) {

        $session_id = Session::getId();

        $smsCode = rand(12345,10012);

        if (Auth::User()->mobile != "")  {

            SmsNotification::sendCompleteOrder(Auth::User()->mobile, $smsCode);

            $cart = Cart::whereSessionId($session_id)->first();
            $cart->sms_code = $smsCode;

            $status = $cart->save();

            if ($status) {
                $data['message'] = "Generated OTP Password";
                $data['status'] = 1;
            }
            else {
                $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
                $data['status'] = 0;    
            }
        }
        else {

             $data['message'] = "You don't have registered mobile no. please update your profile.";
            $data['status'] = 0;    

        }

        return response()->json($data, 200);

    }
    /**
     * Success 
     * @param  Cart   $cart [description]
     * @return [type]       [description]
     */
    public function success(Cart $cart) {
        if ($cart->user_id != Auth::User()->id) return abort(405);
        return view('checkout.success', compact('cart'));   
    }

    public function updateLocationCoordinates(Request $request) {

        $cart = Cart::updateOrCreate(
            ['session_id' => Session::getId(), 
            'ip_address' => $_SERVER['REMOTE_ADDR']]
            , 
            ['session_id' => Session::getId(), 
            'ip_address' => $_SERVER['REMOTE_ADDR'], 
            'user_long' =>  $request->input('long'),
            'user_lat' =>  $request->input('lat'),
        ]);
        
        $cart->deliveryRate();

        $data['message'] = "Successfully added user data coordinates";
        $data['status'] = 1;

        return response()->json($data, 200);

    }

}
