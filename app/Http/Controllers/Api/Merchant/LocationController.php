<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Auth;
use App\Location;
use Str;

class LocationController extends Controller
{
     //
    public function getList() 
    {	
    	$data = array();

    	$location = Location::wherePartnerId(Auth::User()->merchant->id)
    					->orderby('address_1','asc')
    					->paginate(50);

    	$data['location'] = $location;

    	return response()->json($data, 200);
    }

    public function updateStatus(Location $location, Request $request) {

    	$data = array();

    	$location->active = $location->active ? 0 : 1;
		$status = $location->save();	

		if ($status) {
			$data['message'] = "Successfully updated status";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}

    	return response()->json($data, 200);

    }

    public function store(Request $request) {

    	$validatedData = $request->validate([
			'address_1' => 'required',
			'zip' => 'required|max:75',
			'city' => 'required|max:75',
			'mobile' => 'required|max:75',
			'telephone' => 'required|max:75',
	    ]);

		$status = Location::create([
			'partner_id'	=> Auth::User()->merchant->id,
	    	'address_1' => $request->input('address_1'),
	    	'address_2' => $request->input('address_2'), 
	    	'zip' => $request->input('zip'),
	    	'city' => $request->input('city'),
	    	'mobile' => $request->input('mobile'),
	    	'telephone' => $request->input('telephone'),
	    	'active'	=> $request->input('active'),
		]);
	   
		if ($status) {
			$data['message'] = "Successfully added new location";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);

    }	
    public function update(Location $location, Request $request) {

    	$validatedData = $request->validate([
			'address_1' => 'required',
			'zip' => 'required|max:15',
			'city' => 'required|max:15',
			'mobile' => 'required|max:25',
			'telephone' => 'required|max:25',
	    ]);
    		
		$location->partner_id = Auth::User()->merchant->id;
		$location->address_1 = $request->input('address_1');
		$location->address_2 = $request->input('address_2');
		$location->zip_code = $request->input('zip');
		$location->city = $request->input('city');
    	$location->mobile = $request->input('mobile');
    	$location->telephone = $request->input('telephone');
    	$location->active = $request->input('active');

    	$status = $location->save();

		if ($status) {
			$data['message'] = "Successfully updated location";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);

    }	

    public function destroy(Location $location) {

    	$data = array();
    	$delete = Location::find($location->id);
    	$status = $delete->delete();

    	if ($status) {
			$data['message'] = "Successfully delete location";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
	
		return response()->json($data, 200);
	}
}
