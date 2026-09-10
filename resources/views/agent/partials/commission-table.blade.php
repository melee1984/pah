<table class="agent-table agent-commission-table">
    <thead>
        <tr>
            <th>Restaurant / order</th>
            <th>Order breakdown</th>
            <th>Pahatud commission</th>
            <th>Agent share</th>
            <th>Agent commission</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($commissions as $commission)
        @php
            $subtotal = (float) ($commission->subtotal_amount ?? $commission->order_amount);
            $deliveryFee = (float) ($commission->delivery_fee_amount ?? 0);
            $discount = (float) ($commission->discount_amount ?? 0);
            $total = (float) ($commission->total_amount ?? $commission->order_amount);
            $pahatudPercentage = (float) ($commission->pahatud_commission_percentage ?? $commission->restaurant?->percentage ?? config('agent.pahatud_commission_percentage'));
            $pahatudAmount = (float) ($commission->pahatud_commission_amount ?? round($subtotal * ($pahatudPercentage / 100), 2));
        @endphp
        <tr>
            <td class="agent-table-primary">
                {{ $commission->restaurant?->restaurant_name ?? 'Restaurant unavailable' }}
                <span class="agent-order-number">{{ $commission->order?->cart?->order_no ? 'Order #'.$commission->order->cart->order_no : 'Order number unavailable' }}</span>
            </td>
            <td>
                <div class="agent-order-breakdown">
                    <span><small>Subtotal</small><strong>₱{{ number_format($subtotal, 2) }}</strong></span>
                    <span><small>Delivery fee</small><strong>₱{{ number_format($deliveryFee, 2) }}</strong></span>
                    @if ($discount > 0)
                        <span><small>Discount</small><strong>-₱{{ number_format($discount, 2) }}</strong></span>
                    @endif
                    <span class="agent-order-total"><small>Total</small><strong>₱{{ number_format($total, 2) }}</strong></span>
                </div>
            </td>
            <td>
                <div class="agent-rate-stack"><strong>{{ number_format($pahatudPercentage, 2) }}%</strong><span>₱{{ number_format($pahatudAmount, 2) }} of subtotal</span></div>
            </td>
            <td>
                <div class="agent-rate-stack"><strong>{{ number_format($commission->commission_percentage, 2) }}%</strong><span>of Pahatud commission</span></div>
            </td>
            <td>
                <div class="agent-commission-result {{ $commission->status !== 'reversed' ? 'is-earned' : '' }}">
                    <strong>₱{{ number_format($commission->commission_amount, 2) }}</strong>
                    <span>₱{{ number_format($subtotal, 2) }} × {{ number_format($pahatudPercentage, 2) }}% × {{ number_format($commission->commission_percentage, 2) }}%</span>
                </div>
            </td>
            <td><span class="agent-badge agent-badge-{{ $commission->status }}">{{ $commission->status }}</span>@if($commission->reversal_reason)<span class="agent-table-secondary">{{ $commission->reversal_reason }}</span>@endif</td>
            <td><strong class="agent-date">{{ $commission->qualified_at->format('M d, Y') }}</strong><span class="agent-table-secondary">{{ $commission->qualified_at->format('g:i A') }}</span></td>
        </tr>
    @endforeach
    </tbody>
</table>
