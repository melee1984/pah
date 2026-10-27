<?php

namespace App\Jobs;

use App\AdminPushNotification;
use App\AdminPushNotificationDelivery;
use App\Services\AdminPushRecipientResolver;
use App\Services\FirebasePush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SendAdminPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1200;

    public function __construct(public readonly int $notificationId) {}

    public function handle(AdminPushRecipientResolver $resolver, FirebasePush $push): void
    {
        $claimed = AdminPushNotification::query()
            ->whereKey($this->notificationId)
            ->where('status', AdminPushNotification::STATUS_QUEUED)
            ->update([
                'status' => AdminPushNotification::STATUS_PROCESSING,
                'last_error' => null,
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $notification = AdminPushNotification::find($this->notificationId);
        if (! $notification) {
            return;
        }

        try {
            $recipients = $resolver->resolve(
                $notification->audience_type,
                $notification->target_ids ?? [],
                $notification->category,
            );

            $notification->update([
                'recipient_count' => $recipients->count(),
                'success_count' => 0,
                'failed_count' => 0,
            ]);

            if ($recipients->isEmpty()) {
                $notification->update([
                    'status' => AdminPushNotification::STATUS_FAILED,
                    'failed_count' => 0,
                    'sent_at' => now(),
                    'last_error' => 'No recipients currently have an eligible push-notification device.',
                ]);

                return;
            }

            $successCount = 0;
            $failedCount = 0;
            $riderNotificationsCreated = [];
            $imageUrl = $notification->image_url;

            foreach ($recipients as $recipient) {
                $tokenHash = hash('sha256', $recipient['token']);
                $delivery = AdminPushNotificationDelivery::firstOrCreate(
                    [
                        'notification_id' => $notification->id,
                        'token_hash' => $tokenHash,
                    ],
                    [
                        'recipient_type' => $recipient['type'],
                        'recipient_id' => $recipient['id'],
                        'recipient_label' => $recipient['label'],
                        'status' => 'pending',
                    ],
                );

                if ($delivery->status === 'sent') {
                    $successCount++;

                    continue;
                }

                if ($recipient['type'] === 'rider' && ! isset($riderNotificationsCreated[$recipient['id']])) {
                    $this->createRiderInboxNotification($notification, $recipient['id'], $imageUrl);
                    $riderNotificationsCreated[$recipient['id']] = true;
                }

                try {
                    $messageId = $push->send(
                        $recipient['token'],
                        $notification->title,
                        $notification->message,
                        [
                            'type' => 'admin_notification',
                            'notification_id' => $notification->reference,
                            'category' => $notification->category,
                            'deep_link' => $notification->deep_link ?? '',
                            'image_url' => $imageUrl ?? '',
                        ],
                        $imageUrl,
                    );

                    $delivery->update([
                        'status' => 'sent',
                        'provider_message_id' => mb_substr($messageId, 0, 255),
                        'error' => null,
                        'sent_at' => now(),
                    ]);
                    $successCount++;
                } catch (Throwable $exception) {
                    $delivery->update([
                        'status' => 'failed',
                        'error' => mb_substr($exception->getMessage(), 0, 2000),
                    ]);
                    $failedCount++;

                    Log::warning('Admin push notification delivery failed.', [
                        'notification_id' => $notification->id,
                        'recipient_type' => $recipient['type'],
                        'recipient_id' => $recipient['id'],
                        'token_hash' => $tokenHash,
                        'error' => $exception->getMessage(),
                    ]);
                }

                $notification->forceFill([
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ])->save();
            }

            $status = $failedCount === 0
                ? AdminPushNotification::STATUS_SENT
                : ($successCount > 0 ? AdminPushNotification::STATUS_PARTIAL : AdminPushNotification::STATUS_FAILED);

            $notification->update([
                'status' => $status,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'sent_at' => now(),
                'last_error' => $failedCount > 0
                    ? $failedCount.' '.Str::plural('delivery', $failedCount).' failed. Open the notification for details.'
                    : null,
            ]);
        } catch (Throwable $exception) {
            $notification->update([
                'status' => AdminPushNotification::STATUS_FAILED,
                'sent_at' => now(),
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            Log::error('Admin push notification job failed.', [
                'notification_id' => $notification->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function createRiderInboxNotification(AdminPushNotification $notification, int $riderId, ?string $imageUrl): void
    {
        if (! Schema::hasTable('rider_api_notifications')) {
            return;
        }

        DB::table('rider_api_notifications')->insert([
            'reference' => (string) Str::uuid(),
            'rider_id' => $riderId,
            'type' => 'admin_'.$notification->category,
            'title' => $notification->title,
            'body' => $notification->message,
            'deep_link' => $notification->deep_link ? mb_substr($notification->deep_link, 0, 255) : null,
            'data' => json_encode([
                'notification_id' => $notification->reference,
                'category' => $notification->category,
                'image_url' => $imageUrl,
            ], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
