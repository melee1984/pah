<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Auth;
use App\PartnerLocation;
use App\Model\Cart;
use App\PartnerLocationCheckoutOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Str;

class LocationController extends Controller
{
     //
    public function getList() 
    {	
    	$data = array();

		$location = PartnerLocation::with(['checkoutOptions', 'diningTables' => function ($query) {
							$query->orderBy('name');
						}])
						->wherePartnerId(Auth::User()->merchant->id)
    					->orderby('address_1','asc')
    					->paginate(50);

    	$data['location'] = $location;

    	return response()->json($data, 200);
    }

    public function updateStatus(PartnerLocation $location, Request $request) {

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
			'latitude' => ['required', 'numeric', 'between:-90,90'],
			'longtitude' => ['required', 'numeric', 'between:-180,180'],
			'checkout_options' => ['required', 'array', 'min:1'],
			'checkout_options.*' => ['required', 'string', 'distinct', Rule::in(Cart::FULFILLMENT_TYPES)],
	    ]);

		$status = DB::transaction(function () use ($request) {
			$location = PartnerLocation::create([
				'partner_id' => Auth::User()->merchant->id,
				'address_1' => $request->input('address_1'),
				'address_2' => $request->input('address_2'),
				'zip_code' => $request->input('zip'),
				'city' => $request->input('city'),
				'mobile' => $request->input('mobile'),
				'telephone' => $request->input('telephone'),
				'latitude' => $request->input('latitude'),
				'longtitude' => $request->input('longtitude'),
				'active' => $request->boolean('active'),
			]);

			$this->syncCheckoutOptions($location, $request->input('checkout_options'));

			return $location;
		});
	   
		if ($status) {
			$data['message'] = "Successfully added new branch";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);

    }	
    public function update(PartnerLocation $location, Request $request) {

		$validatedData = $request->validate([
			'address_1' => 'required',
			'zip' => 'required|max:15',
			'city' => 'required|max:15',
			'mobile' => 'required|max:25',
			'telephone' => 'required|max:25',
			'latitude' => ['required', 'numeric', 'between:-90,90'],
			'longtitude' => ['required', 'numeric', 'between:-180,180'],
			'checkout_options' => ['required', 'array', 'min:1'],
			'checkout_options.*' => ['required', 'string', 'distinct', Rule::in(Cart::FULFILLMENT_TYPES)],
	    ]);

		abort_unless((int) $location->partner_id === (int) Auth::User()->merchant->id, 403);
    		
		$status = DB::transaction(function () use ($location, $request) {
			$location->address_1 = $request->input('address_1');
			$location->address_2 = $request->input('address_2');
			$location->zip_code = $request->input('zip');
			$location->city = $request->input('city');
			$location->mobile = $request->input('mobile');
			$location->telephone = $request->input('telephone');
			$location->latitude = $request->input('latitude');
			$location->longtitude = $request->input('longtitude');
			$location->active = $request->boolean('active');
			$location->save();

			$this->syncCheckoutOptions($location, $request->input('checkout_options'));

			return true;
		});

		if ($status) {
			$data['message'] = "Successfully updated branch";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);

    }	

    public function destroy(PartnerLocation $location) {

    	$data = array();
		$delete = PartnerLocation::find($location->id);
    	$status = $delete->delete();

    	if ($status) {
			$data['message'] = "Successfully deleted branch";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
	
		return response()->json($data, 200);
	}

	private function syncCheckoutOptions(PartnerLocation $location, array $enabledOptions): void
	{
		foreach (Cart::FULFILLMENT_TYPES as $type) {
			PartnerLocationCheckoutOption::query()->updateOrCreate([
				'partner_location_id' => $location->id,
				'type' => $type,
			], [
				'active' => in_array($type, $enabledOptions, true),
			]);
		}
	}
}
