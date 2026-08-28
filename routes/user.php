<?php

use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Mobile\Booking\AddressesController as MobileBookingAddressesController;
use App\Http\Controllers\Api\Mobile\Booking\BookingController as MobileBookingController;
use App\Http\Controllers\Api\Mobile\CartController as MobileCartController;
use App\Http\Controllers\Api\Mobile\CheckoutController as MobileCheckoutController;
use App\Http\Controllers\Api\Mobile\HomeController as MobileHomeController;
use App\Http\Controllers\Api\Mobile\OrderController as MobileOrderController;
use App\Http\Controllers\Api\Mobile\ResourcesController;
use App\Http\Controllers\Api\Mobile\Rider\OrderController as RiderOrderController;
use App\Http\Controllers\Api\Mobile\UserController as MobileUserController;
use App\Http\Controllers\Api\User\AccessController;
use App\Http\Controllers\Map\DistanceController;
use Illuminate\Support\Facades\Route;

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
        Route::post('account/google', [AccessController::class, 'google']);
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
