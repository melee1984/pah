<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Auth;
use App\Products;
use App\ProductItemHeader;

class ItemsController extends Controller
{
    public function index() {	

    	$categories = Auth::User()->categories;
    	$products = Auth::User()->products;


    	// Filter Products 
		return view('merchant.pages.product.view', compact('categories', 'products' ));
    }
    /**
     * Product Addons 
     *
     * @return [type] [description]
     */
    public function productaddons() {

        return view('merchant.pages.product.addons', compact('product'));
    }

    public function edit(Products $product) {
    	
        $product = Products::find($product->id);

        $product->productImageCheck($product);

        if ($product->variants()->exists()) {
            foreach($product->variants as $variant) {
                $variant->imgCheck($variant);
            }
        }

        $product->user->merchant;
     	// Filter Products 
		return view('merchant.pages.product.edit', compact('product'));
    }

}
