<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User
use App\Http\Controllers\Api\User\AccessController;

// Cart
use App\Http\Controllers\Api\CartController;

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

// Domain-specific API routes
require __DIR__.'/merchant.php';
require __DIR__.'/user.php';
require __DIR__.'/rider.php';
