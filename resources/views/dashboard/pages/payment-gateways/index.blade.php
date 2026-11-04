@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Checkout settings</span><h1>Payment gateways</h1><p>Add the payment choices shown at checkout and control which ones customers can select.</p></div></div></div>
    </section>

    <section class="content"><div class="container-fluid">
        @if (session('success'))<div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif

        <div class="card admin-card mb-4">
            <div class="admin-card-header"><div><h2>Add payment gateway</h2><p>This creates a new payment choice. Activate it when it is ready for customers.</p></div></div>
            <form method="POST" action="{{ route('dashboard.payment-gateways.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-lg-3"><label for="gateway_title">Display name</label><input class="form-control" id="gateway_title" name="title" value="{{ old('title') }}" maxlength="75" placeholder="Bank transfer" required></div>
                        <div class="form-group col-lg-5"><label for="gateway_description">Customer instructions</label><input class="form-control" id="gateway_description" name="description" value="{{ old('description') }}" maxlength="250" placeholder="Pay using your preferred bank"></div>
                        <div class="form-group col-lg-2"><label for="gateway_class">Material icon</label><input class="form-control" id="gateway_class" name="class" value="{{ old('class') }}" maxlength="20" placeholder="account_balance"><small class="form-text text-muted">Optional icon name.</small></div>
                        <div class="form-group col-lg-2 d-flex flex-column justify-content-end"><input type="hidden" name="active" value="0"><label><input name="active" type="checkbox" value="1" @checked((bool) old('active'))> Active at checkout</label><button class="btn admin-btn-primary mt-2" type="submit"><i class="fas fa-plus mr-2"></i>Add gateway</button></div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card admin-card">
            <div class="admin-card-header"><div><h2>Configured gateways</h2><p>{{ number_format($paymentGateways->count()) }} {{ Str::plural('gateway', $paymentGateways->count()) }} configured. Inactive gateways remain on existing order records but are hidden from checkout.</p></div></div>
            @if ($paymentGateways->isEmpty())
                <div class="admin-empty-state"><span><i class="fas fa-credit-card"></i></span><h3>No payment gateways</h3><p>Add the first payment option for checkout.</p></div>
            @else
                <div class="table-responsive"><table class="table admin-table payment-gateway-table">
                    <thead><tr><th>Gateway</th><th>Status</th><th>Customer instructions</th><th>Icon</th><th>Action</th></tr></thead>
                    <tbody>@foreach ($paymentGateways as $paymentGateway)<tr>
                        <td><strong>{{ $paymentGateway->title }}</strong><small>Gateway ID {{ $paymentGateway->id }}</small></td>
                        <td><span class="admin-status {{ $paymentGateway->active ? 'admin-status-active' : 'admin-status-inactive' }}">{{ $paymentGateway->active ? 'Active' : 'Inactive' }}</span></td>
                        <td colspan="3">
                            <form class="row align-items-center" method="POST" action="{{ route('dashboard.payment-gateways.update', $paymentGateway) }}">
                                @csrf @method('PUT')
                                <div class="col-lg-3 mb-2 mb-lg-0"><input class="form-control form-control-sm" name="title" value="{{ $paymentGateway->title }}" maxlength="75" aria-label="Gateway display name" required></div>
                                <div class="col-lg-4 mb-2 mb-lg-0"><input class="form-control form-control-sm" name="description" value="{{ $paymentGateway->description }}" maxlength="250" aria-label="Customer instructions" placeholder="Customer instructions"></div>
                                <div class="col-lg-2 mb-2 mb-lg-0"><input class="form-control form-control-sm" name="class" value="{{ $paymentGateway->class }}" maxlength="20" aria-label="Material icon" placeholder="Icon name"></div>
                                <div class="col-lg-2 mb-2 mb-lg-0"><input type="hidden" name="active" value="0"><label class="mb-0 text-nowrap"><input name="active" type="checkbox" value="1" @checked($paymentGateway->active)> Active</label></div>
                                <div class="col-lg-1"><button class="btn admin-btn-secondary btn-sm" type="submit">Save</button></div>
                            </form>
                        </td>
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </div>
    </div></section>
</div>
@endsection
