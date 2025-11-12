<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Booking\RequestController;
use App\Http\Controllers\Flower\FlowerstoreController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Restaurant\PageController as RestaurantPageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PageController::class, 'index']);

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/imager', 'ImageController@image')->name('cacheImage');
Route::get('/image/size', 'ImageController@imageResize')->name('image-resize');

Route::get('/byplaceId', 'Map\DistanceController@getByPlaceId');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/distance', 'Map\DistanceController@getKilometerByCoodinates');
Route::get('/sign-in/fb/complete', 'Api\User\AccessController@registerFB')->name('user.registerFB');
Route::post('/search', [RestaurantPageController::class, 'search'])->name('search');
Route::get('/restaurants/{search_string}', [RestaurantPageController::class, 'searchTag'])->name('search.tag');

Route::get('/home', 'PageController@index')->name('/');
Route::get('/about-us', 'PageController@aboutus')->name('aboutus');
Route::get('/be-partner', [PageController::class, 'bepartner'])->name('bepartner');

Route::get('/contact-us', [PageController::class, 'contactus'])->name('contactus');
Route::get('/privacy-policy', [PageController::class, 'privacypolicy'])->name('privacypolicy');
Route::get('/terms-of-use', [PageController::class, 'termsofuse'])->name('termsofuse');
Route::get('/fraud-prevention', [PageController::class, 'fraudprevention'])->name('fraudprevention');
Route::get('/payment-method', [PageController::class, 'paymentmethod'])->name('paymentmethod');
// Front End Restaurant View 
Route::get('/restaurant/{partner:slug}', [RestaurantPageController::class, 'view'])->name('restaurant.view');
Route::get('/restaurants', [RestaurantPageController::class, 'show'])->name('restaurant.show');
// Flowerstore 
Route::get('/flowerstore/{partner:slug}', [RestaurantPageController::class, 'view'])->name('flowerstore.view');
Route::get('/flowerstore', [FlowerstoreController::class, 'show'])->name('flowerstore.show');

/// Errand 
Route::get('/request/booking', [RequestController::class, 'index'])->name('new.bookings');
Route::get('/request-booking/completed', 'Booking\RequestController@success')->name('booking.success');
// Category 
Route::get('/category/{category}', 'CategoryController@bepartner')->name('category.section');
Route::get('/partner/{partner}', 'PartnerController@bepartner')->name('partner.section');
Route::post('newsletter/submit', 'NewsletterController@subscribe')->name('newsletter.submit');
Route::get('/user/profile', 'ProfileController@index')->name('profile.home');
Route::get('profile/soa', 'Merchant\ReportController@soa')->name('merchant.dashboard.report.soa');
// Checkout 
// 
Route::post('login/submit', 'Api\User\AccessController@loginAccess')->name('login.submit');
Route::post('contact/submit', 'PageController@storeContact')->name('contact.submit');

Route::group(['middleware' => 'auth'], function() {
    
  Route::get('/checkout', 'Checkout\PageController@index')->name('checkout.index');
  Route::get('checkout/{cart:order_no}/success', 'Api\CartController@success')->name('checkout.success');
  // Dashboard 
  Route::get('/dashboard', 'Booking\RequestController@bookings')->name('profile.dashboard');
  //** BOOKINGS
  Route::get('/profile/bookings', 'Booking\RequestController@bookings')->name('profile.bookings');
  // Orders 
  Route::get('/profile/orders', 'Order\OrderHistoryController@index')->name('profile.orders');
  Route::get('/profile/order/{cart:order_no}', 'Order\OrderHistoryController@view')->name('profile.orders.view');

  Route::get('/logout', 'Merchant\DashboardController@showPasswordResetForm');
  
});

// **********************************************************************
// **********************************************************************
// ********************** Admin Dashboard *******************************
// **********************************************************************
// **********************************************************************
 
Route::get('/data/login', [DashboardController::class, 'login']);
Route::post('/data/dashboard/login/submit', [DashboardController::class, 'validateLogin'])->name('dashboard.login.submit');
Route::get('/data/dashboard/logout', [DashboardController::class, 'logout'])->name('dashboard.logout');

// Admin 
Route::group(['middleware' => 'admin'], function() {
	
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

  Route::get('merchant/aulogin/{id}', function($loginId, Request $request) {

      Auth::logout();
      $request->session()->invalidate();
      $request->session()->regenerateToken();

      $user = User::find($loginId);
      Auth::login($user, true);

      return redirect()->route('merchant.dashboard.index');
  });

});

// End of the Admin Dashboard 
// **********************************************************************
// **********************************************************************
// **********************************************************************
// ******************* Merchant Dashboard *******************************
// **********************************************************************
// **********************************************************************

Route::get( '/login/{social}', 'SocialLoginController@getSocialRedirect' );
Route::get( '/login/{social}/callback', 'SocialLoginController@getSocialCallback' );

Route::get('/merchant/dashboard/login', [\App\Http\Controllers\Merchant\DashboardController::class, 'login'])->name('merchant.login');
Route::get('/merchant/login', [\App\Http\Controllers\Merchant\DashboardController::class, 'login'])->name('merchant.login');
Route::get('/merchant/register', [\App\Http\Controllers\Merchant\DashboardController::class, 'register'])->name('merchant.register');

Route::get('/merchant/forgot', 'Merchant\DashboardController@forgot')->name('merchant.forgot');
Route::get('/merchant/reset', 'Merchant\DashboardController@forgot')->name('merchant.reset');
Route::get('/merchant/logout', 'Merchant\DashboardController@logout')->name('merchant.logout');
Route::get('/merchant/setpassword', 'Merchant\DashboardController@setPassword')->name('merchant.setPassword');

Route::post('/merchant/dashboard/login/submit', 'Merchant\DashboardController@validateLogin')->name('merchant.login.submit');
Route::post('/merchant/register/submit', 'Merchant\DashboardController@storeMerchant')->name('merchant.register.submit');
Route::post('/merchant/reset/submit', 'Merchant\DashboardController@reset')->name('merchant.reset.submit');

Route::get('/merchant/reset-password/{token}', 'Merchant\DashboardController@showPasswordResetForm');
Route::post('/merchant/reset-password/{token}', 'Merchant\DashboardController@resetPassword');

Route::group(['middleware' => 'merchant'], function() {

	Route::get('merchant/verify-status', 'Merchant\DashboardController@verification')->name('merchant.status');
	Route::get('merchant/dashboard', 'Merchant\DashboardController@index')->name('merchant.dashboard.index');
	Route::get('merchant/products', 'Merchant\ItemsController@index')->name('merchant.dashboard.product');
	Route::get('merchant/product/{product}/edit', 'Merchant\ItemsController@edit')->name('merchant.dashboard.product.edit');
	Route::get('merchant/products-addons', 'Merchant\ItemsController@productaddons')->name('merchant.dashboard.product-addons');
	// Order 
	Route::get('merchant/orders', 'Merchant\OrderController@index')->name('merchant.dashboard.orders');
	Route::get('merchant/previous-orders', 'Merchant\OrderController@previous')->name('merchant.dashboard.previous-order');
	// Settings 
	Route::get('merchant/location', 'Merchant\LocationController@index')->name('merchant.dashboard.location');
	Route::get('merchant/category', 'Merchant\CategoryController@index')->name('merchant.dashboard.category');
	Route::get('merchant/settings', 'Merchant\SettingsController@index')->name('merchant.dashboard.settings');
	Route::get('merchant/voucher', 'Merchant\VoucherController@index')->name('merchant.dashboard.voucher');
	// Report
	Route::get('merchant/report-sales-for-today', 'Merchant\ReportController@today')->name('merchant.dashboard.report.salestoday');
	Route::get('merchant/report-sales', 'Merchant\ReportController@salesReport')->name('merchant.dashboard.report.report');
	Route::get('merchant/soa', 'Merchant\ReportController@soa')->name('merchant.dashboard.report.soa');

});




require __DIR__.'/auth.php';




