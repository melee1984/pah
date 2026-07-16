<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Booking\RequestController;
use App\Http\Controllers\Flower\FlowerstoreController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Restaurant\PageController as RestaurantPageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// require __DIR__.'/auth.php';

// -------------------------------------------------------
// PUBLIC ROUTES
// -------------------------------------------------------

/** 
 * Demo Gere
 */

Auth::routes(['verify' => true]);
Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/location-restriction', [PageController::class, 'restriction'])->name('location.restriction')   ;
Route::get('/demo', [PageController::class, 'demoMap']);

Route::get('/imager', [\App\Http\Controllers\ImageController::class, 'image'])->name('cacheImage');
// Route::get('/image/size', [\App\Http\Controllers\ImageController::class, 'imageResize'])->name('image-resize');
Route::get('/image/size', [\App\Http\Controllers\ImageController::class, 'image'])->name('image-resize');

Route::get('/byplaceId', [\App\Http\Controllers\Map\DistanceController::class, 'getByPlaceId']);
Route::get('/distance', [\App\Http\Controllers\Map\DistanceController::class, 'getKilometerByCoodinates']);

Route::get('/sign-in/fb/complete', [\App\Http\Controllers\Api\User\AccessController::class, 'registerFB'])->name('user.registerFB');

Route::post('/search', [RestaurantPageController::class, 'search'])->name('search');
Route::get('/restaurants/{search_string}', [RestaurantPageController::class, 'searchTag'])->name('search.tag');

Route::get('/home', [PageController::class, 'index'])->name('/');
Route::get('/about-us', [PageController::class, 'aboutus'])->name('aboutus');
Route::get('/be-partner', [PageController::class, 'bepartner'])->name('bepartner');

Route::get('/contact-us', [PageController::class, 'contactus'])->name('contactus');
Route::get('/privacy-policy', [PageController::class, 'privacypolicy'])->name('privacypolicy');
Route::get('/terms-of-use', [PageController::class, 'termsofuse'])->name('termsofuse');
Route::get('/fraud-prevention', [PageController::class, 'fraudprevention'])->name('fraudprevention');
Route::get('/payment-method', [PageController::class, 'paymentmethod'])->name('paymentmethod');

// Frontend Restaurant
Route::get('/restaurant/{partner:slug}', [RestaurantPageController::class, 'view'])->name('restaurant.view');
Route::get('/restaurants', [RestaurantPageController::class, 'show'])->name('restaurant.show');

// Flowerstore
Route::get('/flowerstore/{partner:slug}', [RestaurantPageController::class, 'view'])->name('flowerstore.view');
Route::get('/flowerstore', [FlowerstoreController::class, 'show'])->name('flowerstore.show');

// Errand
Route::get('/request/booking', [RequestController::class, 'index'])->name('new.bookings');
Route::get('/request-booking/completed', [\App\Http\Controllers\Booking\RequestController::class, 'success'])->name('booking.success');

// Category & Partner
Route::get('/category/{category}', [\App\Http\Controllers\CategoryController::class, 'bepartner'])->name('category.section');
Route::get('/partner/{partner}', [\App\Http\Controllers\PartnerController::class, 'bepartner'])->name('partner.section');

Route::post('newsletter/submit', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.submit');

// User Profile
Route::get('/user/profile', [ProfileController::class, 'index'])->name('profile.home');

// SOA
Route::get('profile/soa', [\App\Http\Controllers\Merchant\ReportController::class, 'soa'])->name('profile.report.soa');

// API
Route::post('login/submit', [\App\Http\Controllers\Api\User\AccessController::class, 'loginAccess'])->name('login.submit');
Route::post('contact/submit', [PageController::class, 'storeContact'])->name('contact.submit');


// -------------------------------------------------------
// LOGGED IN USER ROUTES
// -------------------------------------------------------
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['verified'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Checkout
    Route::get('/checkout', [\App\Http\Controllers\Checkout\PageController::class, 'index'])->name('checkout.index');
    Route::get('checkout/{cart:order_no}/success', [\App\Http\Controllers\Api\CartController::class, 'success'])->name('checkout.success');

    // Dashboard & Bookings
    Route::get('/dashboard', [\App\Http\Controllers\Booking\RequestController::class, 'bookings'])->name('profile.dashboard');
    Route::get('/profile/bookings', [\App\Http\Controllers\Booking\RequestController::class, 'bookings'])->name('profile.bookings');

    // Orders
    Route::get('/profile/orders', [\App\Http\Controllers\Order\OrderHistoryController::class, 'index'])->name('profile.orders');
    Route::get('/profile/order/{cart:order_no}', [\App\Http\Controllers\Order\OrderHistoryController::class, 'view'])->name('profile.orders.view');

    Route::get('/logout', [\App\Http\Controllers\Merchant\DashboardController::class, 'showPasswordResetForm']);
});


// -------------------------------------------------------
// ADMIN PANEL
// -------------------------------------------------------

Route::get('/data/login', [DashboardController::class, 'login']);
Route::post('/data/dashboard/login/submit', [DashboardController::class, 'validateLogin'])->name('dashboard.login.submit');
Route::get('/data/dashboard/logout', [DashboardController::class, 'logout'])->name('dashboard.logout');

Route::middleware('admin')->group(function () {

    Route::get('data/dashboard', [DashboardController::class, 'index'])->name('dashboard.data');
    Route::get('data/dashboard/orders', [DashboardController::class, 'index'])->name('dashboard.orders');
    Route::get('data/dashboard/bookings', [DashboardController::class, 'index'])->name('dashboard.bookings');

    Route::get('data/dashboard/booking/add', [BookingController::class, 'index'])->name('dashboard.booking.add');

    Route::get('data/dashboard/settings', [DashboardController::class, 'index'])->name('dashboard.settings');
    Route::get('data/dashboard/riders', [DashboardController::class, 'index'])->name('dashboard.rider');
    Route::get('data/dashboard/users', [DashboardController::class, 'memberlist'])->name('dashboard.user');
    Route::get('data/dashboard/merchant', [DashboardController::class, 'merchantlist'])->name('dashboard.merchant');

    Route::get('data/dashboard/report/orders', [DashboardController::class, 'reportOrder'])->name('dashboard.report.orders');
    Route::get('data/dashboard/report/bookings', [DashboardController::class, 'index'])->name('dashboard.report.bookings');
    Route::get('data/dashboard/report/riders', [DashboardController::class, 'index'])->name('dashboard.report.riders');

    // Auto-login as merchant
    Route::get('merchant/aulogin/{id}', function ($loginId, \Illuminate\Http\Request $request) {

        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user = \App\Models\User::find($loginId);
        \Illuminate\Support\Facades\Auth::login($user, true);

        return redirect()->route('merchant.dashboard.index');
    });
});

// -------------------------------------------------------
// MERCHANT DASHBOARD
// -------------------------------------------------------

Route::get('/login/{social}', [\App\Http\Controllers\SocialLoginController::class, 'getSocialRedirect']);
Route::get('/login/{social}/callback', [\App\Http\Controllers\SocialLoginController::class, 'getSocialCallback']);

Route::get('/merchant/dashboard/login', [\App\Http\Controllers\Merchant\DashboardController::class, 'login'])->name('merchant.dashboard.login');
Route::get('/merchant/login', [\App\Http\Controllers\Merchant\DashboardController::class, 'login'])->name('merchant.login');
Route::get('/merchant/register', [\App\Http\Controllers\Merchant\DashboardController::class, 'register'])->name('merchant.register');

Route::get('/merchant/forgot', [\App\Http\Controllers\Merchant\DashboardController::class, 'forgot'])->name('merchant.forgot');
Route::get('/merchant/reset', [\App\Http\Controllers\Merchant\DashboardController::class, 'forgot'])->name('merchant.reset');
Route::get('/merchant/logout', [\App\Http\Controllers\Merchant\DashboardController::class, 'logout'])->name('merchant.logout');
Route::get('/merchant/setpassword', [\App\Http\Controllers\Merchant\DashboardController::class, 'setPassword'])->name('merchant.setPassword');

Route::post('/merchant/dashboard/login/submit', [\App\Http\Controllers\Merchant\DashboardController::class, 'validateLogin'])->name('merchant.login.submit');
Route::post('/merchant/register/submit', [\App\Http\Controllers\Merchant\DashboardController::class, 'storeMerchant'])->name('merchant.register.submit');
Route::post('/merchant/reset/submit', [\App\Http\Controllers\Merchant\DashboardController::class, 'reset'])->name('merchant.reset.submit');

Route::get('/merchant/reset-password/{token}', [\App\Http\Controllers\Merchant\DashboardController::class, 'showPasswordResetForm']);
Route::post('/merchant/reset-password/{token}', [\App\Http\Controllers\Merchant\DashboardController::class, 'resetPassword']);

Route::middleware('merchant')->group(function () {

    Route::get('merchant/verify-status', [\App\Http\Controllers\Merchant\DashboardController::class, 'verification'])->name('merchant.status');
    Route::get('merchant/dashboard', [\App\Http\Controllers\Merchant\DashboardController::class, 'index'])->name('merchant.dashboard.index');

    Route::get('merchant/products', [\App\Http\Controllers\Merchant\ItemsController::class, 'index'])->name('merchant.dashboard.product');
    Route::get('merchant/product/{product}/edit', [\App\Http\Controllers\Merchant\ItemsController::class, 'edit'])->name('merchant.dashboard.product.edit');

    Route::get('merchant/products-addons', [\App\Http\Controllers\Merchant\ItemsController::class, 'productaddons'])->name('merchant.dashboard.product-addons');

    Route::get('merchant/orders', [\App\Http\Controllers\Merchant\OrderController::class, 'index'])->name('merchant.dashboard.orders');
    Route::get('merchant/previous-orders', [\App\Http\Controllers\Merchant\OrderController::class, 'previous'])->name('merchant.dashboard.previous-order');

    // Settings
    Route::get('merchant/location', [\App\Http\Controllers\Merchant\LocationController::class, 'index'])->name('merchant.dashboard.location');
    Route::get('merchant/category', [\App\Http\Controllers\Merchant\CategoryController::class, 'index'])->name('merchant.dashboard.category');
    Route::get('merchant/settings', [\App\Http\Controllers\Merchant\SettingsController::class, 'index'])->name('merchant.dashboard.settings');
    Route::get('merchant/voucher', [\App\Http\Controllers\Merchant\VoucherController::class, 'index'])->name('merchant.dashboard.voucher');

    // Reports
    Route::get('merchant/report-sales-for-today', [\App\Http\Controllers\Merchant\ReportController::class, 'today'])->name('merchant.dashboard.report.salestoday');
    Route::get('merchant/report-sales', [\App\Http\Controllers\Merchant\ReportController::class, 'salesReport'])->name('merchant.dashboard.report.report');
    Route::get('merchant/soa', [\App\Http\Controllers\Merchant\ReportController::class, 'soa'])->name('merchant.dashboard.report.soa');
});
