<?php

namespace App\Http\Controllers\Api\User\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use Auth;
use Session;
use Validator;
use Illuminate\Support\Facades\DB;
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
		$request->validate(['coupon' => ['required', 'string', 'max:255']]);
		$cart = Cart::query()->whereSessionId(Session::getId())->first();

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
			->map(fn (Coupon $coupon) => $this->couponPayload($coupon))
			->values();

		return response()->json(['data' => $coupons]);
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
		$items = DB::table('cart_details')->where('cart_id', $cart->id)->get();
		$subtotal = (float) $items->sum(fn ($item) => (int) $item->qty * ((float) $item->price + (float) $item->variance_total));
		$itemDiscount = (float) $items->sum(fn ($item) => (int) $item->qty * (float) $item->discount_amount);
		$discount = $itemDiscount + (float) $cart->discount_amount;
		$deliveryFee = (float) $cart->delivery_fee;

		return [
			'sub_total' => number_format($subtotal, 2),
			'delivery_fee' => number_format($deliveryFee, 2),
			'discount' => number_format($discount, 2),
			'total' => number_format(max(0, $subtotal + $deliveryFee - $discount), 2),
			'qty' => (int) $items->sum('qty'),
			'currency' => '₱',
		];
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

}
