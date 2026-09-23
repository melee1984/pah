@extends('merchant.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div><span class="admin-eyebrow">Store marketing</span><h1>Promotions</h1><p>Create banners for your store and submit them for administrator approval.</p></div>
                <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.promotions.index') }}">Promotions</a></li><li class="breadcrumb-item active">{{ $promotion->exists ? 'Edit' : 'Add' }}</li></ol>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="merchant-settings-page">
                @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif

                <div class="merchant-form-shell merchant-form-shell-wide">
                    <div class="card admin-card merchant-form-card">
                        <div class="admin-card-header">
                            <div><span class="admin-eyebrow">Promotion details</span><h2>{{ $promotion->exists ? 'Edit promotion' : 'Add a promotion' }}</h2><p>Use a clear banner and schedule. Changes remain unpublished until approved.</p></div>
                            @if ($promotion->exists)<form method="POST" action="{{ route('merchant.dashboard.promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion? This cannot be undone.');">@csrf @method('DELETE')<button class="btn merchant-danger-button" type="submit"><i class="fas fa-trash-alt mr-2"></i>Delete</button></form>@endif
                        </div>

                        <form class="merchant-settings-form" method="POST" enctype="multipart/form-data" action="{{ $promotion->exists ? route('merchant.dashboard.promotions.update', $promotion) : route('merchant.dashboard.promotions.store') }}">
                            @csrf @if ($promotion->exists) @method('PUT') @endif

                            <div class="dashboard-page-note"><span><i class="fas fa-user-check"></i></span><div><strong>Administrator approval required</strong><p>New promotions and edits to approved promotions will not appear in the app until reviewed.</p></div></div>

                            <div class="merchant-toggle-panel">
                                <div><strong>Publish after approval</strong><small>The promotion will display only while active and within its scheduled dates.</small></div>
                                <label class="merchant-toggle" for="active"><input id="active" name="active" type="checkbox" value="1" @checked((bool) old('active', $promotion->active))><span><i></i></span><strong>Active</strong></label>
                            </div>

                            <div class="merchant-form-grid">
                                <div class="form-group merchant-form-span"><label for="name">Promotion name</label><input class="form-control" id="name" name="name" value="{{ old('name', $promotion->name) }}" maxlength="255" placeholder="e.g. Weekend special" required></div>
                                <div class="form-group merchant-form-span"><label for="subtitle">Subtitle <small>(optional)</small></label><input class="form-control" id="subtitle" name="subtitle" value="{{ old('subtitle', $promotion->subtitle) }}" maxlength="255" placeholder="A short message shown with the promotion"></div>
                                <div class="form-group merchant-form-span"><label for="description">Description <small>(optional)</small></label><textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Describe the promotion">{{ old('description', $promotion->description) }}</textarea></div>
                                <div class="form-group"><label for="cta_label">Button label <small>(optional)</small></label><input class="form-control" id="cta_label" name="cta_label" value="{{ old('cta_label', $promotion->cta_label) }}" maxlength="100" placeholder="Shop now"></div>
                                <div class="form-group"><label for="sort_order">Display order</label><input class="form-control" id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $promotion->sort_order ?? 0) }}" required><small class="merchant-field-help">Lower numbers display first.</small></div>
                                <div class="form-group merchant-form-span"><label for="link_url">Fallback URL <small>(optional)</small></label><input class="form-control" id="link_url" name="link_url" type="url" value="{{ old('link_url', $promotion->link_url) }}" maxlength="2048" placeholder="Leave blank to open your merchant page"></div>
                                <div class="form-group"><label for="starts_at">Starts at <small>(optional)</small></label><input class="form-control" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\\TH:i')) }}"></div>
                                <div class="form-group"><label for="ends_at">Ends at <small>(optional)</small></label><input class="form-control" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d\\TH:i')) }}"></div>
                                <div class="form-group merchant-form-span">
                                    <label for="image">{{ $promotion->exists ? 'Replace banner image' : 'Banner image' }}</label>
                                    @if ($promotion->exists)<div class="merchant-media-preview merchant-banner-preview merchant-promotion-preview"><img src="{{ $promotion->image_url }}" alt="Current promotion banner"></div>@endif
                                    <div class="custom-file"><input class="custom-file-input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" {{ $promotion->exists ? '' : 'required' }} onchange="this.nextElementSibling.textContent = this.files.length ? this.files[0].name : 'Choose banner image'"><label class="custom-file-label" for="image">Choose banner image</label></div>
                                    <small class="merchant-field-help">JPG, PNG, or WebP up to 5 MB. A wide 2:1 image works best.</small>
                                </div>
                            </div>

                            <div class="merchant-form-actions"><a class="btn admin-btn-secondary" href="{{ route('merchant.dashboard.promotions.index') }}">Cancel</a><button class="btn admin-btn-primary" type="submit">Submit for approval</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
