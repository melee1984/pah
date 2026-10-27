@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Notification history</span>
                    <h1>{{ $notification->title }}</h1>
                    <p>Reference {{ $notification->reference }}</p>
                </div>
                <a class="btn admin-btn-secondary" href="{{ route('dashboard.push-notifications.index') }}"><i class="fas fa-arrow-left mr-2"></i>Back to history</a>
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

            <div class="push-result-strip">
                <div><small>Status</small><strong><span class="admin-status {{ $notification->status_css_class }}">{{ $notification->status_label }}</span></strong></div>
                <div><small>Targeted devices</small><strong>{{ number_format($notification->recipient_count) }}</strong></div>
                <div><small>Successfully sent</small><strong class="text-success">{{ number_format($notification->success_count) }}</strong></div>
                <div><small>Failed attempts</small><strong class="{{ $notification->failed_count ? 'text-danger' : '' }}">{{ number_format($notification->failed_count) }}</strong></div>
            </div>

            @if ($notification->last_error)
                <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $notification->last_error }}</div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card admin-card mb-4">
                        <div class="admin-card-header"><div><h2>Content and audience</h2><p>What was sent and who was targeted.</p></div><span class="push-category push-category-{{ $notification->category }}">{{ $notification->category_label }}</span></div>
                        <div class="card-body">
                            <dl class="push-detail-list">
                                <div><dt>Audience</dt><dd>{{ $notification->audience_label }}</dd></div>
                                <div><dt>Created by admin</dt><dd>User #{{ $notification->created_by ?: 'unknown' }}</dd></div>
                                <div><dt>Confirmed</dt><dd>{{ $notification->confirmed_at?->format('M d, Y · g:i A') ?? 'Not confirmed' }}</dd></div>
                                <div><dt>Scheduled for</dt><dd>{{ $notification->scheduled_at?->format('M d, Y · g:i A') ?? 'Immediate delivery' }}</dd></div>
                                <div><dt>Completed</dt><dd>{{ $notification->sent_at?->format('M d, Y · g:i A') ?? 'Not completed' }}</dd></div>
                                <div><dt>App screen link</dt><dd>{{ $notification->deep_link ?: 'No link' }}</dd></div>
                                <div class="push-detail-wide"><dt>Message</dt><dd>{{ $notification->message }}</dd></div>
                            </dl>
                            @if ($notification->image_url)
                                <div class="push-review-image"><span>Attached image</span><img src="{{ $notification->image_url }}" alt="Notification image"></div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card admin-card mb-4">
                        <div class="admin-card-header"><div><h2>Device preview</h2><p>Approximate mobile appearance.</p></div></div>
                        <div class="card-body">
                            <div class="push-preview-notification push-preview-static">
                                <div class="push-preview-head"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><strong>Pahatud</strong><span>sent</span></div>
                                @if ($notification->image_url)<div class="push-preview-image"><img src="{{ $notification->image_url }}" alt="Notification image"></div>@endif
                                <h3>{{ $notification->title }}</h3>
                                <p>{{ $notification->message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header"><div><h2>Delivery attempts</h2><p>Device tokens are represented by a one-way hash and are never displayed or stored here.</p></div></div>
                @if ($deliveries->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-hourglass-half"></i></span><h3>No attempts recorded yet</h3><p>The queued or scheduled notification has not started delivery.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table push-delivery-table">
                            <thead><tr><th>Recipient</th><th>Type</th><th>Device reference</th><th>Attempted</th><th>Status</th><th>Error</th></tr></thead>
                            <tbody>
                            @foreach ($deliveries as $delivery)
                                <tr>
                                    <td><strong>{{ $delivery->recipient_label ?: 'Recipient #'.$delivery->recipient_id }}</strong><small>ID {{ $delivery->recipient_id }}</small></td>
                                    <td>{{ ucfirst($delivery->recipient_type) }}</td>
                                    <td><code>{{ substr($delivery->token_hash, 0, 12) }}…</code></td>
                                    <td>{{ ($delivery->sent_at ?? $delivery->updated_at)?->format('M d, Y · g:i:s A') }}</td>
                                    <td><span class="admin-status {{ $delivery->status === 'sent' ? 'admin-status-active' : ($delivery->status === 'failed' ? 'admin-status-inactive' : 'admin-status-pending') }}">{{ ucfirst($delivery->status) }}</span></td>
                                    <td><span class="push-delivery-error">{{ $delivery->error ?: '—' }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $deliveries->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
