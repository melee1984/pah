@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Agent management</span>
                    <h1>Agents</h1>
                    <p>Manage Agent Portal access, restaurant networks, and each agent's share of Pahatud commission.</p>
                </div>
                <button class="btn admin-btn-primary" type="button" data-toggle="modal" data-target="#addAgentModal">
                    <i class="fas fa-plus mr-2"></i>Add new agent
                </button>
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
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-user-tie"></i></span><div><small>Total agents</small><strong>{{ number_format($metrics['total']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-user-check"></i></span><div><small>Active agents</small><strong>{{ number_format($metrics['active']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-key"></i></span><div><small>Setup pending</small><strong>{{ number_format($metrics['password_pending']) }}</strong></div></article>
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-wallet"></i></span><div><small>Total commission</small><strong>₱{{ number_format($metrics['commission'], 2) }}</strong></div></article>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div><h2>All agents</h2><p>{{ number_format($agents->total()) }} registered Agent Portal {{ Str::plural('account', $agents->total()) }}</p></div>
                    <form class="admin-search" method="GET" action="{{ route('dashboard.agents.index') }}">
                        <i class="fas fa-search"></i>
                        <input name="search" value="{{ $search }}" placeholder="Search name, email, or mobile" aria-label="Search agents">
                        @if ($search !== '')<a href="{{ route('dashboard.agents.index') }}" aria-label="Clear search">&times;</a>@endif
                    </form>
                </div>

                @if ($agents->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-user-tie"></i></span><h3>No agents found</h3><p>{{ $search !== '' ? 'Try another search term.' : 'Add the first agent to start building the restaurant network.' }}</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Agent</th><th>Contact</th><th>Restaurants</th><th>Agent share</th><th>Commission earned</th><th>Account</th><th>Last login</th><th>Action</th></tr></thead>
                            <tbody>
                            @foreach ($agents as $agent)
                                <tr>
                                    <td><div class="admin-agent-cell"><span>{{ mb_strtoupper(mb_substr($agent->name, 0, 1)) }}</span><div><strong>{{ $agent->name }}</strong><small>Agent #{{ $agent->id }}</small></div></div></td>
                                    <td><strong>{{ $agent->email }}</strong><small>{{ $agent->mobile ?: 'No mobile number' }}</small></td>
                                    <td><span class="admin-number-pill">{{ number_format($agent->restaurants_count) }}</span></td>
                                    <td><strong>{{ number_format($agent->commission_percentage, 2) }}%</strong></td>
                                    <td><strong class="admin-money">₱{{ number_format($agent->commission_total ?? 0, 2) }}</strong></td>
                                    <td>
                                        @if (! $agent->active)
                                            <span class="admin-status admin-status-inactive">Inactive</span>
                                        @elseif ($agent->must_change_password)
                                            <span class="admin-status admin-status-pending">Awaiting setup</span>
                                        @else
                                            <span class="admin-status admin-status-active">Active</span>
                                        @endif
                                    </td>
                                    <td>{{ $agent->last_login_at?->format('M d, Y') ?? 'Never' }}<small>{{ $agent->last_login_at?->format('g:i A') }}</small></td>
                                    <td>
                                        @if (! $agent->active)
                                            <form method="POST" action="{{ route('dashboard.agents.approve', $agent) }}">@csrf<button class="btn admin-btn-primary btn-sm" type="submit"><i class="fas fa-check mr-1"></i>Approve</button></form>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $agents->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="addAgentModal" tabindex="-1" role="dialog" aria-labelledby="addAgentTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content admin-modal">
            <div class="modal-header">
                <div><span class="admin-eyebrow">New portal account</span><h2 id="addAgentTitle">Add new agent</h2></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="POST" action="{{ route('dashboard.agents.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="admin-form-note"><i class="fas fa-envelope"></i><span>A secure temporary password will be generated and emailed automatically. The agent must replace it on first login.</span></div>
                    <div class="form-group"><label for="agent_name">Full name</label><input class="form-control" id="agent_name" name="name" value="{{ old('name') }}" maxlength="255" required></div>
                    <div class="form-group"><label for="agent_email">Email address</label><input class="form-control" id="agent_email" name="email" type="email" value="{{ old('email') }}" required></div>
                    <div class="form-row">
                        <div class="form-group col-md-7"><label for="agent_mobile">Mobile number <small>(optional)</small></label><input class="form-control" id="agent_mobile" name="mobile" value="{{ old('mobile') }}" maxlength="30"></div>
                        <div class="form-group col-md-5"><label for="agent_rate">Share of Pahatud commission</label><div class="input-group"><input class="form-control" id="agent_rate" name="commission_percentage" type="number" min="0" max="100" step="0.01" value="{{ old('commission_percentage', config('agent.default_commission_percentage')) }}" required><div class="input-group-append"><span class="input-group-text">%</span></div></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn admin-btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn admin-btn-primary"><i class="fas fa-paper-plane mr-2"></i>Create and email password</button></div>
            </form>
        </div>
    </div>
</div>

@if ($errors->any())
<script>document.addEventListener('DOMContentLoaded', function () { $('#addAgentModal').modal('show'); });</script>
@endif
@endsection
