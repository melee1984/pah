@extends($layout)

@php
    $fulfillmentLabels = ['delivery' => 'Delivery', 'pickup' => 'Pickup', 'dine_in' => 'Dine-in'];
    $fulfillmentLabel = $fulfillmentLabels[$fulfillmentType] ?? 'Delivery';
    $statusClass = fn ($statusId) => (int) $statusId === 7 ? 'is-success' : ((int) $statusId === 8 ? 'is-danger' : 'is-progress');
    $location = $order->cart->partnerlocation;
    $storeAddress = collect([$location?->address_1, $location?->address_2, $location?->city, $location?->zip_code])->filter()->join(', ');
    $storeContact = collect([$location?->mobile, $location?->telephone])->filter()->join(' / ');
    $scheduleLabel = $fulfillmentType === 'delivery' ? 'Estimated delivery' : 'Requested time';
    $customerLabel = ['pickup' => 'Picking up this order', 'dine_in' => 'Dining at the restaurant'][$fulfillmentType] ?? 'Delivery recipient';
    $tableLabel = $order->cart->diningTable?->name ?? $order->cart->diningTable?->table_name ?? ($order->cart->dining_table_id ? 'Table #'.$order->cart->dining_table_id : 'Table not specified');
@endphp

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="admin-page-heading dashboard-operations-heading">
                <div><span class="admin-eyebrow">Order information</span><h1>Order #{{ $order->cart->order_no }}</h1><p>Review the complete order, customer, fulfillment, and payment details.</p></div>
                <div class="admin-dashboard-actions"><a href="{{ route($backRoute) }}" class="btn admin-btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Back to sales report</a></div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <article class="order-detail-page">
                <header class="order-detail-header">
                    <div class="order-detail-heading">
                        <span class="order-detail-eyebrow"><i class="fas fa-receipt"></i> Order overview</span>
                        <h2>Order #{{ $order->cart->order_no }}</h2>
                        <p>Placed {{ optional($order->submitted_at ?: $order->created_at)->format('m/d/Y h:i a') ?? 'Date unavailable' }}</p>
                    </div>
                    <div class="order-detail-statuses">
                        @if($order->orderStatus)<span class="order-detail-status {{ $statusClass($order->orderStatus->id) }}"><small>Order</small>{{ $order->orderStatus->title }}</span>@endif
                        @if($order->status)<span class="order-detail-status {{ $statusClass($order->status->id) }}"><small>Delivery</small>{{ $order->status->title }}</span>@endif
                    </div>
                </header>

                <div class="order-detail-body">
                    <div class="order-detail-highlights">
                        <div><span>{{ $scheduleLabel }}</span><strong>{{ $order->cart->delivery_date ?: 'Date unavailable' }}</strong><small>{{ $order->cart->delivery_time ?: 'Time unavailable' }}</small></div>
                        <div><span>Order option</span><strong>{{ $fulfillmentLabel }}</strong><small>{{ $fulfillmentType === 'dine_in' ? $tableLabel : ($storeAddress ?: 'Branch unavailable') }}</small></div>
                        <div><span>Items</span><strong>{{ $summary['qty'] ?? 0 }}</strong><small>in this order</small></div>
                        <div class="order-detail-highlight-total"><span>Order total</span><strong>₱{{ $summary['total'] ?? '0.00' }}</strong><small>{{ $fulfillmentType === 'delivery' ? 'including delivery' : $fulfillmentLabel.' order' }}</small></div>
                    </div>

                    <div class="order-detail-layout">
                        <div class="order-detail-main">
                            <section class="order-detail-panel">
                                <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-utensils"></i></span><div><h3>Items ordered</h3><p>A breakdown of the customer's order</p></div></div>
                                @forelse($order->cart->details as $item)
                                    <div class="order-detail-item">
                                        <span class="order-detail-item-qty">{{ $item->qty }}×</span>
                                        <div class="order-detail-item-copy">
                                            <strong>{{ $item->item?->title ?? 'Item unavailable' }}</strong>
                                            @foreach(($item->variance_content ?: []) as $variation)<span>+ {{ data_get($variation, 'title') }}</span>@endforeach
                                            @if($item->instruction)<em>Note: {{ $item->instruction }}</em>@endif
                                        </div>
                                        <strong class="order-detail-item-price">₱{{ number_format((float) $item->price + (float) $item->variance_total, 2) }}</strong>
                                    </div>
                                @empty
                                    <p class="order-detail-muted">No items are available for this order.</p>
                                @endforelse
                            </section>

                            <div class="order-detail-people">
                                <section class="order-detail-panel">
                                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-store"></i></span><div><h3>Merchant</h3><p>Preparing this order</p></div></div>
                                    <strong>{{ $order->partner?->restaurant_name ?? 'Merchant unavailable' }}</strong>
                                    <p class="order-detail-muted">{{ $storeAddress ?: 'Address unavailable' }}</p>
                                    @if($storeContact)<p class="order-detail-muted">{{ $storeContact }}</p>@endif
                                </section>
                                <section class="order-detail-panel">
                                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-user"></i></span><div><h3>Customer</h3><p>{{ $customerLabel }}</p></div></div>
                                    <strong>{{ $order->cart->fullname ?: 'Name unavailable' }}</strong>
                                    @if($fulfillmentType === 'delivery' && $order->cart->address)<p class="order-detail-muted">{{ $order->cart->address->address_1 }}</p>@endif
                                    @if($fulfillmentType === 'dine_in')<p class="order-detail-muted">{{ $tableLabel }}</p>@endif
                                    @if($order->cart->mobile)<p class="order-detail-muted">{{ $order->cart->mobile }}</p>@endif
                                </section>
                            </div>

                            @if((int) $order->order_status_id === 7 && $fulfillmentType === 'delivery')
                                <section class="order-detail-panel order-detail-proof-panel">
                                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-camera"></i></span><div><h3>Proof of delivery</h3><p>Confirmation provided by the rider</p></div></div>
                                    @if($proofs->isNotEmpty())
                                        <div class="order-detail-proof-grid">
                                            @foreach($proofs as $proof)
                                                <a class="order-detail-proof {{ $proof->file_url ? '' : 'order-detail-proof--static' }}" @if($proof->file_url) href="{{ $proof->file_url }}" target="_blank" rel="noopener" @endif>
                                                    @if($proof->file_url)<img src="{{ $proof->file_url }}" alt="Proof of delivery">@else<span class="order-detail-proof-placeholder"><i class="fas fa-check-circle"></i></span>@endif
                                                    <span class="order-detail-proof-caption"><strong>{{ ucfirst($proof->method ?: 'Delivery proof') }}</strong><small>{{ \Carbon\Carbon::parse($proof->created_at)->format('m/d/Y h:i a') }}</small></span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="order-detail-muted">No proof of delivery was submitted.</p>
                                    @endif
                                </section>
                            @endif
                        </div>

                        <aside class="order-detail-side">
                            <section class="order-detail-panel order-detail-summary">
                                <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-file-invoice"></i></span><div><h3>Payment summary</h3><p>Order charges at a glance</p></div></div>
                                <div class="order-detail-summary-row order-detail-payment-method"><span>Payment method</span><strong>{{ $order->cart->payment?->title ?? 'Not specified' }}</strong></div>
                                <div class="order-detail-summary-row"><span>Subtotal</span><strong>₱{{ $summary['sub_total'] ?? '0.00' }}</strong></div>
                                <div class="order-detail-summary-row"><span>Convenience fee</span><strong>₱{{ $summary['convenience_fee'] ?? '0.00' }}</strong></div>
                                <div class="order-detail-summary-row"><span>VAT</span><strong>₱{{ $summary['vat_amount'] ?? '0.00' }}</strong></div>
                                <div class="order-detail-summary-row"><span>Delivery fee</span><strong>₱{{ $summary['delivery_fee'] ?? '0.00' }}</strong></div>
                                <div class="order-detail-summary-row"><span>Discount</span><strong>− ₱{{ $summary['discount'] ?? '0.00' }}</strong></div>
                                @if($order->cart->discount_code)<div class="order-detail-summary-row"><span>Coupon code</span><strong>{{ $order->cart->discount_code }}</strong></div>@endif
                                <div class="order-detail-summary-total"><span>Total</span><strong>₱{{ $summary['total'] ?? '0.00' }}</strong></div>
                                <div class="order-detail-summary-row order-detail-commission"><span>Platform commission</span><strong>− ₱{{ $summary['total_comm'] ?? '0.00' }}</strong></div>
                            </section>

                            @if($fulfillmentType === 'delivery')
                                <section class="order-detail-panel">
                                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-motorcycle"></i></span><div><h3>Delivery partner</h3><p>Assigned rider for this order</p></div></div>
                                    @if($order->rider)<div class="order-detail-person"><strong>{{ $order->rider->name }}</strong>@if($order->rider->mobile)<span>{{ $order->rider->mobile }}</span>@endif</div>@else<p class="order-detail-muted">No rider assigned yet.</p>@endif
                                </section>
                            @endif
                        </aside>
                    </div>
                </div>
            </article>
        </div>
    </section>
</div>
@endsection
