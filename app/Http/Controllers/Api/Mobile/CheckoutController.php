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

use Str;
use Hash;
use URL;

use App\PushNotification;
use App\Events\SendPushNotificationEvent;

class CheckoutController extends Controller
{	
    
    public function checkout(Request $request) {

    	$session_id = $request->input('session_id');

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

            $user = $request->user();
            
            if ($user->addresses) {
                $user->addresses;    
            }
            
            $cart->partnerlocation;

            try {
                foreach($product_items as $list) {
                    $list->variance_content = unserialize($list->variance_content);
                       if ($list->item) {
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

    public function validatedSMScode(Request $request) {

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

            $session_id = $request->input('session_id');
            $user = $request->user();

            $cart = Cart::whereSessionId($session_id)
                            ->whereUserId($user->id)->first();

           if ($cart->sms_code == "") {
                $data['message'] = "Invalid OTP code.";
                $data['status'] = 0;
           }
            else if ($request->input('smsCode') != $cart->sms_code) {
                $data['message'] = "Invalid OTP code.";
                $data['status'] = 0;
            }
            else {
                $cart->sms_code_validated_at = now();
                // $cart->sms_code = "";
                $status = $cart->save();

                $data['message'] = "Confirmed OTP Code";
                // with status 
                $data['status'] = 1;
            }
        }

        return response()->json($data, 200);

    }

    public function resetSession(Request $request) {

        $user = $request->user();

        $request->session()->regenerate();
        $session_id = Session::getId();

        return response()->json([
          'status'    =>  1,
          'name'         => $user->firstname . " " . $user->lastname,
          'firstname'    => $user->firstname,
          'lastname'    => $user->lastname,
          'email'        => $user->email,
          'mobile'  =>$user->mobile,
          'access_token' => $user->api_token,
          'session_id' => $session_id,
        ]);


    }

    public function process(Request $request) {

        $session_id = $request->input('session_id');
        $user = $request->user();

        $cart = Cart::whereSessionId($session_id)
                        ->whereUserId($user->id)->first();

        if ($cart->sms_code_validated_at == "") {

            $data['message'] = "We have encounter some issue when verifying the OPT code. Please try to re submit again.";
            $data['status'] = 0;
        }
        else {
            try {       
                if ($request->input('deliveryDate') == "Today") {
                    $deliveryDate = Date('M d, Y');
                }
                else {
                    $deliveryDate = $request->input('deliveryDate');
                }
                $cart->processed_at = now();
                $cart->mobile = $user->mobile;
                $cart->fullname = $user->firstname . " " . $user->lastname;; 
                $cart->email = $user->email;
                $cart->delivery_date = $deliveryDate;
                $cart->delivery_time = $request->input('deliveryTime');
                $cart->address_id = $request->input('deliveryAddressId'); // Static 
                $cart->payment_id = $request->input('deliveryPaymentId');
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

                            // Push Notification 
                            // PushNotification::sendPushStore($order);
                            event (new SendPushNotificationEvent($order));
                        }

                    } catch (Exception $e) {
                        \Log('Error on saving Order ' . $cart_id );
                    }

                    // regenerate session 
                    // redirect url 
                    // message 
                    $data['message'] = "Successfully processed order";
                    // with status 
                    $data['status'] = 1;
                    $data['order_id'] = $order->id;
                    $data['order'] = $order;

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
        
        return response()->json($data, 200);
    }

    public function smsSending(Request $request) {

        $session_id = $request->input('session_id');

        $smsCode = rand(12345,10012);

        if (Auth::User()->mobile != "")  {

            if ($request->user()->isAdmin()) {
                // does nothing 
                $smsCode = "00000";
            }
            else {
                SmsNotification::sendCompleteOrder(Auth::User()->mobile, $smsCode);
            }
            
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
     * [address description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function addAddress(Request $request) {
        
         $rules = [
            'title'=>'required',
            'address'=>'required',
            'latitude' => 'required',
            'longtitude' => 'required',
        ];

        $data = array();
        $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
        $data['status'] = 0;    

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          // Validation failed
          return response()->json([
            'status' => 0,
            'message' => "Missing information required"
          ]);
        } 
        else {

            $session_id = $request->input('session_id');
            $user = $request->user();

            $cart = Cart::whereSessionId($session_id)->first();
            
            $userAddress = UserAddress::updateOrCreate(
                ['address_1' => $request->input('address'), 'landmark' => $request->input('landmark'), 'title' => $request->input('title'), 'user_id' => $user->id],
                ['active' => 1, 'address_1' => $request->input('address'), 'landmark' => $request->input('landmark'), 'title' => $request->input('title'), 'lat' => $request->input('latitude'), 'long' => $request->input('longtitude'), 'user_id' => $user->id, 'status' => ' 1']);

            if ($cart) {
                
                // Updating rate 
                $cart->address_id = $userAddress->id;
                $cart->user_long = $userAddress->long;
                $cart->user_lat = $userAddress->lat;
                $cart->save();

                // reloadingrate after wards
                $cart->deliveryRate();

            }

            $data['message'] = "Successfully insert record";
            $data['status'] = 1;
            $data['addresses'] = $user->addresses;
            $data['address'] = $userAddress;
            
        }

        return response()->json($data, 200);

    }

    public function updateUserProfile(Request $request) {

        $rules = [
            'fullname'=>'required',
            'email'=>'required',
            'mobile' => 'required',
        ];

        $data = array();
        $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
        $data['status'] = 0;    

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          // Validation failed
          return response()->json([
            'status' => 0,
            'message' => "Missing information required"
          ]);
        }
        else {

            $session_id = $request->input('session_id');
            $user = $request->user();

            $cart = Cart::whereSessionId($session_id)->first();

            $cart->fullname = $request->input('fullname');
            $cart->mobile = $request->input('mobile');
            $cart->email = $request->input('email');
            $status = $cart->save();

            if ($status) {
                $data['status'] = 1;
                $data['message'] = "Successfully updated cart profile";
            }

            $data['cart'] = $cart;

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
                $session_id = $request->input('session_id');
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


}
