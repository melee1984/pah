<?php

namespace App\Http\Controllers\Api\Mobile\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Bookings\BookingDropoffInfo;
use App\Model\Bookings\BookingPickupInfo;
use App\Model\Bookings\Bookings;
use Validator;

class AddressesController extends Controller
{
  	public function insertNewAddress(Request $request) {

	   	$rules = [
	      'lat'     => 'required',
	      'long'    => 'required',
	      'name'    => 'required',
	      'address' => 'required',
	      'mobile' => 'required',
	    ];

	    $validator = Validator::make($request->all(), $rules);

	    if ($validator->fails()) {
	     
		    return response()->json([
		    	'message' => $validator->messages()->first(),
		    ]);
	      
	    } 
	    else {
	    	$user = $request->user();

	    	if ($request->input('option') == 1) {
	    		$address = BookingPickupInfo::updateOrCreate([
	    				'latitude' => $request->input('lat'), 
					    'longtitude' => $request->input('long'),
					    'address' =>$request->input('address'),
					    'name' => $request->input('name'),
					    'mobile' =>$request->input('mobile'),
					    'landmark' => $request->input('landmark'),
					    'user_id' => $user->id,
	    		]);

	    	}
	    	else {


	    		$address = BookingDropoffInfo::updateOrCreate([
	    				'latitude' => $request->input('lat'), 
					    'longtitude' => $request->input('long'),
					    'address' =>$request->input('address'),
					    'name' => $request->input('name'),
					    'mobile' =>$request->input('mobile'),
					    'landmark' => $request->input('landmark'),
					    'user_id' => $user->id,
	    		]);


	    	}

	    	$data['address'] = $address;
	    	$data['status'] = 1;
	    	$data['message'] = "Successfully created new address";

	    }

	    return response()->json($data, 200);


	}

	



}
