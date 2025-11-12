<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\ProductItemHeader;
use App\ProductItemDetails;
use App\Products;
use User;
use Auth;

class VariantController extends Controller
{	

	public function getList(Products $product) {

		$data = array();

		$product = Products::find($product->id);
		$product->productImageCheck($product);

		if ($product->variants()->exists()) {
			foreach($product->variants as $variant) {
			    $variant->imgCheck($variant);
			}
		}

		$data['product'] = $product;

		return response()->json($data, 200);

	}
    //
    public function store(Request $request, Products $product) {

    	$validatedData = $request->validate([
			'title' => 'required|max:75',
	    ]);
		$status = ProductItemHeader::create([
			'product_id'	=> $request->input('product_id'),
	    	'title' => $request->input('title'),
	    	'active' => $request->input('active'),
	    	'is_required' => $request->input('required'),
	    	'sorting'	=> $request->input('sorting'),
	    	'is_multiple'	=> $request->input('is_multiple'),
	    ]);
	   	
	   	if ($status) {
			$data['message'] = "Successfully added new variant";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);
    }

    public function storeDetails(Request $request, ProductItemHeader $productItemHeader) {

    	$validatedData = $request->validate([
			'title' => 'required|max:75',
			'price' => 'required',
	    ]);

		$status = ProductItemDetails::create([
			'product_header_id'	=> $productItemHeader->id,
	    	'title' => $request->input('title'),
	    	'active' => $request->input('active'),
	    	'markdown_price'	=> $request->input('markdown_price'),
	    	'price_comm'	=> $request->input('price') * .15,
	    	'price'	=> $request->input('price'),
	    ]);
	   	
	   	if ($status) {
			$data['message'] = "Successfully added new record";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);
    }

    public function updateDetails(Request $request, ProductItemHeader $productItemHeader) {

    	$validatedData = $request->validate([
			'title' => 'required|max:75',
			'price' => 'required',
	    ]);

		$productItemDetails = ProductItemDetails::find($request->input('id'));
		$productItemDetails->title = $request->input('title');
		$productItemDetails->price = $request->input('price');
		$productItemDetails->price_comm = $request->input('price') * .15;
		$productItemDetails->active = $request->input('active');
		$status = $productItemDetails->save();
	   	
	   	if ($status) {
			$data['message'] = "Successfully added new record";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);
    }

    public function getVariantDetails(ProductItemHeader $productItemHeader) {

    	$data = array();
    	$data['product_details'] = $productItemHeader->product_details;

    	return response()->json($data, 200);
    }

	public function destroy(ProductItemHeader $productItemHeader) {

		$data = array();
		$obj = ProductItemHeader::find($productItemHeader->id);
		$status = $obj->delete();

		if ($status) {
			$data['message'] = "Successfully deleted Variant Header";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}

		return response()->json($data, 200);
	}
	/**
	 * [deleteDetails description]
	 * @param  ProductItemDetails $productItemDetails [description]
	 * @return [type]                                 [description]
	 */
	public function deleteDetails(ProductItemDetails $productItemDetails) {

		$data = array();
		$obj = ProductItemDetails::find($productItemDetails->id);
		$status = $obj->delete();

		if ($status) {
			$data['message'] = "Successfully deleted";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}

		return response()->json($data, 200);
	}
	
}
