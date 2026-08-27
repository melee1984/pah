<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Model\Cart;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {       
        $cart = new Cart();

        return view('checkout.index', compact('cart'));
    }
}
