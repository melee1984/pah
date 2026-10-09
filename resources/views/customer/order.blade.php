@extends('templates.customer-account')
@section('title', 'Order #'.$cart->order_no)
@section('content')
<a class="customer-back" href="{{ route('profile.orders') }}">← My Orders</a>
<div class="customer-order-grid">
    <section class="customer-card">
        <h2>{{ $cart->partner?->restaurant_name ?? 'Order details' }}</h2>
        <p>Placed {{ $order->created_at?->format('M j, Y · g:i A') }}</p>
        <span class="customer-status">{{ $order->orderStatus?->title ?? 'Order placed' }}</span>
        @if($order->status)<p>Delivery status: <strong>{{ $order->status->title ?? $order->status->description }}</strong></p>@endif
        <p>Fulfillment: {{ ['delivery'=>'Delivery', 'pickup'=>'Pickup', 'dine_in'=>'Dine-in'][$cart->fulfillment_type ?: 'delivery'] ?? 'Delivery' }}</p>
        <div class="customer-table-wrap"><table class="customer-order-table"><thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead><tbody>
        @foreach($cart->details as $detail)
            <tr><td>{{ $detail->item?->title ?? 'Item unavailable' }}@if($detail->instruction)<small>{{ $detail->instruction }}</small>@endif</td><td>{{ $detail->qty }}</td><td>₱{{ number_format(($detail->price + $detail->variance_total) * $detail->qty, 2) }}</td></tr>
        @endforeach
        </tbody></table></div>
        <dl class="customer-order-totals">
            @foreach(['sub_total'=>'Subtotal', 'delivery_fee'=>'Delivery fee', 'convenience_fee'=>'Convenience fee', 'discount'=>'Discount', 'total'=>'Total'] as $key=>$label)<div><dt>{{ $label }}</dt><dd>{{ $key === 'discount' ? '−' : '' }}₱{{ $summary[$key] }}</dd></div>@endforeach
        </dl>
    </section>
    <aside class="customer-card"><h2>Order information</h2><p><strong>{{ $cart->fullname ?: auth()->user()->full_name }}</strong><br>{{ $cart->mobile }}</p>
        @if($cart->address)<h3>Delivery address</h3><p>{{ $cart->address->address_1 }}<br>{{ $cart->address->address_2 }}</p>@endif
        @if($cart->payment)<h3>Payment method</h3><p>{{ $cart->payment->title ?? $cart->payment->name }}</p>@endif
        <h3>Need a hand?</h3><p>Our support team can help with this order.</p><a href="{{ route('profile.support') }}">My Support Requests →</a>
    </aside>
</div>
@endsection
