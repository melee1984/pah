<?php

use App\Http\Controllers\Api\Mobile\Rider\OrderController as RiderOrderController;
use App\Http\Controllers\Api\RiderApplicationController;
use App\Http\Controllers\Api\User\AccessController;
use App\Http\Controllers\Api\V1\Rider\AuthController as V1RiderAuthController;
use App\Http\Controllers\Api\V1\Rider\CommunicationController as V1RiderCommunicationController;
use App\Http\Controllers\Api\V1\Rider\DeliveryController as V1RiderDeliveryController;
use App\Http\Controllers\Api\V1\Rider\OperationsController as V1RiderOperationsController;
use App\Http\Controllers\Api\V1\Rider\ProfileController as V1RiderProfileController;
use App\Http\Controllers\Api\V1\Rider\RiderController as V1RiderController;
use App\Http\Controllers\Api\V1\Rider\WalletController as V1RiderWalletController;
use Illuminate\Support\Facades\Route;

// ----------------------------------------------------------------------
// RIDER APP API
// ----------------------------------------------------------------------

Route::group(['middleware' => 'isRequest'], function () {
    Route::prefix('v1/rider')->group(function () {

        Route::post('auth/login', [V1RiderAuthController::class, 'login']);
        Route::post('auth/otp/send', [V1RiderAuthController::class, 'sendOtp']);
        Route::post('auth/otp/verify', [V1RiderAuthController::class, 'verifyOtp']);
        Route::post('auth/password/forgot', [V1RiderAuthController::class, 'forgotPassword']);
        Route::post('auth/password/reset', [V1RiderAuthController::class, 'resetPassword']);

        Route::post('applications', [RiderApplicationController::class, 'create'])
            ->name('v1.rider.applications.create');

        Route::middleware('rider.application')->group(function () {

            Route::get('applications/current', [RiderApplicationController::class, 'current']);
            Route::get('applications/{application}', [RiderApplicationController::class, 'show']);
            Route::patch('applications/{application}/personal', [RiderApplicationController::class, 'updatePersonal']);
            Route::patch('applications/{application}/emergency-contact', [RiderApplicationController::class, 'updateEmergencyContact']);
            Route::patch('applications/{application}/vehicle', [RiderApplicationController::class, 'updateVehicle']);
            Route::patch('applications/{application}/payout-account', [RiderApplicationController::class, 'updatePayoutAccount']);
            Route::post('applications/{application}/documents', [RiderApplicationController::class, 'uploadDocument']);
            Route::delete('applications/{application}/documents/{document}', [RiderApplicationController::class, 'deleteDocument']);
            Route::post('applications/{application}/submit', [RiderApplicationController::class, 'submit']);
            Route::post('applications/{application}/resubmit', [RiderApplicationController::class, 'resubmit']);
            Route::get('applications/{application}/status', [RiderApplicationController::class, 'status']);
            Route::post('applications/{application}/activation/send', [RiderApplicationController::class, 'sendActivation']);
            Route::post('applications/{application}/activation/confirm', [RiderApplicationController::class, 'confirmActivation']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('auth/refresh', [V1RiderAuthController::class, 'refresh']);
            Route::post('auth/logout', [V1RiderAuthController::class, 'logout']);
            Route::post('auth/logout-all', [V1RiderAuthController::class, 'logoutAll']);
            Route::get('me', [V1RiderAuthController::class, 'me']);
        });

        Route::middleware(['auth:sanctum', 'rider.approved'])->group(function () {


            // This should display the information about the rider's dashboard, including earnings, deliveries, and other relevant metrics.
            Route::get('dashboard', [V1RiderOperationsController::class, 'dashboard']);
            // this should display the list of new bookings that are available for the rider to accept or decline.
            Route::get('new/bookings', [V1RiderOperationsController::class, 'newBookings']);
            Route::get('bookings', [V1RiderOperationsController::class, 'bookings']);
            // Route::get('orders', [V1RiderDeliveryController::class, 'orders']);
            // Route::get('bookings-orders', [V1RiderDeliveryController::class, 'orders']);

            Route::get('wallet/balance', [V1RiderController::class, 'walletBalance']);

            Route::get('devices', [V1RiderAuthController::class, 'devices']);
            Route::post('devices', [V1RiderAuthController::class, 'registerDevice']);
            Route::patch('devices/{device}', [V1RiderAuthController::class, 'updateDevice']);
            Route::delete('devices/{device}', [V1RiderAuthController::class, 'revokeDevice']);
            Route::get('status', [V1RiderController::class, 'status']);
            Route::get('availability', [V1RiderOperationsController::class, 'availability']);
            Route::get('activity-logs', [V1RiderController::class, 'activityLogs']);
            Route::post('orders/{order}/accept', [V1RiderDeliveryController::class, 'acceptBooking']);
            Route::post('orders/{order}/decline', [V1RiderDeliveryController::class, 'declineBooking']);
            Route::post('orders/{order}/action', [V1RiderDeliveryController::class, 'acceptAction']);

            // Route::get('orders/{order}', [V1RiderDeliveryController::class, 'order']);
            Route::get('overview/today', [V1RiderController::class, 'todayOverview']);
            
            Route::put('status', [V1RiderController::class, 'updateStatus']);
            Route::post('activity-logs', [V1RiderController::class, 'recordActivity']);
            
            Route::put('availability', [V1RiderOperationsController::class, 'updateAvailability']);
            Route::post('availability/heartbeat', [V1RiderOperationsController::class, 'heartbeat']);
            Route::get('availability/schedule', [V1RiderOperationsController::class, 'schedule']);
            Route::put('availability/schedule', [V1RiderOperationsController::class, 'updateSchedule']);
            Route::get('zones', [V1RiderOperationsController::class, 'zones']);
            Route::put('zones/preferences', [V1RiderOperationsController::class, 'updateZonePreferences']);
            Route::get('alerts', [V1RiderOperationsController::class, 'alerts']);
            Route::post('location', [V1RiderOperationsController::class, 'saveLocation']);
            Route::post('location/batch', [V1RiderOperationsController::class, 'saveLocationBatch']);
            Route::get('location/config', [V1RiderOperationsController::class, 'locationConfig']);

            Route::get('offers/current', [V1RiderDeliveryController::class, 'currentOffer']);
            Route::get('offers/{offer}', [V1RiderDeliveryController::class, 'offer']);
            Route::post('offers/{offer}/accept', [V1RiderDeliveryController::class, 'acceptOffer']);
            Route::post('offers/{offer}/decline', [V1RiderDeliveryController::class, 'declineOffer']);
            
            Route::get('deliveries', [V1RiderDeliveryController::class, 'index']);
            Route::get('deliveries/active', [V1RiderDeliveryController::class, 'active']);
            Route::get('deliveries/{delivery}', [V1RiderDeliveryController::class, 'show']);
            // This is for the rider to get the route for a specific delivery, which can be used for navigation purposes.
            Route::get('deliveries/{delivery}/route', [V1RiderDeliveryController::class, 'route']);
            // This is for the rider to update the status of a delivery, such as marking it as picked up, in transit, or delivered.
            Route::post('deliveries/{delivery}/events', [V1RiderDeliveryController::class, 'event']);
            Route::post('deliveries/{delivery}/pickup/verify', [V1RiderDeliveryController::class, 'verifyPickup']);
            Route::post('deliveries/{delivery}/customer/verify', [V1RiderDeliveryController::class, 'verifyCustomer']);
            Route::post('deliveries/{delivery}/cod/confirm', [V1RiderDeliveryController::class, 'confirmCod']);
            Route::post('deliveries/{delivery}/proof/uploads', [V1RiderDeliveryController::class, 'requestProofUpload']);
            Route::post('deliveries/{delivery}/proof', [V1RiderDeliveryController::class, 'attachProof']);
            Route::get('deliveries/{delivery}/proof/status', [V1RiderDeliveryController::class, 'proofStatus']);
            Route::get('deliveries/{delivery}/proof/{proof}/file', [V1RiderDeliveryController::class, 'viewProof']);
            Route::post('deliveries/{delivery}/issues', [V1RiderDeliveryController::class, 'issue']);
            Route::post('deliveries/{delivery}/calls', [V1RiderDeliveryController::class, 'call']);
            Route::post('deliveries/{delivery}/share-trip', [V1RiderDeliveryController::class, 'shareTrip']);
        
            Route::get('wallet', [V1RiderWalletController::class, 'wallet']);
            Route::get('wallet/earnings', [V1RiderWalletController::class, 'earnings']);
            Route::get('wallet/transactions', [V1RiderWalletController::class, 'transactions']);
            Route::get('wallet/cod', [V1RiderWalletController::class, 'cod']);
            Route::get('wallet/cod/remittance-instructions', [V1RiderWalletController::class, 'remittanceInstructions']);
            Route::post('wallet/cod/remittances', [V1RiderWalletController::class, 'submitRemittance']);
            Route::get('wallet/cod/remittances/{remittance}', [V1RiderWalletController::class, 'remittance']);
            Route::get('wallet/payouts', [V1RiderWalletController::class, 'payouts']);
            Route::get('wallet/payouts/{payout}', [V1RiderWalletController::class, 'payout']);
            Route::post('wallet/withdrawals', [V1RiderWalletController::class, 'requestWithdrawal']);
            Route::get('wallet/withdrawals/{withdrawal}', [V1RiderWalletController::class, 'withdrawal']);
            Route::get('wallet/payout-accounts', [V1RiderWalletController::class, 'payoutAccounts']);
            Route::post('wallet/payout-accounts', [V1RiderWalletController::class, 'addPayoutAccount']);
            Route::patch('wallet/payout-accounts/{account}', [V1RiderWalletController::class, 'updatePayoutAccount']);
            Route::delete('wallet/payout-accounts/{account}', [V1RiderWalletController::class, 'deletePayoutAccount']);
            Route::post('wallet/disputes', [V1RiderWalletController::class, 'dispute']);

            Route::get('conversations', [V1RiderCommunicationController::class, 'conversations']);
            Route::post('conversations', [V1RiderCommunicationController::class, 'startConversation']);
            Route::get('conversations/{conversation}', [V1RiderCommunicationController::class, 'conversation']);
            Route::get('conversations/{conversation}/messages', [V1RiderCommunicationController::class, 'messages']);
            Route::post('conversations/{conversation}/messages', [V1RiderCommunicationController::class, 'sendMessage']);
            Route::post('conversations/{conversation}/attachments', [V1RiderCommunicationController::class, 'uploadAttachment']);
            Route::post('conversations/{conversation}/read', [V1RiderCommunicationController::class, 'markConversationRead']);
            Route::get('notifications', [V1RiderCommunicationController::class, 'notifications']);
            Route::post('notifications/{notification}/read', [V1RiderCommunicationController::class, 'markNotificationRead']);
            Route::post('notifications/read-all', [V1RiderCommunicationController::class, 'markAllNotificationsRead']);
            Route::get('notification-preferences', [V1RiderCommunicationController::class, 'notificationPreferences']);
            Route::put('notification-preferences', [V1RiderCommunicationController::class, 'updateNotificationPreferences']);

            Route::get('profile', [V1RiderProfileController::class, 'profile']);
            Route::patch('profile', [V1RiderProfileController::class, 'updateProfile']);
            Route::post('profile/photo', [V1RiderProfileController::class, 'updatePhoto']);
            Route::get('profile/documents', [V1RiderProfileController::class, 'documents']);
            Route::post('profile/documents', [V1RiderProfileController::class, 'updateDocument']);
            Route::get('profile/vehicle', [V1RiderProfileController::class, 'vehicle']);
            Route::put('profile/vehicle', [V1RiderProfileController::class, 'updateVehicle']);
            Route::get('profile/performance', [V1RiderProfileController::class, 'performance']);
            Route::get('profile/feedback', [V1RiderProfileController::class, 'feedback']);
            Route::get('settings', [V1RiderProfileController::class, 'settings']);
            Route::put('settings', [V1RiderProfileController::class, 'updateSettings']);
            Route::post('account/password/change', [V1RiderProfileController::class, 'changePassword']);
            Route::post('account/delete-request', [V1RiderProfileController::class, 'requestDeletion']);
            Route::get('account/delete-request', [V1RiderProfileController::class, 'deletionStatus']);
            Route::delete('account/delete-request', [V1RiderProfileController::class, 'cancelDeletion']);
        });
    });

    Route::group(['prefix' => 'rider'], function () {

        // (basedURL)api/rider/account/login

        Route::post('account/register', [RiderApplicationController::class, 'store'])
            ->name('rider.account.register');
        Route::post('register/submit', [RiderApplicationController::class, 'store'])
            ->name('rider.application.submit');
        Route::post('account/login', [V1RiderAuthController::class, 'login']);
        Route::post('login/submit', [V1RiderAuthController::class, 'login']);

        Route::group(['middleware' => 'auth:api'], function () {

            Route::post('account/logout', [AccessController::class, 'postLogout']);
            Route::post('token/save', [RiderOrderController::class, 'saveToken'])->name('rider.save-token');
            Route::get('bookings', [RiderOrderController::class, 'bookings']);
            Route::get('accepted/bookings', [RiderOrderController::class, 'getAcceptedBooking']);
            Route::get('accepted/day/bookings', [RiderOrderController::class, 'getAcceptedBookingByDate']);
            Route::post('bookings/{order}/{action}/submit', [RiderOrderController::class, 'acceptBooking']);

            Route::post('bookings/job/{booking}/{action}/submit', [RiderOrderController::class, 'acceptJobBooking']);
            Route::post('token/submit', [RiderOrderController::class, 'saveTokenDevice']);
        });
    });
});
