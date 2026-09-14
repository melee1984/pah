<?php

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
                        RestaurantEnrollmentDocument::LABELS[$document->document_type].' has expired. Please upload a current replacement.');
                }
            }
        });
})->purpose('Mark expired restaurant documents and notify applicants');

Schedule::command('restaurants:expire-documents')->daily();
