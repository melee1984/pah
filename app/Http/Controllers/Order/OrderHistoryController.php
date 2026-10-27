<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\Model\Orders\Orders;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    public function index(Request $request)
    {
        $orders = Orders::with(['cart', 'orderStatus', 'status'])
            ->where('user_id', $request->user()->id)->latest()->paginate(15);

        return view('customer.orders', compact('orders'));
    }

    public function view(Request $request, Cart $cart)
    {
        // Authorize before loading items, addresses or payment information.
        abort_unless((int) $cart->user_id === (int) $request->user()->id, 404);
        $order = Orders::where('cart_id', $cart->id)->where('user_id', $request->user()->id)
            ->with(['orderStatus', 'status'])->firstOrFail();
        $cart->load(['partner', 'details.item', 'address', 'payment']);
        $summary = $cart->cartItemSummary();

        return view('customer.order', compact('order', 'cart', 'summary'));
    }
}
