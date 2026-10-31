@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Service areas</span><h1>Cities</h1><p>Customers can save addresses in any listed city. Only active cities are available for service and agent applications.</p></div></div></div>
    </section>

    <section class="content"><div class="container-fluid">
        @if (session('success'))<div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif

        <div class="card admin-card mb-4">
            <div class="admin-card-header"><div><h2>Add a city</h2><p>Inactive cities remain available for saved addresses but cannot be used for delivery.</p></div></div>
            <form method="POST" action="{{ route('dashboard.cities.store') }}">
                @csrf
                <div class="card-body"><div class="row">
                    <div class="form-group col-md-8"><label for="city_name">City name</label><input class="form-control" id="city_name" name="name" value="{{ old('name') }}" maxlength="120" placeholder="Davao City" required></div>
                    <div class="form-group col-md-2 d-flex align-items-center"><input type="hidden" name="active" value="0"><label class="mb-0"><input name="active" type="checkbox" value="1" @checked((bool) old('active', true))> Serviceable</label></div>
                    <div class="form-group col-md-2 d-flex align-items-end"><button class="btn admin-btn-primary btn-block" type="submit"><i class="fas fa-plus mr-2"></i>Add city</button></div>
                </div></div>
            </form>
        </div>

        <div class="card admin-card">
            <div class="admin-card-header"><div><h2>City list</h2><p>{{ number_format($cities->count()) }} {{ Str::plural('city', $cities->count()) }} configured</p></div></div>
            @if ($cities->isEmpty())
                <div class="admin-empty-state"><span><i class="fas fa-city"></i></span><h3>No cities</h3><p>Add cities for customer addresses and service availability.</p></div>
            @else
                <div class="table-responsive"><table class="table admin-table">
                    <thead><tr><th>City</th><th>Status</th><th>Update</th><th>Action</th></tr></thead>
                    <tbody>@foreach ($cities as $city)<tr>
                        <td><strong>{{ $city->name }}</strong></td>
                        <td><span class="admin-status {{ $city->active ? 'admin-status-active' : 'admin-status-inactive' }}">{{ $city->active ? 'Active' : 'Inactive' }}</span></td>
                        <td><form class="d-flex align-items-center" style="gap: 10px" method="POST" action="{{ route('dashboard.cities.update', $city) }}">@csrf @method('PUT')<input class="form-control form-control-sm" name="name" value="{{ $city->name }}" maxlength="120" aria-label="City name" required><input type="hidden" name="active" value="0"><label class="mb-0 text-nowrap"><input name="active" type="checkbox" value="1" @checked($city->active)> Active</label><button class="btn admin-btn-secondary btn-sm" type="submit">Save</button></form></td>
                        <td><form method="POST" action="{{ route('dashboard.cities.destroy', $city) }}">@csrf @method('DELETE')<button class="btn admin-btn-secondary btn-sm" type="submit" onclick="return confirm('Remove this city? Existing records will not be changed.')"><i class="fas fa-trash-alt mr-1"></i>Delete</button></form></td>
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </div>
    </div></section>
</div>
@endsection
