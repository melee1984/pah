<?php

namespace App\Http\Controllers\Api\User\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use Auth;
use Session;
use Validator;
use App\Model\User\UserAddress;

use App\Model\Cart;
use App\Coupon;

class CheckoutController extends Controller
{
    public function __construct(){
		//
	}
	/**
	 * Checkout process 
	 * @return [type] [description]
	 */
    public function checkout() {
    	die();
    }

    public function deleteAddress(Request $request) {

    	$data = array();
    	$status = false;

    	$rs = UserAddress::whereUserId(Auth::User()->id)->whereId($request->input('id'))->first();

    	if ($rs) {
    		$status = $rs->delete();	
    	}
    	
    	if ($status) {
			$data['message'] = "Successfully deleted address";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
	
		return response()->json($data, 200);

    }

	public function updateAddressSelected(Request $request)
	{
		// Validation
		$validator = Validator::make($request->all(), [
			'address' => 'required',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status'  => 0,
				'message' => "Missing required information.",
			], 200);
		}

		// Fetch user's address
		$userAddress = UserAddress::where('user_id', Auth::id())
			->find($request->address);

		if (!$userAddress) {
			return response()->json([
				'status'  => 0,
				'message' => "Address not found.",
			], 200);
		}

		// Fetch cart
		$cart = Cart::whereSessionId(Session::getId())->first();

		if (!$cart) {
			return response()->json([
				'status'  => 0,
				'message' => "Cart not found.",
			], 200);
		}

		// Prepare data for cart address
		$data = $userAddress->only([
			'address_1',
			'address_2',
			'zip_code',
			'mobile',
			'landmark',
			'country_id',
			'province_id',
			'city_id',
			'barangay_id',
			'lat',
			'long'
		]);

		$data['user_id'] = Auth::id();

		// Update or create cart address
		$address = $cart->address()->updateOrCreate(
			['cart_id' => $cart->id],
			$data
		);

		$cart->address_id = $userAddress->id;
		$cart->save();

		// Build response
		return response()->json([
			'status'    => $address ? 1 : 0,
			'message'   => $address
				? "Successfully updated record"
				: "We've encountered an issue during the process. Please refresh the page and try again.",
			'addresses' => $address ? $userAddress : null,
		], 200);
	}

    /**
     * [updateAddress description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updateAddress(Request $request, UserAddress $userAddress) {
    	
    	 $rules = [
	    	'title'=>'required',
	    	'address'=>'required',
	    	'latitude' => 'required',
	    	'longtitude' => 'required',
	    ];

	    $validator = Validator::make($request->all(), $rules);

	    if ($validator->fails()) {
	      // Validation failed
	      return response()->json([
	        'status' => 0,
	        'message' => "Missing information requred"
	      ]);
	    } 
	    else {

	    	$userAddress->address_1 = $request->address_1;
	    	$userAddress->landmark = $request->landmark;
	    	$userAddress->title = $request->title;
	    	$userAddress->lat = $request->latitude;	
	    	$userAddress->long = $request->longtitude;

			$cart = Cart::whereSessionId(Session::getId())->first();
			
	    	$status = $userAddress->save();

	    	if ($status) {
				$data['message'] = "Successfully updated record";
				$data['status'] = 1;
				$data['addresses'] = Auth::User()->addresses;
			}
			else {
				$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
				$data['status'] = 0;	
			}
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

	    	$session_id = Session::getId();
			$cart = Cart::whereSessionId($session_id)->first();
			
			$userAddress = UserAddress::updateOrCreate(
			    ['address_1' => $request->input('address'), 'landmark' => $request->input('landmark'), 'title' => $request->input('title'), 'user_id' => Auth::User()->id],
			    ['active' => 1, 'address_1' => $request->input('address'), 'landmark' => $request->input('landmark'), 'title' => $request->input('title'), 'lat' => $request->input('latitude'), 'long' => $request->input('longtitude'), 'user_id' => Auth::User()->id, 'status' => ' 1']);

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
			$data['addresses'] = Auth::User()->addresses;
			$data['address_id'] = $userAddress->id;
			
		}

		return response()->json($data, 200);

    }

    /**
     * [updateProfile description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updateProfile(Request $request) {
    
    	$data = array();	
    	$data['status'] = 0;
        $data['message'] = "";  

	    $rules = [
	    	'firstname'=>'required',
	    	'lastname'=>'required',
	     	'mobile'=>'required'
	    ];

	    $session_id = Session::getId();

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

	    	$user = User::find(Auth::User()->id);
	    	$user->firstname = $request->input('firstname');
	    	$user->lastname = $request->input('lastname');
	    	$user->mobile = $request->input('mobile');
	    	// $user->email = $request->input('email');
	    	$status = $user->save();

	    	if ($status) {
	    		$data['status'] = 1;
	    		$data['message'] = "Succesfully process book";
	    	}
	    }

		return response()->json($data, 200);

    }

    public function couponCode(Request $request) {

    	$data = array();
    	$data['status'] = 0;

    	$coupon = Coupon::whereActive(1)
    				->whereCoupon($request->input('coupon'))
    				->first();

    	if ($coupon) {
    		
    		$session_id = Session::getId();
			$cart = Cart::whereSessionId($session_id)->first();

			$cart->discount_amount = $coupon->discount_value;
			$cart->discount_code = $coupon->coupon;
			$cart->save();
			$data['status'] = 1;
    	}
    	else {
    		$data['message'] = "The coupon code that you entered does not exist or might be expired.";
    	}

		return response()->json($data, 200);
    	
    }

}