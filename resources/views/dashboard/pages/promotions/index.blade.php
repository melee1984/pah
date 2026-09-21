@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Homepage content</span>
                    <h1>Promotions</h1>
                    <p>Manage the sale and promotion banners shown on the Pahatud homepage.</p>
                </div>
                <a class="btn admin-btn-primary" href="{{ route('dashboard.promotions.create') }}">
                    <i class="fas fa-plus mr-2"></i>Add promotion
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
            @endif

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div><h2>Promotion banners</h2><p>{{ number_format($promotions->total()) }} {{ Str::plural('banner', $promotions->total()) }}</p></div>
                </div>

                @if ($promotions->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-bullhorn"></i></span><h3>No promotions yet</h3><p>Add the first banner to feature a promotion on the homepage.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Banner</th><th>Promotion</th><th>Linked merchant</th><th>Schedule</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            @foreach ($promotions as $promotion)
                                <tr>
                                    <td><img src="{{ $promotion->image_url }}" alt="{{ $promotion->name }}" style="width: 150px; height: 68px; object-fit: cover; border-radius: 8px;"></td>
                                    <td><strong>{{ $promotion->name }}</strong><small>{{ $promotion->subtitle ?: 'No subtitle' }}</small></td>
                                    <td><strong>{{ $promotion->partner?->restaurant_name ?? 'Merchant unavailable' }}</strong><small>{{ $promotion->partner?->slug ? '/restaurant/'.$promotion->partner->slug : 'Select a merchant' }}</small></td>
                                    <td>
                                        <strong>{{ $promotion->starts_at?->format('M d, Y · g:i A') ?? 'Immediately' }}</strong>
                                        <small>Until {{ $promotion->ends_at?->format('M d, Y · g:i A') ?? 'no end date' }}</small>
                                    </td>
                                    <td><span class="admin-number-pill">{{ $promotion->sort_order }}</span></td>
                                    <td>
                                        @if (! $promotion->active)
                                            <span class="admin-status admin-status-inactive">Inactive</span>
                                        @elseif ($promotion->starts_at && $promotion->starts_at->isFuture())
                                            <span class="admin-status admin-status-pending">Scheduled</span>
                                        @elseif ($promotion->ends_at && $promotion->ends_at->isPast())
                                            <span class="admin-status admin-status-inactive">Expired</span>
                                        @else
                                            <span class="admin-status admin-status-active">Live</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a class="btn admin-btn-secondary btn-sm mr-2" href="{{ route('dashboard.promotions.edit', $promotion) }}">Edit</a>
                                            <form method="POST" action="{{ route('dashboard.promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $promotions->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
