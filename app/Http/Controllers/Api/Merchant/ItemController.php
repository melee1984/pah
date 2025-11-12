<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Products;
use App\ProductItemHeader;
use App\User;
use Auth;
use Str;

use Image;

class ItemController extends Controller
{	
	public function __construct()
    {
        $this->middleware('auth');
    }
	/**
	 * [index description]
	 * @return [type] [description]
	 */
    public function index() {
        return Article::all();
    }
 	
 	/**
 	 * [show description]
 	 * @param  [type] $id [description]
 	 * @return [type]     [description]
 	 */
    public function show($id) {
        return Article::find($id);
    }
    public function fetchData() {
    	
    	$data = array();

		$products = Products::whereUserId(Auth::User()->id)
    					->orderby('title','asc')
    					->paginate(50);

    	foreach($products as $product) {
    		$product =  Products::productImageCheck($product);
    	}

		$categories = Auth::User()->categories;

		$data['merchant'] = Auth::User()->merchant;
    	$data['products'] = $products;
    	$data['categories'] = $categories;

    	return response()->json($data, 200);
    }
    public function fetchDataByCategory(Request $request) {

    	$data = array();
    	
    	$products = Products::whereUserId(Auth::User()->id)->whereCategoryId($request->input('category_id'))
    					->orderby('title','asc')
    					->paginate(50);
    	foreach($products as $product) {
    		$product =  Products::productImageCheck($product);
    	}

    	$data['products'] = $products;

    	return response()->json($data, 200);
    }
    /**
     * [store description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function store(Request $request)
    {		
    	$validatedData = $request->validate([
			'title' => 'required|max:75',
			'description'	=> 'required',
			'category_id'	=> 'required',
	        'price' => 'required|numeric',
	    ]);

    	$percentage = .15;

		if (Auth()->User()->merchant->percentage == 15) {
			$percentage = .15;
		} else if (Auth()->User()->merchant->percentage == 10) {
			$percentage = .10;
		}

		$product = Products::create([
			'user_id'	=> Auth::User()->id,
	    	'title' => $request->input('title'),
	    	'description' => $request->input('description'),
	    	'location_id' => $request->input('location_id'),
	    	'slug'		=> Str::slug($request->input('title')),
	    	'category_id' => $request->input('category_id'),
	    	'price' => $request->input('price'),
	    	'markdown_price' => $request->input('markdown_price'),
	    	'price_comm' => $request->input('price') * $percentage,
	    	'markdown_price_comm' => $request->input('markdown_price') * $percentage,
	    	'active'	=> $request->input('active'),
		]);
	   
		if ($product) {
			$data['message'] = "Successfully added new item";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		// $status = Mail::to($contactus->email)
		// 				->send(new ContactUsMail($contactus));	
	
		return response()->json($data, 200);
    }

    /**
     * [update description]
     * @param  Request $request [description]
     * @param  [type]  $id      [description]
     * @return [type]           [description]
     */
    public function update(Request $request, Products $product)
    {
       $validatedData = $request->validate([
			'title' => 'required|max:75',
			'description'	=> 'required',
			'category_id'	=> 'required',
	        'price' => 'required|numeric',
	    ]);

		$product->user_id = Auth::User()->id;
		$product->title  = $request->input('title');
		$product->description = $request->input('description');
		$product->category_id = $request->input('category_id');
		$product->slug =  Str::slug($request->input('title'));
		$product->price = $request->input('price');
		$product->markdown_price = $request->input('markdown_price');
		$product->active = $request->input('active');

		$percentage = .15;

		if (Auth()->User()->merchant->percentage == 15) {
			$percentage = .15;
		} else if (Auth()->User()->merchant->percentage == 10) {
			$percentage = .10;
		}

		$product->price_comm = $request->input('price') * $percentage;
		$product->markdown_price_comm =$request->input('markdown_price') * $percentage;
	
		$status = $product->save();
		
		if ($status) {
			$data['message'] = "Successfully updated item";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		// $status = Mail::to($contactus->email)
		// 				->send(new ContactUsMail($contactus));	
	
		return response()->json($data, 200);
    }	

    public function status(Request $request, Products $product)
    {
       	$validatedData = $request->validate([
			'active' => 'required',
	    ]);
		
		$request->input('active')?$product->active=0:$product->active=1;
		$status = $product->save();

		if ($status) {
			$data['message'] = "Successfully updated status";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		// $status = Mail::to($contactus->email)
		// 				->send(new ContactUsMail($contactus));	
	
		return response()->json($data, 200);
    }

    /**
     * [delete description]
     * @param  Request $request [description]
     * @param  [type]  $id      [description]
     * @return [type]           [description]
     */
    public function destroy(Request $request, Products $product)
    {	
    	$data = array();

		if ($product) {
			$this->unlinkImage($product);
			$product->delete();
			$data['message'] = "Successfully deleted item";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}

		return response()->json($data, 200);
    }

    public function requirementList() {
    	
    	$categories = Auth::User()->categories;
    	$locations = Auth::User()->merchant->locations;

		return response()->json(compact('categories', 'locations'), 200);

    }

    public function uploadImage(Request $request, Products $product) {

    	request()->validate([
			'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

    	$data = array();
    	$image = $request->file('file');
        $filename   = now()->toDateString()."-".$image->getClientOriginalName();
       
       	try {

       		$this->unlinkImage($product);
			
       		$originalDirectory = public_path().'/uploads/'.$product->id;
       		// $originalDirectoryThumb = public_path().'/uploads/'.$product->id ."/thumb";

    		try {
    			
    			if (!file_exists($originalDirectory)) {
		            mkdir($originalDirectory, 0755, true);
		        }

    		} catch (Exception $e) {
    			
    			\Log('Error when uploading image ');

				$data['message'] = "Uploading image failed";
				$data['status'] = 0;
				return response()->json($data, 200);

    		}

	        // if (!file_exists($originalDirectoryThumb)) {
	        //     mkdir($originalDirectoryThumb, 0755, true);
	        // }
	        
	       	$image->move($originalDirectory."/",$filename);

			// $image_resize = Image::make($originalDirectory."/".$filename);

			// $image_resize->resize(200, 200, function ($constraint) {
			//     $constraint->aspectRatio();
			//     $constraint->upsize();
			// });

			// $image_resize->save($originalDirectoryThumb."/".$filename);
	   			
			$product->img = $filename;
			$status = $product->save();

			$productUpdate = Products::productImageCheck($product);

			if ($status) {
				$data['img'] = $productUpdate->imgname;
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

	public function unlinkImage(Products $product) {

			$originalDirectory = public_path().'/uploads/'.$product->id;
       		// $originalDirectoryThumb = public_path().'/uploads/'.$product->id ."/thumb";

       		// Removing existing image from here 
       		if ($product->img !="") {
				$deleteFile = $originalDirectory."/".$product->img;
				// $deleteFileThumbnail = $originalDirectory."/thumb/".$product->img;
    			\File::delete($deleteFile);
    			// \File::delete($deleteFileThumbnail);
	       	}

	}

	public function updateVariantStatus(ProductItemHeader $productItemHeader, Request $request) {
		// dd($productItemHeader);
		// 
		if ($request->input('option') == 1) {
			$productItemHeader->active = $productItemHeader->active?0:1;
		}
		elseif ($request->input('option') == 2) {
			$productItemHeader->is_required = $productItemHeader->is_required?0:1;
			$productItemHeader->is_multiple = 0;
		}
		elseif ($request->input('option') == 3) {
			$productItemHeader->is_required = 0;
			$productItemHeader->is_multiple = $productItemHeader->is_multiple?0:1;
		}

		$status = $productItemHeader->save();

		$data['message'] = "Successfully updated field";
		$data['status'] = 1;

		return response()->json($data, 200);


	}	

}
