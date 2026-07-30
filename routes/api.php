<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User
use App\Http\Controllers\Api\User\AccessController;

// Cart
use App\Http\Controllers\Api\CartController;

// Merchant
use App\Http\Controllers\Api\Merchant\OrderController as MerchantOrderController;
use App\Http\Controllers\Api\Merchant\ItemController as MerchantItemController;
use App\Http\Controllers\Api\Merchant\CategoryController as MerchantCategoryController;
use App\Http\Controllers\Api\Merchant\LocationController as MerchantLocationController;
use App\Http\Controllers\Api\Merchant\VoucherController as MerchantVoucherController;
use App\Http\Controllers\Api\Merchant\ProfileController as MerchantProfileController;
use App\Http\Controllers\Api\Merchant\VariantController as MerchantVariantController;

// Admin
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\Admin\MerchantController as AdminMerchantController;

// Booking
use App\Http\Controllers\Api\Booking\RequestBooking;

// Page
use App\Http\Controllers\Api\Restaurant\PageController as RestaurantPageController;
use App\Http\Controllers\Api\Flowerstore\PageController as FlowerstorePageController;

// Map
use App\Http\Controllers\Map\DistanceController;

// Register / Partner
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\RiderApplicationController;
use App\Http\Controllers\Api\V1\Rider\AuthController as V1RiderAuthController;
use App\Http\Controllers\Api\V1\Rider\CommunicationController as V1RiderCommunicationController;
use App\Http\Controllers\Api\V1\Rider\DeliveryController as V1RiderDeliveryController;
use App\Http\Controllers\Api\V1\Rider\OperationsController as V1RiderOperationsController;
use App\Http\Controllers\Api\V1\Rider\ProfileController as V1RiderProfileController;
use App\Http\Controllers\Api\V1\Rider\WalletController as V1RiderWalletController;

// Mobile
use App\Http\Controllers\Api\Mobile\HomeController as MobileHomeController;
use App\Http\Controllers\Api\Mobile\CartController as MobileCartController;
use App\Http\Controllers\Api\Mobile\CheckoutController as MobileCheckoutController;
use App\Http\Controllers\Api\Mobile\OrderController as MobileOrderController;
use App\Http\Controllers\Api\Mobile\Rider\OrderController as RiderOrderController;
use App\Http\Controllers\Api\Mobile\Store\OrderController as StoreOrderController;
use App\Http\Controllers\Api\Mobile\Booking\BookingController as MobileBookingController;
use App\Http\Controllers\Api\Mobile\Booking\AddressesController as MobileBookingAddressesController;
use App\Http\Controllers\Api\Mobile\UserController as MobileUserController;

use App\Http\Controllers\Api\Mobile\ResourcesController;


// -------------------------------------------------------------
// PUBLIC ROUTES
// -------------------------------------------------------------



// Item
Route::middleware(['api', 'web'])->group(function () {

    Route::post('account/login', [AccessController::class, 'login']);
    Route::post('account/register', [AccessController::class, 'register']);
    Route::post('login/submit', [AccessController::class, 'login']);

    Route::post('location/submit', [CartController::class, 'updateLocationCoordinates']);
    Route::get('/getschedule', [RequestBooking::class, 'getAvailableSchedule']);

    // Cart
    Route::get('cart/summary', [CartController::class, 'index']);
    Route::post('cart/{cartItem}/action/{status?}/submit', [CartController::class, 'modifyCartItem']);

    Route::post('item/{action}/add-cart', [CartController::class, 'addCart']);
    Route::post('item/add-cart', [CartController::class, 'addCart']);

});

Route::middleware(['web'])->group(function () {
    Route::post('cart/validate-session', [CartController::class, 'validateSession']);
});

// Route::get('home-categories', [ResourcesController::class, 'getCategories']);
// Route::get('home-cuisines', [ResourcesController::class, 'getCuisines']);
// Route::get('home-promo-banner', [ResourcesController::class, 'getPromoBanner']);
// Route::get('home-top-picks', [ResourcesController::class, 'getTopPicks']);
// Route::get('near/restaurants', [RestaurantPageController::class, 'list']);


// Restaurants
Route::get('restaurants', [RestaurantPageController::class, 'list']);
Route::get('flowerstore', [FlowerstorePageController::class, 'list']);

// Partner & Register
Route::post('partner/submit', [PartnerController::class, 'store']);
Route::post('register/submit', [RegisterController::class, 'store']);

// Merchant Registration
Route::post('merchant/register/submit', [PartnerController::class, 'insertMerchantPartner']);
Route::post('merchant/forgot/submit', [PartnerController::class, 'resetPassword']);

// -------------------------------------------------------------
// AUTHENTICATED API ROUTES
// -------------------------------------------------------------

Route::group(['middleware' => ['api', 'web']], function () {

    Route::post('checkout', [CartController::class, 'checkout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Checkout
    Route::get('checkout/address', [CartController::class, 'useraddress']);
    Route::post('checkout/profile/update/submit', [\App\Http\Controllers\Api\User\Cart\CheckoutController::class, 'updateProfile']);
    Route::post('checkout/address/delete/submit', [\App\Http\Controllers\Api\User\Cart\CheckoutController::class, 'deleteAddress']);
    Route::post('checkout/address/update/{userAddress}/submit', [\App\Http\Controllers\Api\User\Cart\CheckoutController::class, 'updateAddress']);
    Route::post('checkout/address/update/submit', [\App\Http\Controllers\Api\User\Cart\CheckoutController::class, 'updateAddressSelected']);
    Route::post('checkout/address/add/submit', [\App\Http\Controllers\Api\User\Cart\CheckoutController::class, 'addAddress']);

    Route::post('checkout/payment/update/submit', [CartController::class, 'updatePaymentGateway']);
    Route::post('checkout/submit', [CartController::class, 'process']);
    Route::post('checkout/sms/submit', [CartController::class, 'smsSending']);
    Route::post('checkout/coupon/submit', [\App\Http\Controllers\Api\User\Cart\CheckoutController::class, 'couponCode']);

    // Distance
    Route::post('/distance', [DistanceController::class, 'getKilometerByCoodinates']);
    Route::post('/getdistance', [DistanceController::class, 'getByLocation']);
    Route::post('/getCoordinateByPlaceId', [DistanceController::class, 'getByPlaceId']);

    // Merchant API
    Route::get('merchant/order/list', [MerchantOrderController::class, 'getList']);
    Route::post('merchant/order/search/list', [MerchantOrderController::class, 'getListwithFilter']);
    Route::get('/merchant/dashboard/order/summary', [MerchantOrderController::class, 'orderSummary']);
    Route::post('merchant/update/store/online/submit', [PartnerController::class, 'storeOnlineUpdate']);
    Route::post('merchant/variant/{productItemHeader}/submit', [MerchantItemController::class, 'updateVariantStatus']);

    // Admin API
    Route::get('dashboard/order/list', [AdminOrderController::class, 'getList']);
    Route::get('dashboard/booking/list', [AdminBookingController::class, 'getList']);

    Route::post('data/dashboard/update/{order}/rider/submit', [AdminOrderController::class, 'updateOrderRider']);
    Route::post('data/dashboard/update/{order}/status/submit', [AdminOrderController::class, 'updateOrderStatus']);

    Route::post('data/dashboard/booking/update/{booking}/rider/submit', [AdminBookingController::class, 'updateOrderRider']);
    Route::post('data/dashboard/booking/update/{booking}/status/submit', [AdminBookingController::class, 'updateOrderStatus']);

    Route::get('/data/dashboard/order/summary', [AdminOrderController::class, 'orderSummary']);
    Route::post('data/order/search/list', [AdminOrderController::class, 'getListwithFilter']);
    Route::get('data/merchant/search/list', [AdminOrderController::class, 'getListMerchantwithFilter']);
    Route::get('data/member/search/list', [AdminOrderController::class, 'getListMemberwithFilter']);

    Route::post('data/merchant/{partner:id}/online/submit', [AdminMerchantController::class, 'updateOnlineStatus']);
    Route::post('data/merchant/{partner:id}/status/submit', [AdminMerchantController::class, 'updateStatus']);
    Route::post('data/merchant/{partner:id}/verify/submit', [AdminMerchantController::class, 'verify']);
    Route::post('data/merchant/{partner:id}/preorder/submit', [AdminMerchantController::class, 'preorder']);
    Route::post('data/merchant/{partner:id}/coomrate/submit', [AdminMerchantController::class, 'coomrate']);

    Route::post('data/merchant/{partner:id}/password/submit', [AdminMerchantController::class, 'passwordSend']);
    Route::get('data/dashboard/booking/new', [AdminBookingController::class, 'index']);
    Route::post('data/dashboard/address/submit', [AdminBookingController::class, 'addressStore']);
    Route::post('data/dashboard/boooking/submit', [AdminBookingController::class, 'storeNewBooking']);
});

// ----------------------------------------------------------------------
// MERCHANT OPEN APIs (No Auth)
// ----------------------------------------------------------------------

Route::prefix('merchant')->middleware(['api', 'web'])->group(function () {
        
    Route::post('product/submit', [MerchantItemController::class, 'store']);
    Route::get('product/requirement', [MerchantItemController::class, 'requirementList']);
    Route::get('product/list', [MerchantItemController::class, 'fetchData']);
    Route::post('product/category/list', [MerchantItemController::class, 'fetchDataByCategory']);
    Route::put('product/{product}/submit', [MerchantItemController::class, 'update']);
    Route::delete('product/{product}/delete', [MerchantItemController::class, 'destroy']);
    Route::post('product/{product}/upload/submit', [MerchantItemController::class, 'uploadImage']);
    Route::put('product/{product}/status/submit', [MerchantItemController::class, 'status']);

    // Merchant Category
    Route::get('category/list', [MerchantCategoryController::class, 'getList']);
    Route::put('category/{category}/update/status', [MerchantCategoryController::class, 'updateStatus']);
    Route::post('category/submit', [MerchantCategoryController::class, 'store']);
    Route::put('category/{category}/submit', [MerchantCategoryController::class, 'update']);
    Route::delete('category/{category}/delete', [MerchantCategoryController::class, 'destroy']);

    // Merchant Location
    Route::get('location/list', [MerchantLocationController::class, 'getList']);
    Route::put('location/{location}/update/status', [MerchantLocationController::class, 'updateStatus']);
    Route::post('location/submit', [MerchantLocationController::class, 'store']);
    Route::put('location/{location}/submit', [MerchantLocationController::class, 'update']);
    Route::delete('location/{location}/delete', [MerchantLocationController::class, 'destroy']);

    // Merchant Voucher
    Route::get('voucher/list', [MerchantVoucherController::class, 'getList']);
    Route::put('voucher/{voucher}/update/status', [MerchantVoucherController::class, 'updateStatus']);
    Route::post('voucher/submit', [MerchantVoucherController::class, 'store']);
    Route::put('voucher/{voucher}/submit', [MerchantVoucherController::class, 'update']);
    Route::delete('voucher/{voucher}/delete', [MerchantVoucherController::class, 'destroy']);

    // Merchant Profile
    Route::get('profile', [MerchantProfileController::class, 'getList']);
    Route::post('profile/upload/submit', [MerchantProfileController::class, 'uploadImage']);
    Route::post('banner/upload/submit', [MerchantProfileController::class, 'uploadImageBanner']);
    Route::put('profile/submit', [MerchantProfileController::class, 'update']);
    Route::put('sector/{sector}/update/status', [MerchantProfileController::class, 'updateSector']);

    // Merchant Variants
    Route::post('product/variant/submit', [MerchantVariantController::class, 'store']);
    Route::get('product/{product}/variant/list', [MerchantVariantController::class, 'getList']);
    Route::delete('product/variant/{productItemHeader}/delete', [MerchantVariantController::class, 'destroy']);
    Route::get('product/variant/{productItemHeader}/details', [MerchantVariantController::class, 'getVariantDetails']);
    Route::delete('product/variant/detail/{productItemDetails}/delete', [MerchantVariantController::class, 'deleteDetails']);
    Route::post('product/variant/{productItemHeader}/detail/submit', [MerchantVariantController::class, 'storeDetails']);
    Route::put('product/variant/{productItemHeader}/detail/submit', [MerchantVariantController::class, 'updateDetails']);
});

// ----------------------------------------------------------------------
// MOBILE API (User App)
// ----------------------------------------------------------------------

Route::group(['middleware' => 'isRequest'], function () {
    Route::group(['prefix' => 'mobile'], function () {

        Route::get('home', [MobileHomeController::class, 'home']);
        Route::get('restaurants', [MobileHomeController::class, 'list']);

        Route::get('data/dashboard', [ResourcesController::class, 'getDashboardData']);
        
        Route::get('restaurant/search', [MobileHomeController::class, 'search']);
        Route::get('restaurant/{partner:id}', [MobileHomeController::class, 'restaurant']);

        Route::post('account/login', [AccessController::class, 'login']);
        Route::post('account/register', [AccessController::class, 'register']);
        Route::post('login/submit', [AccessController::class, 'login']);

        Route::post('cart/{cartItem}/action/{status?}/submit', [MobileCartController::class, 'modifyCartItem']);

        Route::group(['middleware' => ['auth:api']], function () {
            //

            Route::post('user/coordinates', [ResourcesController::class, 'updateUserCoordinates']);

            Route::post('item/add-cart', [MobileCartController::class, 'addCart']);
            Route::post('checkout/submit', [MobileCheckoutController::class, 'process']);
            Route::post('item/{status?}/add-cart', [MobileCartController::class, 'addCart']);

            Route::post('shopping-cart', [MobileCartController::class, 'getCart']);
            Route::get('checkout', [MobileCheckoutController::class, 'checkout']);
            
            Route::post('checkout/sms/submit', [MobileCheckoutController::class, 'smsSending']);
            Route::post('checkout/sms/confirm/submit', [MobileCheckoutController::class, 'validatedSMScode']);
            Route::post('checkout/update/user/submit', [MobileCheckoutController::class, 'updateUserProfile']);
            Route::post('user/reset/session', [MobileCheckoutController::class, 'resetSession']);

            Route::post('order/{order}', [MobileOrderController::class, 'index']);
            Route::get('getorders', [MobileOrderController::class, 'orders']);
            Route::post('getorder/{order}', [MobileOrderController::class, 'getOrderById']);

            Route::post('address/add/submit', [MobileCheckoutController::class, 'addAddress']);
            Route::get('address/list', [MobileUserController::class, 'getAddresses']);
            Route::post('address/delete/submit', [MobileUserController::class, 'deleteAddress']);
            Route::post('address/submit', [MobileUserController::class, 'addAddress']);

            Route::post('/byplaceId', [DistanceController::class, 'getByPlaceId']);
            Route::post('data/dashboard/update/{order}/status/submit', [AdminOrderController::class, 'updateOrderStatus']);
            Route::post('cancel/order/{order}/submit', [MobileOrderController::class, 'updateOrderStatus']);
            Route::post('token/submit', [RiderOrderController::class, 'saveTokenDeviceFood']);

            // Booking
            Route::post('bookings', [MobileBookingController::class, 'index']);
            Route::post('booking/addresses', [MobileBookingController::class, 'getUserAddresses']);
            Route::post('booking/submit', [MobileBookingController::class, 'store']);
            Route::post('booking/address/submit', [MobileBookingAddressesController::class, 'insertNewAddress']);
            Route::post('booking/address/update/submit', [MobileBookingAddressesController::class, 'updateAddress']);
            Route::post('/booking/check', [MobileBookingController::class, 'getKilometerByCoodinates']);
            Route::post('cancel/booking/{booking}/submit', [MobileBookingController::class, 'cancelBooking']);
            Route::post('getbooking/{booking}', [MobileBookingController::class, 'getBookingById']);

            Route::post('booking/address/delete/submit', [MobileBookingController::class, 'deleteBookingAddress']);
        });

        Route::post('account/registration/mobile/submit', [AccessController::class, 'registerMobile2']);
        Route::post('restaurants/sector/{sector}', [MobileHomeController::class, 'listSector']);
        Route::post('restaurants/highlights/{highlights}', [MobileHomeController::class, 'list']);
        Route::post('checkout/address/cart/update/submit', [MobileCheckoutController::class, 'updateAddress']);
    });
});


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
            Route::get('devices', [V1RiderAuthController::class, 'devices']);
            Route::post('devices', [V1RiderAuthController::class, 'registerDevice']);
            Route::patch('devices/{device}', [V1RiderAuthController::class, 'updateDevice']);
            Route::delete('devices/{device}', [V1RiderAuthController::class, 'revokeDevice']);

            Route::get('dashboard', [V1RiderOperationsController::class, 'dashboard']);
            Route::get('availability', [V1RiderOperationsController::class, 'availability']);
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
            Route::get('deliveries/active', [V1RiderDeliveryController::class, 'active']);
            Route::get('deliveries/{delivery}', [V1RiderDeliveryController::class, 'show']);
            Route::get('deliveries/{delivery}/route', [V1RiderDeliveryController::class, 'route']);
            Route::post('deliveries/{delivery}/events', [V1RiderDeliveryController::class, 'event']);
            Route::post('deliveries/{delivery}/pickup/verify', [V1RiderDeliveryController::class, 'verifyPickup']);
            Route::post('deliveries/{delivery}/customer/verify', [V1RiderDeliveryController::class, 'verifyCustomer']);
            Route::post('deliveries/{delivery}/cod/confirm', [V1RiderDeliveryController::class, 'confirmCod']);
            Route::post('deliveries/{delivery}/proof/uploads', [V1RiderDeliveryController::class, 'requestProofUpload']);
            Route::post('deliveries/{delivery}/proof', [V1RiderDeliveryController::class, 'attachProof']);
            Route::get('deliveries/{delivery}/proof/status', [V1RiderDeliveryController::class, 'proofStatus']);
            Route::post('deliveries/{delivery}/issues', [V1RiderDeliveryController::class, 'issue']);
            Route::post('deliveries/{delivery}/calls', [V1RiderDeliveryController::class, 'call']);
            Route::post('deliveries/{delivery}/share-trip', [V1RiderDeliveryController::class, 'shareTrip']);
            Route::get('orders', [V1RiderDeliveryController::class, 'orders']);
            Route::get('orders/{order}', [V1RiderDeliveryController::class, 'order']);

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

            Route::post('token/save', [RiderOrderController::class, 'saveToken'])->name('rider.save-token');
            Route::post('bookings', [RiderOrderController::class, 'bookings']);
            Route::post('accepted/bookings', [RiderOrderController::class, 'getAcceptedBooking']);
            Route::post('accepted/day/bookings', [RiderOrderController::class, 'getAcceptedBookingByDate']);
            Route::post('bookings/{order}/{action}/submit', [RiderOrderController::class, 'acceptBooking']);

            Route::post('bookings/job/{booking}/{action}/submit', [RiderOrderController::class, 'acceptJobBooking']);
            Route::post('token/submit', [RiderOrderController::class, 'saveTokenDevice']);
        });
    });
});


// ----------------------------------------------------------------------
// STORE DASHBOARD MOBILE API
// ----------------------------------------------------------------------

Route::group(['middleware' => 'isRequest'], function () {
    Route::group(['prefix' => 'vjhgf'], function () {

        Route::post('account/login', [AccessController::class, 'loginStore']);
        Route::post('login/submit', [AccessController::class, 'loginStore']);

        Route::group(['middleware' => 'auth:api'], function () {

            Route::post('token/save', [StoreOrderController::class, 'saveToken'])->name('merchant.save-token');
            Route::post('bookings', [StoreOrderController::class, 'bookings']);
            Route::post('accepted/bookings', [StoreOrderController::class, 'getAcceptedBooking']);
            Route::post('accepted/day/bookings', [StoreOrderController::class, 'getAcceptedBookingByDate']);
            Route::post('bookings/{order}/{action}/submit', [StoreOrderController::class, 'acceptBooking']);

            Route::post('token/submit', [StoreOrderController::class, 'saveTokenDeviceStore']);
        });
    });
});
