@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">App communication</span>
                    <h1>Push notifications</h1>
                    <p>Send timely updates to customers, riders, and merchant devices.</p>
                </div>
                <a class="btn admin-btn-primary" href="{{ route('dashboard.push-notifications.create') }}">
                    <i class="fas fa-plus mr-2"></i>Create notification
                </a>
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

            <div class="admin-stat-grid push-stat-grid">
                <div class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-paper-plane"></i></span><div><small>Confirmed notifications</small><strong>{{ number_format($stats['total']) }}</strong></div></div>
                <div class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-clock"></i></span><div><small>Scheduled</small><strong>{{ number_format($stats['scheduled']) }}</strong></div></div>
                <div class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-check"></i></span><div><small>Sent successfully</small><strong>{{ number_format($stats['sent']) }}</strong></div></div>
                <div class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-exclamation-triangle"></i></span><div><small>Needs attention</small><strong>{{ number_format($stats['failed']) }}</strong></div></div>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header push-history-header">
                    <div><h2>Notification history</h2><p>Content, audience, timing, and delivery outcome for every confirmed notification.</p></div>
                    <form method="GET" class="push-filter-form">
                        <select class="form-control" name="category" aria-label="Filter by category">
                            <option value="">All categories</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="form-control" name="status" aria-label="Filter by status">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $value => $label)
                                @if ($value !== \App\AdminPushNotification::STATUS_DRAFT)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button class="btn admin-btn-secondary" type="submit">Filter</button>
                        @if (request()->hasAny(['category', 'status']))
                            <a class="push-clear-filter" href="{{ route('dashboard.push-notifications.index') }}" aria-label="Clear filters"><i class="fas fa-times"></i></a>
                        @endif
                    </form>
                </div>

                @if ($notifications->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-bell"></i></span><h3>No notifications found</h3><p>Create a notification or clear the current filters.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table push-history-table">
                            <thead><tr><th>Notification</th><th>Category</th><th>Audience</th><th>Delivery time</th><th>Results</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($notifications as $notification)
                                <tr>
                                    <td><strong>{{ $notification->title }}</strong><small>{{ Str::limit($notification->message, 75) }}</small></td>
                                    <td><span class="push-category push-category-{{ $notification->category }}">{{ $notification->category_label }}</span></td>
                                    <td><strong>{{ $notification->audience_label }}</strong><small>{{ number_format($notification->recipient_count) }} deliverable {{ Str::plural('device', $notification->recipient_count) }}</small></td>
                                    <td>
                                        <strong>{{ ($notification->scheduled_at ?? $notification->confirmed_at)?->format('M d, Y · g:i A') }}</strong>
                                        <small>{{ $notification->scheduled_at ? 'Scheduled' : 'Sent immediately' }}</small>
                                    </td>
                                    <td><strong>{{ number_format($notification->success_count) }} sent</strong><small class="{{ $notification->failed_count ? 'text-danger' : '' }}">{{ number_format($notification->failed_count) }} failed</small></td>
                                    <td><span class="admin-status {{ $notification->status_css_class }}">{{ $notification->status_label }}</span></td>
                                    <td><a class="btn admin-btn-secondary push-view-button" href="{{ route('dashboard.push-notifications.show', $notification) }}">View</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $notifications->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
