<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Coupon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $cart->payment;

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

        \Log::info('Checkout data: ' . json_encode($data));

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

            $cart = Cart::whereSessionId($session_id)->whereUserId($user->id)->first();

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

        if ($request->has('deliveryPaymentId')) {
            $deliveryPaymentId = PaymentMethod::resolveCheckoutId(
                $request->input('deliveryPaymentId')
            );

            if ($deliveryPaymentId === null) {
                return response()->json([
                    'status' => 0,
                    'message' => 'The delivery payment method must be credit_card, gcash, cod, 1, 2, or 3.',
                    'errors' => [
                        'deliveryPaymentId' => [
                            'The selected delivery payment method is invalid.',
                        ],
                    ],
                ], 200);
            }

            $request->merge([
                'deliveryPaymentId' => $deliveryPaymentId,
            ]);
        }

        $rules = [
            'session_id' => 'required|string',
            'deliveryDate'=>'required',
            'deliveryTime'=>'required',
            'deliveryAddressId' => 'required|integer',
            'deliveryPaymentId' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 200);
        }

        $session_id = $request->input('session_id');
        $user = $request->user();

        \Log::info('Processing checkout for user ID: ' . $user->id . ' with session ID: ' . $session_id);
        \Log::info('user data: ' . json_encode($user));
        
        $cart = Cart::whereSessionId($session_id)
                        ->whereUserId($user->id)->first();

        $cart->payment;

        if (!$cart) {
            return response()->json([
                'status' => 0,
                'message' => 'Cart not found for this user and session.',
            ], 200);
        }

        if ($cart->discount_code) {
            $coupon = Coupon::appliedToCart($cart);

            if (! $coupon || $coupon->hasReachedUsageLimit()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'The applied coupon is no longer available or has reached its usage limit. Please remove it and try again.',
                ], 200);
            }
        }

        \Log::info('checkout input data: ' . json_encode($request->all()));

        $userAddress = UserAddress::where('id', $request->input('deliveryAddressId'))
            ->whereUserId($user->id)
            ->where('active', 1)
            ->first();

        if (!$userAddress) {
            return response()->json([
                'status' => 0,
                'message' => 'The selected delivery address is invalid.',
            ], 200);
        }

        $paymentMethod = PaymentMethod::where('id', $request->input('deliveryPaymentId'))
            ->where('active', 1)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'status' => 0,
                'message' => 'The selected payment method is invalid.',
            ], 200);
        }

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
                // samplke
                $cart->processed_at = now();
                $cart->mobile = $user->mobile;
                $cart->fullname = $user->firstname . " " . $user->lastname;; 
                $cart->email = $user->email;
                $cart->delivery_date = $deliveryDate;
                $cart->delivery_time = $request->input('deliveryTime');
                $cart->address_id = $request->input('deliveryAddressId'); // address of the user  
                $cart->payment_id = $request->input('deliveryPaymentId');

                $cart->order_no = $cart->generateOrderNo();
                $cart->sms_code = "";
                $cart->active = 1;
              
                $status = $cart->save();

                if ($status) {

                    $cart_id = $cart->id;

                    // copy selected address 
                    $cartUserAddress = CartUserAddress::updateOrCreate([
                        'cart_id' => $cart_id, 
                        'user_id' => $cart->user_id,],
                        array(
                                'cart_id' => $cart_id, 
                                'user_id' => $cart->user_id, 
                                'title' => $userAddress->title ?? null, 
                                'address_1' => $userAddress->address_1 ?? null,  
                                'address_2' => $userAddress->address_2 ?? null,  
                                'zip_code' => $userAddress->zip_code ?? null,  
                                'mobile' => $userAddress->mobile ?? null,  
                                'landmark' => $userAddress->landmark ?? null,   
                                'country_id'=> $userAddress->country_id ?? null,   
                                'province_id'=> $userAddress->province_id ?? null,   
                                'city_id' => $userAddress->city_id ?? null,  
                                'barangay_id'=> $userAddress->barangay_id ?? null,   
                                'lat' => $userAddress->lat ?? null,  
                                'long' => $userAddress->long ?? null,  
                        )
                    );

                    try {
                        
                        $order = Orders::firstOrCreate([
                                'cart_id' => $cart_id,
                            ], 
                            array(
                                'user_id' => $cart->user_id,
                                'cart_id' => $cart_id,
                                'submitted_at' => now(),
                                'order_status_id' => 1,
                                'partner_id' => $cart->partner_id
                        ));

                        if ($order) {

                            OrderProcess::updateOrCreate([
                                'status_id' => 1,
                                'order_id' => $order->id
                            ]);

                            if ($order->wasRecentlyCreated) {
                                event(new SendPushNotificationEvent($order));
                            }
                        }

                    } catch (Exception $e) {
                        
                        \Log::error('Error creating order for cart ID: ' . $cart_id, [
                            'exception' => $e->getMessage(),
                        ]);
                    
                    }

                    // regenerate session 
                    // redirect url 
                    // message 
                    $data['message'] = "Successfully processed order";
                    // with status 
                    $data['status'] = 1;
                    $data['order_id'] = $order->id;
                    $data['order'] = $order;
                    $data['order_no'] = $order->cart->order_no;
                    $data['payment'] = $order->cart->payment;

                    \Log::info(['summary' => $cart->cartItemSummary(), 'order' => $order, 'cart' => $cart]);

                    $data['summary'] = $cart->cartItemSummary();
                    
                    $session_id = Session::getId();
                    Session::setId($session_id); ///

                    $data['session_id'] = $session_id;  // Need to send another 

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
        // just makeing it static for now to avoid sending sms to the user
        $smsCode = 12345;

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

    public function couponCode(Request $request) {


        $session_id = $request->input('session_id');
        $user = $request->user();

		$request->validate(['coupon' => ['required', 'string', 'max:255']]);
		$cart = Cart::query()->whereSessionId($session_id)->first();

		if (! $cart) {
			return response()->json(['status' => 0, 'message' => 'Cart not found.'], 200);
		}

		$code = strtoupper(trim((string) $request->input('coupon')));
		$coupon = Coupon::query()
			->available($cart->partner_id ? (int) $cart->partner_id : null)
			->whereRaw('UPPER(coupon) = ?', [$code])
			->first();

		if (! $coupon) {
			return response()->json([
				'status' => 0,
				'message' => 'The coupon code is invalid, inactive, expired, or unavailable for this merchant.',
			], 200);
		}

		if ($coupon->hasReachedUsageLimit()) {
			return response()->json([
				'status' => 0,
				'message' => 'This coupon has reached its usage limit.',
			], 200);
		}

		$subtotal = $this->discountableSubtotal($cart);
		if ($coupon->condition !== null && $subtotal < (float) $coupon->condition) {
			return response()->json([
				'status' => 0,
				'message' => 'This coupon requires a minimum order of ₱'.number_format((float) $coupon->condition, 2).'.',
			], 200);
		}

		$discount = $coupon->discountFor($subtotal);
		$cart->discount_amount = $discount;
		$cart->discount_code = $coupon->coupon;
		$cart->save();

		return response()->json([
			'status' => 1,
			'message' => 'Coupon applied successfully.',
			'coupon' => $this->couponPayload($coupon),
			'discount_amount' => $discount,
			'cart_summary' => $this->checkoutSummary($cart->fresh()),
		], 200);
    	
    }

	public function availableCoupons(Request $request)
	{
		$cart = Cart::query()->whereSessionId(Session::getId())->first();
		$partnerId = $cart?->partner_id ?: $request->integer('partner_id') ?: null;

		$coupons = Coupon::query()
			->available($partnerId ? (int) $partnerId : null)
			->orderBy('valid_until')
			->get()
			->reject(fn (Coupon $coupon) => $coupon->hasReachedUsageLimit())
			->map(fn (Coupon $coupon) => $this->couponPayload($coupon))
			->values();

		return response()->json(['data' => $coupons]);
	}

    private function couponPayload(Coupon $coupon): array
	{
		return [
			'id' => $coupon->id,
			'code' => $coupon->coupon,
			'partner_id' => $coupon->partner_id,
			'scope' => $coupon->partner_id ? 'partner' : 'pahatud',
			'discount_value' => $coupon->discount_value !== null ? (float) $coupon->discount_value : null,
			'discount_percentage' => $coupon->discount_percentage !== null ? (float) $coupon->discount_percentage : null,
			'minimum_order' => $coupon->condition !== null ? (float) $coupon->condition : null,
			'valid_from' => $coupon->valid_from?->toIso8601String(),
			'valid_until' => ($coupon->valid_until ?? $coupon->valid_at)?->toIso8601String(),
			'limit' => $coupon->limit,
		];
	}
    
    private function discountableSubtotal(Cart $cart): float
    {
        return round((float) DB::table('cart_details')->where('cart_id', $cart->id)->get()->sum(function ($item) {
            return max(0, (int) $item->qty * (
                (float) $item->price + (float) $item->variance_total - (float) $item->discount_amount
            ));
        }), 2);
    }

    private function checkoutSummary(Cart $cart): array
	{
		return $cart->cartItemSummary();
	}


}
