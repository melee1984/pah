@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Final review</span>
                    <h1>Confirm notification</h1>
                    <p>Check the content, audience, and delivery time before it is queued.</p>
                </div>
                <a class="btn admin-btn-secondary" href="{{ route('dashboard.push-notifications.index') }}"><i class="fas fa-arrow-left mr-2"></i>Back to history</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>
            @endif

            <div class="push-confirm-banner {{ $notification->recipient_count === 0 ? 'is-empty' : '' }}">
                <span><i class="fas {{ $notification->recipient_count === 0 ? 'fa-exclamation-triangle' : 'fa-users' }}"></i></span>
                <div>
                    <small>Deliverable recipient count</small>
                    <strong>{{ number_format($notification->recipient_count) }} {{ Str::plural('device', $notification->recipient_count) }}</strong>
                    <p>{{ $notification->recipient_count === 0 ? 'No eligible devices currently match this audience. The notification cannot be confirmed.' : 'This count was checked against current push tokens and will be checked once more when you confirm.' }}</p>
                </div>
            </div>

            <div class="push-review-layout">
                <div class="card admin-card">
                    <div class="admin-card-header"><div><h2>Notification details</h2><p>Reference {{ $notification->reference }}</p></div><span class="push-category push-category-{{ $notification->category }}">{{ $notification->category_label }}</span></div>
                    <div class="card-body">
                        <dl class="push-detail-list">
                            <div><dt>Audience</dt><dd>{{ $notification->audience_label }}</dd></div>
                            <div><dt>Delivery</dt><dd>{{ $notification->scheduled_at ? $notification->scheduled_at->format('M d, Y · g:i A').' (Asia/Manila)' : 'Immediately after confirmation' }}</dd></div>
                            <div><dt>Title</dt><dd>{{ $notification->title }}</dd></div>
                            <div class="push-detail-wide"><dt>Message</dt><dd>{{ $notification->message }}</dd></div>
                            <div class="push-detail-wide"><dt>App screen link</dt><dd>{{ $notification->deep_link ?: 'No link' }}</dd></div>
                        </dl>
                        @if ($notification->image_url)
                            <div class="push-review-image"><span>Attached image</span><img src="{{ $notification->image_url }}" alt="Notification image"></div>
                        @endif
                    </div>
                </div>

                <aside class="push-preview-column">
                    <div class="push-phone-wrap">
                        <span class="admin-eyebrow">Device preview</span>
                        <div class="push-phone">
                            <div class="push-phone-status"><span>9:41</span><span><i class="fas fa-signal"></i>&nbsp; <i class="fas fa-wifi"></i>&nbsp; <i class="fas fa-battery-full"></i></span></div>
                            <div class="push-phone-screen">
                                <div class="push-preview-notification">
                                    <div class="push-preview-head"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><strong>Pahatud</strong><span>now</span></div>
                                    @if ($notification->image_url)<div class="push-preview-image"><img src="{{ $notification->image_url }}" alt="Notification image"></div>@endif
                                    <h3>{{ $notification->title }}</h3>
                                    <p>{{ $notification->message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="push-confirm-actions">
                <form method="POST" action="{{ route('dashboard.push-notifications.destroy-draft', $notification) }}" onsubmit="return confirm('Discard this notification draft?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn push-discard-button" type="submit"><i class="fas fa-trash-alt mr-2"></i>Discard draft</button>
                </form>
                <div>
                    <form class="d-inline" method="POST" action="{{ route('dashboard.push-notifications.confirm', $notification) }}" onsubmit="return confirm('Confirm this notification for {{ number_format($notification->recipient_count) }} deliverable devices?');">
                        @csrf
                        <button class="btn admin-btn-primary" type="submit" @disabled($notification->recipient_count === 0)>
                            <i class="fas {{ $notification->scheduled_at ? 'fa-calendar-check' : 'fa-paper-plane' }} mr-2"></i>{{ $notification->scheduled_at ? 'Confirm schedule' : 'Confirm and send' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
