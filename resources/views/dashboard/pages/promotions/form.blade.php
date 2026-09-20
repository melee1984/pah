@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Homepage content</span>
                    <h1>{{ $promotion->exists ? 'Edit promotion' : 'Add promotion' }}</h1>
                    <p>Upload a wide banner and control when it appears on the homepage.</p>
                </div>
                <a class="btn admin-btn-secondary" href="{{ route('dashboard.promotions.index') }}"><i class="fas fa-arrow-left mr-2"></i>Back to promotions</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>
            @endif

            <div class="card admin-card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="{{ $promotion->exists ? route('dashboard.promotions.update', $promotion) : route('dashboard.promotions.store') }}">
                        @csrf
                        @if ($promotion->exists) @method('PUT') @endif

                        <div class="admin-form-note"><i class="fas fa-image"></i><span>Recommended banner ratio: 2:1 or wider. Accepted formats are JPG, PNG, and WebP up to 5 MB.</span></div>

                        @if ($promotion->exists)
                            <div class="form-group"><img src="{{ $promotion->image_url }}" alt="Current banner" style="max-width: 520px; width: 100%; max-height: 240px; object-fit: cover; border-radius: 10px;"></div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 form-group"><label for="name">Promotion name</label><input class="form-control" id="name" name="name" value="{{ old('name', $promotion->name) }}" maxlength="255" required></div>
                            <div class="col-md-4 form-group"><label for="partner_id">Linked merchant</label><select class="form-control" id="partner_id" name="partner_id" required><option value="">Select merchant</option>@foreach ($partners as $partner)<option value="{{ $partner->id }}" @selected((string) old('partner_id', $promotion->partner_id) === (string) $partner->id)>{{ $partner->restaurant_name }}</option>@endforeach</select></div>
                            <div class="col-md-2 form-group"><label for="sort_order">Display order</label><input class="form-control" id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $promotion->sort_order ?? 0) }}" required></div>
                        </div>
                        <div class="form-group"><label for="subtitle">Subtitle</label><input class="form-control" id="subtitle" name="subtitle" value="{{ old('subtitle', $promotion->subtitle) }}" maxlength="255"></div>
                        <div class="form-group"><label for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3" maxlength="1000">{{ old('description', $promotion->description) }}</textarea></div>

                        <div class="row">
                            <div class="col-md-4 form-group"><label for="cta_label">Button label</label><input class="form-control" id="cta_label" name="cta_label" value="{{ old('cta_label', $promotion->cta_label) }}" maxlength="100" placeholder="Shop now"></div>
                            <div class="col-md-8 form-group"><label for="link_url">Optional fallback URL</label><input class="form-control" id="link_url" name="link_url" type="url" value="{{ old('link_url', $promotion->link_url) }}" maxlength="2048" placeholder="Leave blank to use the linked merchant page"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group"><label for="starts_at">Starts at</label><input class="form-control" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\\TH:i')) }}"></div>
                            <div class="col-md-6 form-group"><label for="ends_at">Ends at</label><input class="form-control" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d\\TH:i')) }}"></div>
                        </div>

                        <div class="form-group"><label for="image">{{ $promotion->exists ? 'Replace banner image' : 'Banner image' }}</label><input class="form-control-file" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" {{ $promotion->exists ? '' : 'required' }}></div>
                        <div class="form-group form-check"><input type="hidden" name="active" value="0"><input class="form-check-input" id="active" name="active" type="checkbox" value="1" @checked((bool) old('active', $promotion->active))><label class="form-check-label" for="active">Active and eligible to display</label></div>

                        <div class="d-flex justify-content-end mt-4">
                            <a class="btn admin-btn-secondary mr-2" href="{{ route('dashboard.promotions.index') }}">Cancel</a>
                            <button class="btn admin-btn-primary" type="submit">{{ $promotion->exists ? 'Save changes' : 'Create promotion' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
