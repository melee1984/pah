<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\User\UserAddress;

class UserController extends Controller
{
    public function getAddresses(Request $request) {

    	$data = array();

    	$user = $request->user('api');

    	$addresses = UserAddress::whereUserId($user->id)
    							->whereActive(1)->get();

    	$data['addresses'] = $addresses;

    	return response()->json($data, 200);

    }

    public function deleteAddress(Request $request) {

    	$data = array();
    	$data['status'] = 0;
    	$user = $request->user();
    	
    	$userAddress = UserAddress::find($request->input('address_id'));

    	if ($userAddress) {
    		$status = $userAddress->delete();

    		if ($status) {
    			$data['status'] = 1;
    		}

    		$addresses = UserAddress::whereUserId($user->id)
    							->whereActive(1)->get();

    		$data['addresses'] = $addresses;


    	}

    	return response()->json($data, 200);
    	
    }

	public function addAddress(Request $request) {

		$data = array();
		$data['status'] = 0;
		$user = $request->user();

		$request->validate([
			'title' => 'required|string|max:255',
			'address' => 'required|string|max:255',
			'latitude' => 'required|numeric',
			'longitude' => 'required|numeric',
		]);
		
		$user->addresses()->update(['default' => 0]);
		
		$address = new UserAddress();
		$address->user_id = $user->id;
		$address->title =  $request->input('title');
		$address->address_1 = $request->input('address');
		$address->lat = $request->input('latitude');
		$address->long = $request->input('longitude');
		$address->active = 1;
		$address->default = 1;

		if ($address->save()) {
			$data['status'] = 1;
		}

		return response()->json($data, 200);
	}
}
