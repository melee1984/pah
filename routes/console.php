<?php

use App\AdminPushNotification;
use App\Jobs\SendAdminPushNotification;
use App\RestaurantEnrollmentDocument;
use App\Services\RestaurantApplicationNotifier;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('restaurants:expire-documents', function () {
    RestaurantEnrollmentDocument::query()
        ->whereIn('document_type', array_keys(RestaurantEnrollmentDocument::LABELS))
        ->whereDate('expires_at', '<', today())
        ->whereNotIn('status', ['expired', 'rejected'])
        ->with('restaurant')
        ->chunkById(100, function ($documents) {
            foreach ($documents as $document) {
                $document->update(['status' => 'expired']);
                $restaurant = $document->restaurant;
                if ($restaurant && $restaurant->application_status === 'approved') {
                    $restaurant->forceFill(['application_status' => 'pending_review', 'active' => false, 'verified_at' => null])->save();
                }
                if ($restaurant) {
                    app(RestaurantApplicationNotifier::class)->send($restaurant,
                        RestaurantEnrollmentDocument::label($document->document_type).' has expired. Please upload a current replacement.');
                }
            }
        });
})->purpose('Mark expired restaurant documents and notify applicants');

Schedule::command('restaurants:expire-documents')->daily();

Schedule::call(function () {
    AdminPushNotification::query()
        ->where('status', AdminPushNotification::STATUS_SCHEDULED)
        ->where('scheduled_at', '<=', now())
        ->orderBy('id')
        ->chunkById(100, function ($notifications) {
            foreach ($notifications as $notification) {
                $claimed = AdminPushNotification::query()
                    ->whereKey($notification->id)
                    ->where('status', AdminPushNotification::STATUS_SCHEDULED)
                    ->update([
                        'status' => AdminPushNotification::STATUS_QUEUED,
                        'updated_at' => now(),
                    ]);

                if ($claimed === 1) {
                    SendAdminPushNotification::dispatch($notification->id);
                }
            }
        });
})->name('push-notifications:dispatch-due')->everyMinute()->withoutOverlapping();
