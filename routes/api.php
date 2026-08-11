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

Route::post('account/logout', [AccessController::class, 'postLogout'])
    ->middleware('auth:api');

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

        Route::group(['middleware' => ['auth:api']], function () {
            //
            Route::post('account/logout', [AccessController::class, 'postLogout']);
            Route::post('user/coordinates', [ResourcesController::class, 'updateUserCoordinates']);

            Route::post('item/add-cart', [MobileCartController::class, 'addCart']);
            Route::post('item/{status?}/add-cart', [MobileCartController::class, 'addCart']);
            Route::post('cart/{cartItem}/action/{status?}/submit', [MobileCartController::class, 'modifyCartItem']);

            Route::post('checkout/submit', [MobileCheckoutController::class, 'process']);
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
    Route::group(['prefix' => 'rider'], function () {

        // (basedURL)api/rider/account/login

        Route::post('account/login', [AccessController::class, 'login']);
        Route::post('login/submit', [AccessController::class, 'login']);

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


// ----------------------------------------------------------------------
// STORE/Merchant DASHBOARD MOBILE API
// ----------------------------------------------------------------------

Route::group(['middleware' => 'isRequest'], function () {
    Route::group(['prefix' => 'merchant'], function () {

        Route::post('account/login', [AccessController::class, 'loginStore']);
        Route::post('login/submit', [AccessController::class, 'loginStore']);

        Route::group(['middleware' => 'auth:api'], function () {

            Route::post('account/logout', [AccessController::class, 'postLogout']);
            // Route::post('token/save', [StoreOrderController::class, 'saveToken'])->name('merchant.save-token');
            Route::post('location/{partnerLocation}/device-token', [StoreOrderController::class, 'updateLocationDeviceToken'])->name('merchant.location.update-device-token');

            Route::get('orders', [StoreOrderController::class, 'orders']);
            Route::post('orders/{order}/accept', [StoreOrderController::class, 'acceptOrder'])->name('merchant.orders.accept');
            Route::post('orders/{order}/ready-for-pickup', [StoreOrderController::class, 'markOrderReadyForPickup'])->name('merchant.orders.ready-for-pickup');
            // Route::get('bookings', [StoreOrderController::class, 'bookings']);
            Route::get('accepted/bookings', [StoreOrderController::class, 'getAcceptedBooking']);
            // Route::get('accepted/day/bookings', [StoreOrderController::class, 'getAcceptedBookingByDate']);
            // Route::post('bookings/{order}/{action}/submit', [StoreOrderController::class, 'acceptBooking']);

            Route::post('token/submit', [StoreOrderController::class, 'saveTokenDeviceStore']);
            Route::post('toggle/store/online', [StoreOrderController::class, 'toggleStoreOnline']);

            // we need to have the products and categories locate per merchant login
            Route::get('products', [StoreOrderController::class, 'products']);
            Route::post('products/status/update', [StoreOrderController::class, 'updateProductStatus']);
            // we need to an update to the product and category to be able to update the status of the product and category
            // 
        });
    });
});
