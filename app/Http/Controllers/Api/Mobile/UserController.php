<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Model\User\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function getAddresses(Request $request) {

    	$data = array();

    	$user = $request->user('api');

    	$addresses = UserAddress::whereUserId($user->id)
    							->whereActive(1)->get();
		$addresses->load('city');

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
			'city' => ['required', 'string', 'max:120', Rule::exists('cities', 'name')],
			'latitude' => 'required|numeric',
			'longitude' => 'required|numeric',
		], [
			'city.exists' => "The city you've selected is invalid.",
		]);
		
		$address = new UserAddress();
		$address->user_id = $user->id;
		$address->title =  $request->input('title');
		$address->address_1 = $request->input('address');
		$address->city_name = trim($request->input('city'));
		$address->lat = $request->input('latitude');
		$address->long = $request->input('longitude');
		$address->active = 1;
		$address->default = 0;

		if ($address->save()) {
			$serviceable = (bool) $address->load('city')->city?->active;
			if ($serviceable) {
				$address->makeDefault();
			}

			$data['status'] = 1;
			$data['serviceable'] = $serviceable;
			$data['address'] = $address->load('city');
			$data['addresses'] = UserAddress::query()
				->whereUserId($user->id)
				->whereActive(1)
				->with('city')
				->get();
		}

		return response()->json($data, 200);
	}

    public function updateAddressStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'active' => ['required', 'boolean'],
            'default' => ['required', 'boolean'],
        ]);

        if ($validated['default'] && ! $validated['active']) {
            throw ValidationException::withMessages([
                'default' => 'A default address must be active.',
            ]);
        }

        $user = $request->user();

        $address = DB::transaction(function () use ($user, $validated) {
            $address = UserAddress::query()
                ->where('user_id', $user->id)
                ->whereKey($validated['address_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $address->forceFill([
                'active' => $validated['active'],
                'default' => false,
            ])->save();

            if ($validated['default']) {
                $address->makeDefault();
            }

            return $address->refresh()->load('city');
        });

        return response()->json([
            'status' => 1,
            'address' => $address,
            'addresses' => UserAddress::query()
                ->whereUserId($user->id)
                ->whereActive(true)
                ->with('city')
                ->get(),
        ]);
    }
}
