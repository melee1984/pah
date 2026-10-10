@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid"><div class="admin-page-heading">
            <div><span class="admin-eyebrow">Merchant recruitment</span><h1>PahatudFood applications</h1><p>Review food-business applications before document verification and merchant onboarding.</p></div>
            <a class="btn admin-btn-secondary" href="{{ route('merchant.register') }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-2"></i>View recruitment page</a>
        </div></div>
    </section>

    <section class="content"><div class="container-fluid">
        @if (session('success')) <div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div> @endif

        <div class="admin-stat-grid">
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-file-alt"></i></span><div><small>Total applications</small><strong>{{ number_format($metrics['total']) }}</strong></div></article>
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-clock"></i></span><div><small>Pending review</small><strong>{{ number_format($metrics['pending']) }}</strong></div></article>
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-check"></i></span><div><small>Approved</small><strong>{{ number_format($metrics['approved']) }}</strong></div></article>
            <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-times"></i></span><div><small>Declined</small><strong>{{ number_format($metrics['declined']) }}</strong></div></article>
        </div>

        <div class="card admin-card">
            <div class="admin-card-header">
                <div><h2>Merchant applications</h2><p>{{ number_format($applications->total()) }} {{ Str::plural('application', $applications->total()) }} found</p></div>
                <form class="form-inline" method="GET" action="{{ route('dashboard.merchant-applications.index') }}">
                    <label class="sr-only" for="merchantApplicationStatus">Status</label>
                    <select class="form-control form-control-sm mr-2" id="merchantApplicationStatus" name="status">
                        <option value="">All statuses</option>
                        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
                    </select>
                    <label class="sr-only" for="merchantApplicationSearch">Search</label>
                    <input class="form-control form-control-sm mr-2" id="merchantApplicationSearch" name="search" value="{{ $search }}" placeholder="Business, owner, email">
                    <button class="btn admin-btn-primary btn-sm" type="submit"><i class="fas fa-search mr-1"></i>Filter</button>
                    @if ($search !== '' || $status !== '') <a class="btn admin-btn-secondary btn-sm ml-2" href="{{ route('dashboard.merchant-applications.index') }}">Reset</a> @endif
                </form>
            </div>

            @if ($applications->isEmpty())
                <div class="admin-empty-state"><span><i class="fas fa-store"></i></span><h3>No merchant applications found</h3><p>{{ $search !== '' || $status !== '' ? 'Try changing the current filters.' : 'New PahatudFood applications will appear here.' }}</p></div>
            @else
                <div class="table-responsive"><table class="table admin-table mb-0">
                    <thead><tr><th>Submitted</th><th>Business</th><th>Applicant</th><th>Location</th><th>Services</th><th>Commission</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <td><strong>{{ $application->created_at?->format('M d, Y') }}</strong><small>{{ $application->created_at?->format('g:i A') }}</small></td>
                            <td><strong>{{ $application->business_name }}</strong><small>{{ $application->registered_business_name }}</small></td>
                            <td><strong>{{ $application->owner_name }}</strong><small>{{ $application->email }}</small><small>{{ $application->mobile }}</small></td>
                            <td>{{ $application->city }}<small>{{ Str::limit($application->address, 45) }}</small></td>
                            <td>{{ implode(', ', $application->serviceLabels()) }}<small>{{ number_format($application->branch_count) }} {{ Str::plural('branch', $application->branch_count) }}</small></td>
                            <td><strong>{{ number_format($application->commission_percentage, 2) }}%</strong></td>
                            <td><span class="admin-status {{ $application->status === 'approved' ? 'admin-status-active' : ($application->status === 'declined' ? 'admin-status-inactive' : 'admin-status-pending') }}">{{ Str::headline($application->status) }}</span></td>
                            <td><a class="btn admin-btn-secondary btn-sm" href="{{ route('dashboard.merchant-applications.show', $application) }}">Review</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
                <div class="admin-pagination">{{ $applications->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div></section>
</div>
@endsection
