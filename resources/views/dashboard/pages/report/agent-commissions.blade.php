@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Partner reporting</span>
                    <h1>Agent Commission Report</h1>
                    <p>Review each order breakdown and the commission calculated from its subtotal only.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>
            @endif

            <div class="card admin-card rider-report-filter-card">
                <form class="dashboard-filter-bar agent-commission-filters" method="GET" action="{{ route('dashboard.report.agents') }}">
                    <div>
                        <label for="agent_id">Agent</label>
                        <select class="form-control" id="agent_id" name="agent_id">
                            <option value="">All agents</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected($selectedAgentId === $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="restaurant_id">Restaurant</label>
                        <select class="form-control" id="restaurant_id" name="restaurant_id">
                            <option value="">All restaurants</option>
                            @foreach ($restaurants as $restaurant)
                                <option value="{{ $restaurant->id }}" @selected($selectedRestaurantId === $restaurant->id)>{{ $restaurant->restaurant_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All statuses</option>
                            @foreach (['pending', 'approved', 'paid', 'reversed'] as $status)
                                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="from">From</label><input class="form-control" id="from" name="from" type="date" value="{{ $from->toDateString() }}" required></div>
                    <div><label for="to">To</label><input class="form-control" id="to" name="to" type="date" value="{{ $to->toDateString() }}" required></div>
                    <div class="dashboard-filter-actions">
                        <button class="btn admin-btn-primary" type="submit"><i class="fas fa-filter mr-2"></i>Apply filters</button>
                        <a class="btn admin-btn-secondary" href="{{ route('dashboard.report.agents') }}">Reset</a>
                    </div>
                </form>
            </div>

            <div class="admin-stat-grid rider-report-stats">
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-hand-holding-usd"></i></span><div><small>Agent commission</small><strong>₱{{ number_format($metrics['commission'], 2) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-shopping-bag"></i></span><div><small>Qualifying subtotal</small><strong>₱{{ number_format($metrics['subtotal'], 2) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-receipt"></i></span><div><small>Commission entries</small><strong>{{ number_format($metrics['count']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-undo-alt"></i></span><div><small>Reversed commission</small><strong>₱{{ number_format($metrics['reversed'], 2) }}</strong></div></article>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2>{{ $selectedAgentId ? ($agents->firstWhere('id', $selectedAgentId)?->name ?? 'Selected agent') : 'All agent commissions' }}</h2>
                        <p>{{ number_format($commissions->total()) }} {{ Str::plural('entry', $commissions->total()) }} from {{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
                    </div>
                    <span class="dashboard-soft-badge">{{ $selectedStatus ? ucfirst($selectedStatus) : 'All statuses' }}</span>
                </div>

                @if ($commissions->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-file-invoice-dollar"></i></span><h3>No agent commissions found</h3><p>Try another agent, restaurant, status, or date range.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table agent-commission-table">
                            <thead><tr><th>Date/time</th><th>Agent</th><th>Restaurant / order</th><th>Order breakdown</th><th>Pahatud commission</th><th>Agent share</th><th>Agent commission</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach ($commissions as $commission)
                                @php
                                    $statusClass = match ($commission->status) {
                                        'approved', 'paid' => 'is-success',
                                        'reversed' => 'is-danger',
                                        default => 'is-warning',
                                    };
                                    $subtotal = (float) ($commission->subtotal_amount ?? $commission->order_amount);
                                    $deliveryFee = (float) ($commission->delivery_fee_amount ?? 0);
                                    $discount = (float) ($commission->discount_amount ?? 0);
                                    $total = (float) ($commission->total_amount ?? $commission->order_amount);
                                    $pahatudPercentage = (float) ($commission->pahatud_commission_percentage ?? $commission->restaurant?->percentage ?? config('agent.pahatud_commission_percentage'));
                                    $pahatudAmount = (float) ($commission->pahatud_commission_amount ?? round($subtotal * ($pahatudPercentage / 100), 2));
                                @endphp
                                <tr>
                                    <td><strong>{{ $commission->qualified_at->format('M d, Y') }}</strong><small>{{ $commission->qualified_at->format('g:i A') }}</small></td>
                                    <td><strong>{{ $commission->agent?->name ?? 'Agent unavailable' }}</strong><small>{{ $commission->agent?->email }}</small></td>
                                    <td><strong>{{ $commission->restaurant?->restaurant_name ?? 'Restaurant unavailable' }}</strong><small>{{ $commission->order?->cart?->order_no ? 'Order #'.$commission->order->cart->order_no : 'Order number unavailable' }}</small></td>
                                    <td>
                                        <div class="agent-order-breakdown">
                                            <span>Subtotal <strong>₱{{ number_format($subtotal, 2) }}</strong></span>
                                            <span>Delivery fee <strong>₱{{ number_format($deliveryFee, 2) }}</strong></span>
                                            @if ($discount > 0)<span>Discount <strong>-₱{{ number_format($discount, 2) }}</strong></span>@endif
                                            <span class="is-total">Total <strong>₱{{ number_format($total, 2) }}</strong></span>
                                        </div>
                                    </td>
                                    <td><strong>{{ number_format($pahatudPercentage, 2) }}%</strong><small>₱{{ number_format($pahatudAmount, 2) }} of subtotal</small></td>
                                    <td><strong>{{ number_format($commission->commission_percentage, 2) }}%</strong></td>
                                    <td><strong class="admin-money">₱{{ number_format($commission->commission_amount, 2) }}</strong><small>₱{{ number_format($subtotal, 2) }} × {{ number_format($pahatudPercentage, 2) }}% × {{ number_format($commission->commission_percentage, 2) }}%</small></td>
                                    <td><span class="dashboard-status-pill {{ $statusClass }}">{{ ucfirst($commission->status) }}</span>@if($commission->reversal_reason)<small>{{ $commission->reversal_reason }}</small>@endif</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $commissions->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
