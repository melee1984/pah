<?php

namespace App\Http\Controllers\Admin;

use App\AdminPushNotification;
use App\Http\Controllers\Controller;
use App\Jobs\SendAdminPushNotification;
use App\Services\AdminPushRecipientResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PushNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(AdminPushNotification::statuses()))],
            'category' => ['nullable', Rule::in(array_keys(AdminPushNotification::categories()))],
        ]);

        $notifications = AdminPushNotification::query()
            ->where('status', '!=', AdminPushNotification::STATUS_DRAFT)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest('confirmed_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => AdminPushNotification::query()->where('status', '!=', AdminPushNotification::STATUS_DRAFT)->count(),
            'scheduled' => AdminPushNotification::query()->where('status', AdminPushNotification::STATUS_SCHEDULED)->count(),
            'sent' => AdminPushNotification::query()->where('status', AdminPushNotification::STATUS_SENT)->count(),
            'failed' => AdminPushNotification::query()->whereIn('status', [
                AdminPushNotification::STATUS_FAILED,
                AdminPushNotification::STATUS_PARTIAL,
            ])->count(),
        ];

        return view('dashboard.pages.push-notifications.index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'statuses' => AdminPushNotification::statuses(),
            'categories' => AdminPushNotification::categories(),
        ]);
    }

    public function create(AdminPushRecipientResolver $resolver): View
    {
        return view('dashboard.pages.push-notifications.create', [
            'categories' => AdminPushNotification::categories(),
            'audiences' => AdminPushNotification::audiences(),
            'users' => $resolver->selectableUsers(),
            'riders' => $resolver->selectableRiders(),
        ]);
    }

    public function storePreview(Request $request, AdminPushRecipientResolver $resolver): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(AdminPushNotification::categories()))],
            'audience_type' => ['required', Rule::in(array_keys(AdminPushNotification::audiences()))],
            'target_ids' => [
                Rule::requiredIf(fn () => in_array($request->input('audience_type'), [
                    AdminPushNotification::AUDIENCE_SELECTED_USERS,
                    AdminPushNotification::AUDIENCE_SELECTED_RIDERS,
                ], true)),
                'nullable',
                'array',
                'min:1',
            ],
            'target_ids.*' => ['integer', 'min:1', 'distinct'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'deep_link' => ['nullable', 'string', 'max:2048'],
            'send_mode' => ['required', Rule::in(['now', 'scheduled'])],
            'scheduled_at' => ['required_if:send_mode,scheduled', 'nullable', 'date', 'after:now'],
        ], [
            'target_ids.required' => 'Choose at least one recipient.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
        ]);

        $imagePath = $request->hasFile('image') ? $this->storeImage($request) : null;

        try {
            $notification = DB::transaction(function () use ($data, $imagePath, $resolver) {
                $targetIds = array_values($data['target_ids'] ?? []);
                $scheduledAt = $data['send_mode'] === 'scheduled' ? $data['scheduled_at'] : null;
                $recipientCount = $resolver->count($data['audience_type'], $targetIds, $data['category']);

                return AdminPushNotification::create([
                    'reference' => (string) Str::uuid(),
                    'created_by' => auth()->id(),
                    'category' => $data['category'],
                    'audience_type' => $data['audience_type'],
                    'target_ids' => $targetIds ?: null,
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'image_path' => $imagePath,
                    'deep_link' => filled($data['deep_link'] ?? null) ? trim($data['deep_link']) : null,
                    'status' => AdminPushNotification::STATUS_DRAFT,
                    'scheduled_at' => $scheduledAt,
                    'recipient_count' => $recipientCount,
                ]);
            });
        } catch (Throwable $exception) {
            if ($imagePath) {
                $this->deleteImage($imagePath);
            }

            throw $exception;
        }

        return redirect()->route('dashboard.push-notifications.preview', $notification);
    }

    public function preview(AdminPushNotification $notification, AdminPushRecipientResolver $resolver): View|RedirectResponse
    {
        if ($notification->status !== AdminPushNotification::STATUS_DRAFT) {
            return redirect()->route('dashboard.push-notifications.show', $notification);
        }

        $notification->update([
            'recipient_count' => $resolver->count(
                $notification->audience_type,
                $notification->target_ids ?? [],
                $notification->category,
            ),
        ]);

        return view('dashboard.pages.push-notifications.preview', compact('notification'));
    }

    public function confirm(AdminPushNotification $notification, AdminPushRecipientResolver $resolver): RedirectResponse
    {
        if ($notification->status !== AdminPushNotification::STATUS_DRAFT) {
            return redirect()->route('dashboard.push-notifications.show', $notification)
                ->with('success', 'This notification has already been confirmed.');
        }

        $recipientCount = $resolver->count(
            $notification->audience_type,
            $notification->target_ids ?? [],
            $notification->category,
        );

        if ($recipientCount === 0) {
            $notification->update(['recipient_count' => 0]);

            return back()->withErrors([
                'recipients' => 'No eligible devices are available for this audience. Update the recipients or wait until a device registers for push notifications.',
            ]);
        }

        $scheduled = $notification->scheduled_at?->isFuture() ?? false;
        $newStatus = $scheduled
            ? AdminPushNotification::STATUS_SCHEDULED
            : AdminPushNotification::STATUS_QUEUED;

        $updated = AdminPushNotification::query()
            ->whereKey($notification->id)
            ->where('status', AdminPushNotification::STATUS_DRAFT)
            ->update([
                'status' => $newStatus,
                'recipient_count' => $recipientCount,
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            return redirect()->route('dashboard.push-notifications.show', $notification);
        }

        if (! $scheduled) {
            SendAdminPushNotification::dispatch($notification->id);
        }

        return redirect()->route('dashboard.push-notifications.index')->with(
            'success',
            $scheduled
                ? 'Notification scheduled for '.$notification->scheduled_at->format('M d, Y \a\t g:i A').'.'
                : 'Notification queued for immediate delivery.',
        );
    }

    public function show(AdminPushNotification $notification): View
    {
        $deliveries = $notification->deliveries()
            ->orderByRaw("CASE status WHEN 'failed' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->latest('id')
            ->paginate(50);

        return view('dashboard.pages.push-notifications.show', compact('notification', 'deliveries'));
    }

    public function destroyDraft(AdminPushNotification $notification): RedirectResponse
    {
        if ($notification->status !== AdminPushNotification::STATUS_DRAFT) {
            return redirect()->route('dashboard.push-notifications.show', $notification)
                ->withErrors(['notification' => 'Only an unconfirmed draft can be discarded.']);
        }

        $imagePath = $notification->image_path;
        $notification->delete();

        if ($imagePath) {
            $this->deleteImage($imagePath);
        }

        return redirect()->route('dashboard.push-notifications.create')
            ->with('success', 'Draft discarded.');
    }

    private function storeImage(Request $request): string
    {
        $image = $request->file('image');
        $directory = public_path('uploads/push-notifications');
        File::ensureDirectoryExists($directory);
        $filename = Str::uuid().'.'.$image->extension();
        $image->move($directory, $filename);

        return 'uploads/push-notifications/'.$filename;
    }

    private function deleteImage(string $imagePath): void
    {
        $root = realpath(public_path('uploads/push-notifications'));
        $fullPath = realpath(public_path($imagePath));

        if ($root && $fullPath && str_starts_with($fullPath, $root.DIRECTORY_SEPARATOR)) {
            File::delete($fullPath);
        }
    }
}
