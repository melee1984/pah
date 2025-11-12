<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Auth;
use Str;

use Carbon\Carbon;
use Image;
use App\LibraryTimings;
use App\Partners;
use App\Sector;
use DB;
use App\PartnerSector;

class ProfileController extends Controller
{
	public function getList() {	

    	$data = array();
    	$merchant = Auth::User()->merchant;
    	$timings = LibraryTimings::whereActive(1)->get();
    	// 
    	$timings->makeHidden(['created_at', 'updated_at']);

    	// $merchant = Partners::imgCheck($merchant);
    	$merchant = Partners::imgCheck($merchant, 'logo');
    	$merchant = Partners::imgCheck($merchant, 'banner');

    	$sectors = DB::select('select 
    						*, (select count(partner_sector.id) from partner_sector where partner_sector.sector_id = sector.id and partner_sector.partner_id = '. $merchant->id .') selected 

    					from sector');

    	// $sectors = Sector::whereActive(1)->get();

    	foreach($timings as $time) {
			$time->add = array();
    	}

    	$data['sectors'] = $sectors;
    	$data['profile'] = $merchant;
    	$data['timings'] = $timings;

    	return response()->json($data, 200);
    }

    public function update(Request $request) {

    	$validatedData = $request->validate([
			'restaurant_name' => 'required|max:150',
			'email'	=> 'required',
			'telephone'	=> 'required',
			'mobile'	=> 'required',
	        'address' => 'required',
	        'city' => 'required',
	    ]);

    	$merchant = Partners::find(Auth::User()->merchant->id);

    	$merchant->active = $request->input('active');
    	$merchant->store_open = $request->input('store_open');
		$merchant->restaurant_name = $request->input('restaurant_name');
		$merchant->slug = Str::slug($request->input('restaurant_name'),'-');
		$merchant->email = $request->input('email');
		$merchant->telephone = $request->input('telephone');
		$merchant->mobile = $request->input('mobile');
		$merchant->address = $request->input('address');
		$merchant->slug = Str::slug($request->input('restaurant_name'),'-');
		$merchant->city = $request->input('city');
		$merchant->search_string = Str::lower($merchant->restaurant_name) .", ". Str::lower($request->input('city'));
		$merchant->description = $request->input('description');

		$status = $merchant->save();

		if ($status) {
			$data['message'] = "Successfully updated profile";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "Unable to upload image. Please refresh the page and try again. Thank you.";
			$data['status'] = 0;
		}

		return response()->json($data, 200);

    }
	public function uploadImageBanner(Request $request) {

		request()->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

    	$data = array();
    	$image = $request->file('file');
        $filename   = now()->toDateString()."-".$image->getClientOriginalName();
       //Fullsize
       //
       	try {
       		$this->unlinkImageBanner();
			try {

				$originalDirectory = public_path().'/uploads/user/'.Auth::User()->merchant->id . "/banner";

	    		if (!file_exists($originalDirectory)) {
		            mkdir($originalDirectory, 0777, true);
		        }
		       
			} catch (Exception $e) {
				\Log('Error when uploading image ProfileController Merchant Uploading Image');
				$data['message'] = "Uploading banner image failed.";
				$data['status'] = 0;
				return response()->json($data, 200);
			}
	        
	       	$image->move($originalDirectory."/",$filename);

			$image_resize = Image::make($originalDirectory."/".$filename);

			Auth::User()->merchant->banner = $filename;
			$status = Auth::User()->merchant->save();

			if ($status) {
				$data['banner'] = asset('uploads/user/'.Auth::User()->merchant->id."/banner/".$filename);
				$data['message'] = "Successfully uploaded banner image";
				$data['status'] = 1;
			}
			else {
				$data['message'] = "Unable to upload image. Please refresh the page and try again. Thank you.";
				$data['status'] = 0;
			}

       	} catch (Exception $e) {
       		$data['message'] = "An error occur:" . $e;
			$data['status'] = 0;
       	}
        
		return response()->json($data, 200);

	}
	public function uploadImage(Request $request) {

		request()->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        // 'file' => 'required|image|mimes:jpeg,png,jpg|max:2048|dimensions:max_width=500,max_height=500',

    	$data = array();
    	$image = $request->file('file');
        $filename   = now()->toDateString()."-".$image->getClientOriginalName();
       //Fullsize
       //
       	try {
       		
       		$this->unlinkImage();

			try {

			$originalDirectory = public_path().'/uploads/user/'.Auth::User()->merchant->id . "";
       		$originalDirectoryThumb = public_path().'/uploads/user/'.Auth::User()->merchant->id ."/thumb"."";

    		if (!file_exists($originalDirectory)) {
	            mkdir($originalDirectory, 0777, true);
	        }

			} catch (Exception $e) {
				\Log('Error when uploading image ProfileController Merchant');
				$data['message'] = "Uploading image failed";
				$data['status'] = 0;
				return response()->json($data, 200);
			}
	        
	       	$image->move($originalDirectory."/",$filename);

			$image_resize = Image::make($originalDirectory."/".$filename);

			Auth::User()->merchant->img = $filename;
			$status = Auth::User()->merchant->save();

			if ($status) {
				$data['img'] = asset('uploads/user/'.Auth::User()->merchant->id."/".$filename);
				$data['message'] = "Successfully uploaded new image";
				$data['status'] = 1;
			}
			else {
				$data['message'] = "Unable to upload image. Please refresh the page and try again. Thank you.";
				$data['status'] = 0;
			}

       	} catch (Exception $e) {
       		$data['message'] = "An error occur:" . $e;
			$data['status'] = 0;
       	}
        
		return response()->json($data, 200);
	}

	public function unlinkImageBanner() {

		$originalDirectory = public_path().'/uploads/user/'.Auth::User()->merchant->id . "/banner";

   		// Removing existing image from here 
   		if (Auth::User()->merchant->img !="") {
   			try {	
				$deleteFile = $originalDirectory."/".Auth::User()->merchant->banner;
				\File::delete($deleteFile);
   			} catch (Exception $e) {

   			}

       	}

	}
	public function unlinkImage() {

			$originalDirectory = public_path().'/uploads/user/'.Auth::User()->merchant->id;

       		// Removing existing image from here 
       		if (Auth::User()->merchant->img !="") {
       			try {	
			
					$deleteFile = $originalDirectory."/".Auth::User()->merchant->img;
					\File::delete($deleteFile);
       			} catch (Exception $e) {
       			}

       			try {	
					
					$deleteFileThumbnail = $originalDirectory."/thumb/".Auth::User()->merchant->img;
	    			\File::delete($deleteFileThumbnail);
					
       			} catch (Exception $e) {
       			}
	       	}

	}

	public function updateSector(Sector $sector, Request $request) 
	{	
		$partnerSector = PartnerSector::wherePartnerId(Auth::user()->merchant->id)
							->whereSectorId($sector->id)
							->first();

		if (!$partnerSector) {
			$partnerSector = new PartnerSector;
			$partnerSector->partner_id = Auth::user()->merchant->id;
			$partnerSector->sector_id = $sector->id;
			$partnerSector->save();
		}
		else {
			$status = $partnerSector->delete();
		}

		$data['status'] = 1;
		$data['message'] = "Updated sector";
		
		return response()->json($data, 200);

	}

}
