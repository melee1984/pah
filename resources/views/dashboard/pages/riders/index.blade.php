@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Rider management</span>
                    <h1>Available riders</h1>
                    <p>Review rider accounts, approve access, and manage rider credit balances.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>
            @endif

            <div class="admin-stat-grid">
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-motorcycle"></i></span><div><small>Total riders</small><strong>{{ number_format($metrics['total']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-user-check"></i></span><div><small>Approved riders</small><strong>{{ number_format($metrics['approved']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-user-clock"></i></span><div><small>Awaiting approval</small><strong>{{ number_format($metrics['pending']) }}</strong></div></article>
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-wallet"></i></span><div><small>Total rider credits</small><strong>₱{{ number_format($metrics['credits'], 2) }}</strong></div></article>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div><h2>Rider list</h2><p>{{ number_format($riders->total()) }} available rider {{ Str::plural('account', $riders->total()) }}</p></div>
                    <form class="admin-search" method="GET" action="{{ route('dashboard.rider') }}">
                        <i class="fas fa-search"></i>
                        <input name="search" value="{{ $search }}" placeholder="Search name or mobile" aria-label="Search riders">
                        @if ($search !== '')<a href="{{ route('dashboard.rider') }}" aria-label="Clear search">&times;</a>@endif
                    </form>
                </div>

                @if ($riders->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-motorcycle"></i></span><h3>No riders found</h3><p>{{ $search !== '' ? 'Try another search term.' : 'Registered riders will appear here.' }}</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Rider</th><th>Contact</th><th>Joined</th><th>Approval</th><th>Credits</th><th>Actions</th></tr></thead>
                            <tbody>
                            @foreach ($riders as $rider)
                                <tr>
                                    <td><div class="admin-agent-cell"><span>{{ mb_strtoupper(mb_substr($rider->name ?: 'R', 0, 1)) }}</span><div><strong>{{ $rider->name ?: 'Unnamed rider' }}</strong><small>Rider #{{ $rider->id }}</small></div></div></td>
                                    <td><strong>{{ $rider->mobile ?: 'No mobile number' }}</strong></td>
                                    <td>{{ $rider->date_join?->format('M d, Y') ?? $rider->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td>
                                        @if ($rider->active)
                                            <span class="admin-status admin-status-active">Approved</span>
                                            <small>{{ $rider->approved_at?->format('M d, Y · g:i A') ?? 'Approval date unavailable' }}</small>
                                        @else
                                            <span class="admin-status admin-status-pending">Pending</span>
                                            <small>Awaiting admin approval</small>
                                        @endif
                                    </td>
                                    <td><strong class="admin-money">₱{{ number_format((float) ($rider->wallet?->credit_amount ?? 0), 2) }}</strong></td>
                                    <td>
                                        <div class="d-flex flex-wrap" style="gap: 7px">
                                            @if (! $rider->active || ! $rider->approved_at)
                                                <form method="POST" action="{{ route('dashboard.riders.approve', $rider) }}">
                                                    @csrf
                                                    <button class="btn admin-btn-primary btn-sm" type="submit"><i class="fas fa-check mr-1"></i>Approve</button>
                                                </form>
                                            @endif
                                            <button class="btn admin-btn-secondary btn-sm" type="button" data-toggle="modal" data-target="#creditModal{{ $rider->id }}"><i class="fas fa-coins mr-1"></i>Update credits</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $riders->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>

            <div class="card admin-card mt-4">
                <div class="admin-card-header">
                    <div><h2>Pending wallet top-ups</h2><p>Verify the submitted payment proof before adding credits to a rider's wallet.</p></div>
                    <span class="admin-number-pill">{{ number_format($pendingTopUps->count()) }}</span>
                </div>

                @if ($pendingTopUps->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-receipt"></i></span><h3>No top-ups awaiting review</h3><p>New rider top-up requests will appear here.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Rider</th><th>Amount</th><th>Payment</th><th>Submitted</th><th>Proof</th><th>Action</th></tr></thead>
                            <tbody>
                            @foreach ($pendingTopUps as $topUp)
                                <tr>
                                    <td><strong>{{ $topUp->rider_name ?: 'Rider #'.$topUp->rider_id }}</strong><small>Rider #{{ $topUp->rider_id }}</small></td>
                                    <td><strong class="admin-money">₱{{ number_format($topUp->amount_centavos / 100, 2) }}</strong></td>
                                    <td><strong>{{ Str::headline($topUp->payment_method) }}</strong><small>{{ $topUp->payment_reference }}</small></td>
                                    <td>{{ \Carbon\Carbon::parse($topUp->created_at)->format('M d, Y') }}<small>{{ \Carbon\Carbon::parse($topUp->created_at)->format('g:i A') }}</small></td>
                                    <td><a class="dashboard-inline-link" href="{{ route('dashboard.rider-top-ups.proof', $topUp->reference) }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-1"></i>{{ $topUp->proof_original_name }}</a></td>
                                    <td>
                                        <form method="POST" action="{{ route('dashboard.rider-top-ups.approve', $topUp->reference) }}">
                                            @csrf
                                            <button class="btn admin-btn-primary btn-sm" type="submit"><i class="fas fa-check mr-1"></i>Approve top-up</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>

@foreach ($riders as $rider)
    <div class="modal fade" id="creditModal{{ $rider->id }}" tabindex="-1" role="dialog" aria-labelledby="creditModalTitle{{ $rider->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content admin-modal">
                <div class="modal-header">
                    <div><span class="admin-eyebrow">Credit adjustment</span><h2 id="creditModalTitle{{ $rider->id }}">{{ $rider->name ?: 'Rider #'.$rider->id }}</h2></div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form method="POST" action="{{ route('dashboard.riders.credits', $rider) }}">
                    @csrf
                    <input type="hidden" name="rider_id" value="{{ $rider->id }}">
                    <div class="modal-body">
                        <div class="admin-form-note"><i class="fas fa-history"></i><span>Current balance: <strong>₱{{ number_format((float) ($rider->wallet?->credit_amount ?? 0), 2) }}</strong>. Every adjustment is saved in the rider's wallet transaction history with the administrator, amount, resulting balance, reason, and time.</span></div>
                        <div class="form-group">
                            <label for="creditAction{{ $rider->id }}">Adjustment</label>
                            <select class="form-control" id="creditAction{{ $rider->id }}" name="action" required>
                                <option value="add" @selected(old('rider_id') == $rider->id && old('action') === 'add')>Add credits</option>
                                <option value="deduct" @selected(old('rider_id') == $rider->id && old('action') === 'deduct')>Deduct credits</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="creditAmount{{ $rider->id }}">Amount</label>
                            <div class="input-group"><div class="input-group-prepend"><span class="input-group-text">₱</span></div><input class="form-control" id="creditAmount{{ $rider->id }}" name="amount" type="number" min="0.01" max="1000000" step="0.01" value="{{ old('rider_id') == $rider->id ? old('amount') : '' }}" required></div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="creditReason{{ $rider->id }}">Reason</label>
                            <textarea class="form-control" id="creditReason{{ $rider->id }}" name="reason" rows="3" maxlength="500" placeholder="Why is this adjustment being made?" required>{{ old('rider_id') == $rider->id ? old('reason') : '' }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn admin-btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn admin-btn-primary"><i class="fas fa-save mr-2"></i>Save adjustment</button></div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@if ($errors->any() && old('rider_id'))
<script>document.addEventListener('DOMContentLoaded', function () { $('#creditModal{{ (int) old('rider_id') }}').modal('show'); });</script>
@endif
@endsection
