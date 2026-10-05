@extends('dashboard.template.main')
@section('content')
<div class="content-wrapper admin-content-wrapper"><section class="content-header"><div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Merchant billing</span><h1>{{ $statement->reference }}</h1><p>{{ $statement->isPublished() ? 'Published and permanently locked' : 'Private draft awaiting publication' }} for {{ $statement->partner?->restaurant_name }}.</p></div><a class="btn admin-btn-secondary" href="{{ route('dashboard.statements.index') }}"><i class="fas fa-arrow-left mr-1"></i>All statements</a></div></div></section><section class="content"><div class="container-fluid">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if($statement->isDraft())
<div class="statement-draft-actions"><div><i class="fas fa-eye-slash"></i><span><strong>Private draft</strong>This statement is not visible to the merchant. Review every order and amount before publishing.</span></div><div><form method="POST" action="{{ route('dashboard.statements.destroy', $statement) }}" onsubmit="return confirm('Delete this draft? Its orders will become available for processing again.')">@csrf @method('DELETE')<button class="btn btn-outline-danger" type="submit"><i class="fas fa-trash-alt mr-1"></i>Delete draft</button></form><form method="POST" action="{{ route('dashboard.statements.publish', $statement) }}" onsubmit="return confirm('Publish this statement to the merchant? This cannot be undone or deleted.')">@csrf<button class="btn admin-btn-primary" type="submit"><i class="fas fa-paper-plane mr-1"></i>Publish to merchant</button></form></div></div>
@else
<div class="alert alert-light statement-read-only"><i class="fas fa-lock mr-2"></i>This statement is visible to the merchant and permanently locked. It cannot be deleted, changed, or generated again.</div>
@endif
<div class="card admin-card"><div class="card-body">@include('statements.partials.details')</div></div></div></section></div>
@endsection
