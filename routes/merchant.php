<?php

use App\Http\Controllers\Api\Merchant\CategoryController as MerchantCategoryController;
use App\Http\Controllers\Api\Merchant\ItemController as MerchantItemController;
use App\Http\Controllers\Api\Merchant\LocationController as MerchantLocationController;
use App\Http\Controllers\Api\Merchant\OrderController as MerchantOrderController;
use App\Http\Controllers\Api\Merchant\ProfileController as MerchantProfileController;
use App\Http\Controllers\Api\Merchant\VariantController as MerchantVariantController;
use App\Http\Controllers\Api\Merchant\VoucherController as MerchantVoucherController;
use App\Http\Controllers\Api\Mobile\Store\OrderController as StoreOrderController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\User\AccessController;
use Illuminate\Support\Facades\Route;

// Registration and password recovery
Route::post('merchant/register/submit', [PartnerController::class, 'insertMerchantPartner']);
Route::post('merchant/forgot/submit', [PartnerController::class, 'resetPassword']);

// Merchant dashboard API
Route::middleware(['api', 'web'])->group(function () {
    Route::get('merchant/order/list', [MerchantOrderController::class, 'getList']);
    Route::post('merchant/order/search/list', [MerchantOrderController::class, 'getListwithFilter']);
    Route::get('/merchant/dashboard/order/summary', [MerchantOrderController::class, 'orderSummary']);
    Route::post('merchant/update/store/online/submit', [PartnerController::class, 'storeOnlineUpdate']);
    Route::post('merchant/variant/{productItemHeader}/submit', [MerchantItemController::class, 'updateVariantStatus']);
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
            Route::post('orders/{order}/cancel', [StoreOrderController::class, 'cancelOrder'])->name('merchant.orders.cancel');
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
