@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">App communication</span>
                    <h1>Create push notification</h1>
                    <p>Compose the message, choose its audience, then review the exact delivery count.</p>
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

            <form method="POST" enctype="multipart/form-data" action="{{ route('dashboard.push-notifications.store-preview') }}" id="pushComposer">
                @csrf
                <div class="push-compose-layout">
                    <div class="push-compose-main">
                        <div class="card admin-card push-form-card">
                            <div class="admin-card-header"><div><h2>1. Choose recipients</h2><p>Only registered devices with a current push token are counted.</p></div></div>
                            <div class="card-body">
                                <div class="push-audience-grid">
                                    @foreach ($audiences as $value => $label)
                                        <label class="push-choice">
                                            <input type="radio" name="audience_type" value="{{ $value }}" @checked(old('audience_type', \App\AdminPushNotification::AUDIENCE_ALL_USERS) === $value) required>
                                            <span><i class="fas {{ str_contains($value, 'rider') ? 'fa-motorcycle' : ($value === 'merchants' ? 'fa-store' : 'fa-users') }}"></i><strong>{{ $label }}</strong></span>
                                        </label>
                                    @endforeach
                                </div>

                                <div class="push-target-picker" id="selectedUsersPicker">
                                    <label for="selectedUsers">Choose users</label>
                                    <select class="form-control" id="selectedUsers" name="target_ids[]" multiple size="8" disabled>
                                        @forelse ($users as $user)
                                            <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', (array) old('target_ids', [])), true))>
                                                {{ trim($user->firstname.' '.$user->lastname) ?: 'User #'.$user->id }}{{ $user->email ? ' — '.$user->email : ($user->mobile ? ' — '.$user->mobile : '') }}
                                            </option>
                                        @empty
                                            <option disabled>No customers currently have a mobile push token.</option>
                                        @endforelse
                                    </select>
                                    <small>Hold Command on Mac or Ctrl on Windows to select more than one user.</small>
                                </div>

                                <div class="push-target-picker" id="selectedRidersPicker">
                                    <label for="selectedRiders">Choose riders</label>
                                    <select class="form-control" id="selectedRiders" name="target_ids[]" multiple size="8" disabled>
                                        @forelse ($riders as $rider)
                                            <option value="{{ $rider->id }}" @selected(in_array((string) $rider->id, array_map('strval', (array) old('target_ids', [])), true))>
                                                {{ $rider->name ?: 'Rider #'.$rider->id }}{{ $rider->mobile ? ' — '.$rider->mobile : '' }}
                                            </option>
                                        @empty
                                            <option disabled>No riders currently have an active app device.</option>
                                        @endforelse
                                    </select>
                                    <small>Promotion recipients are limited to riders who opted in to marketing messages.</small>
                                </div>
                            </div>
                        </div>

                        <div class="card admin-card push-form-card">
                            <div class="admin-card-header"><div><h2>2. Compose notification</h2><p>Keep the title short and put the most useful information first.</p></div></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-5 form-group">
                                        <label for="category">Category</label>
                                        <select class="form-control" id="category" name="category" required>
                                            @foreach ($categories as $value => $label)
                                                <option value="{{ $value }}" @selected(old('category', \App\AdminPushNotification::CATEGORY_GENERAL) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-7 form-group">
                                        <label for="title">Title <small><span id="titleCount">0</span>/120</small></label>
                                        <input class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="120" placeholder="Your order is on the way" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="message">Message <small><span id="messageCount">0</span>/500</small></label>
                                    <textarea class="form-control" id="message" name="message" rows="5" maxlength="500" placeholder="Write a clear, helpful message for the recipient." required>{{ old('message') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="deepLink">App screen link <small>(optional)</small></label>
                                    <input class="form-control" id="deepLink" name="deep_link" value="{{ old('deep_link') }}" maxlength="2048" placeholder="pahatud://orders/123 or /promotions">
                                    <small>Use a screen path or deep link supported by the mobile app.</small>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="image">Image <small>(optional)</small></label>
                                    <input class="form-control-file" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                                    <small>JPG, PNG, or WebP up to 2 MB. A wide image works best across devices.</small>
                                </div>
                            </div>
                        </div>

                        <div class="card admin-card push-form-card">
                            <div class="admin-card-header"><div><h2>3. Choose delivery time</h2><p>Send now or schedule using Asia/Manila time.</p></div></div>
                            <div class="card-body">
                                <div class="push-schedule-grid">
                                    <label class="push-choice push-delivery-choice">
                                        <input type="radio" name="send_mode" value="now" @checked(old('send_mode', 'now') === 'now') required>
                                        <span><i class="fas fa-bolt"></i><strong>Send immediately</strong><small>Queue the notification after confirmation.</small></span>
                                    </label>
                                    <label class="push-choice push-delivery-choice">
                                        <input type="radio" name="send_mode" value="scheduled" @checked(old('send_mode') === 'scheduled') required>
                                        <span><i class="fas fa-calendar-alt"></i><strong>Schedule for later</strong><small>Send automatically at the chosen time.</small></span>
                                    </label>
                                </div>
                                <div class="push-scheduled-at" id="scheduledAtPanel">
                                    <label for="scheduledAt">Date and time</label>
                                    <input class="form-control" id="scheduledAt" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" disabled>
                                    <small>Timezone: Asia/Manila (UTC+8)</small>
                                </div>
                            </div>
                        </div>

                        <div class="push-compose-actions">
                            <a class="btn admin-btn-secondary" href="{{ route('dashboard.push-notifications.index') }}">Cancel</a>
                            <button class="btn admin-btn-primary" type="submit"><i class="fas fa-eye mr-2"></i>Preview and confirm</button>
                        </div>
                    </div>

                    <aside class="push-preview-column">
                        <div class="push-phone-wrap">
                            <span class="admin-eyebrow">Live preview</span>
                            <div class="push-phone">
                                <div class="push-phone-status"><span>9:41</span><span><i class="fas fa-signal"></i>&nbsp; <i class="fas fa-wifi"></i>&nbsp; <i class="fas fa-battery-full"></i></span></div>
                                <div class="push-phone-screen">
                                    <div class="push-preview-notification">
                                        <div class="push-preview-head"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><strong>Pahatud</strong><span>now</span></div>
                                        <div class="push-preview-image" id="previewImageWrap" hidden><img id="previewImage" alt="Selected notification image"></div>
                                        <h3 id="previewTitle">Notification title</h3>
                                        <p id="previewMessage">Your message will appear here as you type.</p>
                                    </div>
                                </div>
                            </div>
                            <p class="push-preview-note">Appearance varies slightly by device and operating system.</p>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const audienceInputs = document.querySelectorAll('input[name="audience_type"]');
    const userPicker = document.getElementById('selectedUsersPicker');
    const riderPicker = document.getElementById('selectedRidersPicker');
    const userSelect = document.getElementById('selectedUsers');
    const riderSelect = document.getElementById('selectedRiders');
    const sendModeInputs = document.querySelectorAll('input[name="send_mode"]');
    const scheduledPanel = document.getElementById('scheduledAtPanel');
    const scheduledInput = document.getElementById('scheduledAt');
    const title = document.getElementById('title');
    const message = document.getElementById('message');
    const image = document.getElementById('image');

    function updateAudience() {
        const selected = document.querySelector('input[name="audience_type"]:checked')?.value;
        const showUsers = selected === 'selected_users';
        const showRiders = selected === 'selected_riders';
        userPicker.classList.toggle('is-visible', showUsers);
        riderPicker.classList.toggle('is-visible', showRiders);
        userSelect.disabled = !showUsers;
        riderSelect.disabled = !showRiders;
    }

    function updateSchedule() {
        const scheduled = document.querySelector('input[name="send_mode"]:checked')?.value === 'scheduled';
        scheduledPanel.classList.toggle('is-visible', scheduled);
        scheduledInput.disabled = !scheduled;
        scheduledInput.required = scheduled;
    }

    function updateTextPreview() {
        document.getElementById('previewTitle').textContent = title.value.trim() || 'Notification title';
        document.getElementById('previewMessage').textContent = message.value.trim() || 'Your message will appear here as you type.';
        document.getElementById('titleCount').textContent = title.value.length;
        document.getElementById('messageCount').textContent = message.value.length;
    }

    audienceInputs.forEach(input => input.addEventListener('change', updateAudience));
    sendModeInputs.forEach(input => input.addEventListener('change', updateSchedule));
    title.addEventListener('input', updateTextPreview);
    message.addEventListener('input', updateTextPreview);
    image.addEventListener('change', function () {
        const wrap = document.getElementById('previewImageWrap');
        const preview = document.getElementById('previewImage');
        const file = this.files && this.files[0];
        if (!file) {
            wrap.hidden = true;
            preview.removeAttribute('src');
            return;
        }
        preview.src = URL.createObjectURL(file);
        wrap.hidden = false;
    });

    updateAudience();
    updateSchedule();
    updateTextPreview();
});
</script>
@endsection
