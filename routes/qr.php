<?php

use App\Http\Controllers\Api\Merchant\QrOrderingCodeController as MerchantQrOrderingCodeController;
use App\Http\Controllers\Api\Qr\QrOrderingController;
use Illuminate\Support\Facades\Route;

// Guest QR ordering. The UUID is an opaque QR credential; the session UUID identifies one cart.
Route::prefix('qr/{uuid}/branches/{branchId}')
    ->whereUuid('uuid')
    ->whereNumber('branchId')
    ->middleware('throttle:120,1')
    ->group(function () {
        Route::get('menu', [QrOrderingController::class, 'menu'])->name('api.qr.menu');
        Route::post('sessions', [QrOrderingController::class, 'createSession'])
            ->middleware('throttle:qr-sessions');

        Route::prefix('carts/{sessionId}')
            ->whereUuid('sessionId')
            ->middleware('qr.session')
            ->group(function () {
                Route::get('summary', [QrOrderingController::class, 'summary']);
                Route::get('orders', [QrOrderingController::class, 'orders']);
                Route::post('items', [QrOrderingController::class, 'addItem']);
                Route::patch('items/{cartItem}', [QrOrderingController::class, 'updateItem'])
                    ->whereNumber('cartItem');
                Route::post('checkout/quickpay/status', [QrOrderingController::class, 'quickPayStatus'])
                    ->middleware('throttle:qr-payment-status');
                Route::post('checkout', [QrOrderingController::class, 'checkout'])
                    ->middleware('throttle:qr-checkout');
            });
    });

// Existing merchant authentication is enforced inside the QR provisioning controller.
Route::prefix('merchant/location/{partnerLocation}/qr-ordering-codes')
    ->middleware(['api', 'web'])
    ->group(function () {
        Route::get('/', [MerchantQrOrderingCodeController::class, 'index']);
        Route::post('/', [MerchantQrOrderingCodeController::class, 'store']);
        Route::patch('{qrOrderingCode}', [MerchantQrOrderingCodeController::class, 'update']);
    });
