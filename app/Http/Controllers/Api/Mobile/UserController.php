<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\User\UserAddress;

class UserController extends Controller
{
    public function getAddresses(Request $request) {

    	$data = array();

    	$user = $request->user();

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
}
