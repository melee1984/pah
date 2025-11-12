<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Orders\Orders;
use App\Model\Cart;
use Auth;

class OrderHistoryController extends Controller
{
     /**
    * View 
    * @return [type] [description]
    */
    public function view(Cart $cart) 
    {
    	$orders = Orders::with('cart')
                    ->whereUserId(Auth::User()->id)
                    ->orderBy('created_at', 'desc')->get();

		return view('pages.profile.orders.single', compact('orders', 'cart'));
    }


     /**
    * View 
    * @return [type] [description]
    */
    public function index() 
    {       
        $cart = Cart::whereUserId(Auth::User()->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

        $orders = Orders::with('cart')
                    ->whereUserId(Auth::User()->id)
                    ->orderBy('created_at', 'desc')->get();

		return view('pages.profile.orders.listing', compact('orders','cart'));
    }
    
}
