@if($orders->isEmpty())
<div class="customer-empty"><i class="icofont-food-basket" aria-hidden="true"></i><h3>No orders yet</h3><p>Your orders will appear here once you place one.</p><a href="{{ route('home') }}">Explore PahatudFood →</a></div>
@else
<div class="customer-table-wrap"><table class="customer-order-table"><thead><tr><th>Order</th><th>Restaurant</th><th>Placed</th><th>Current status</th><th></th></tr></thead><tbody>
@foreach($orders as $order)
<tr><td>#{{ $order->cart?->order_no ?: $order->id }}</td><td>{{ $order->cart?->partner?->restaurant_name ?? 'Restaurant unavailable' }}</td><td>{{ $order->created_at?->format('M j, Y · g:i A') }}</td><td><span class="customer-status">{{ $order->orderStatus?->title ?? 'Order placed' }}</span>@if($order->status)<small>Delivery: {{ $order->status->title ?? $order->status->description }}</small>@endif</td><td>@if($order->cart?->order_no)<a href="{{ route('profile.orders.view', $order->cart) }}" aria-label="View order {{ $order->cart->order_no }}">View details →</a>@endif</td></tr>
@endforeach
</tbody></table></div>
@endif
