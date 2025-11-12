/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');
window.Event = new Vue();
// import * as VueGoogleMaps from 'vue2-google-maps';
import VueGeolocation from 'vue-browser-geolocation';

// Restaurants 
import detailedView from './components/restaurant/DetailedComponent.vue';
import restaurantView from './components/restaurant/ViewComponent.vue';
// Checkout 
import cartCheckout from './components/checkout/SummaryComponent.vue';
// Merchant Admin Form 
import merchantForm from './components/merchant/RegisterComponent.vue';
import merchantForgotForm from './components/merchant/form/ResetPasswordComponent.vue';
// Products 
import productsViewForm from './components/merchant/pages/products/ViewComponent.vue';
import productsEditForm from './components/merchant/pages/products/EditComponent.vue';
import CategoryViewForm from './components/merchant/pages/category/ViewComponent.vue';
import LocationViewForm from './components/merchant/pages/location/ViewComponent.vue';
import VoucherViewForm from './components/merchant/pages/voucher/ViewComponent.vue';
import OrderView from './components/merchant/pages/orders/ViewComponent.vue';
import ProfileViewForm from './components/merchant/pages/profile/ViewComponent.vue';
import OrderSummary from './components/merchant/pages/orders/SummaryComponent.vue';
// Reporting
import OrderListing from './components/merchant/pages/report/OrderListComponent.vue';
import StoreOnline from './components/merchant/includes/MerchantStoreOnlineComponent.vue';
import TodayReport from './components/merchant/pages/report/OrderReportTodayComponent.vue';

Vue.use(VueGeolocation);
Vue.component('pagination-display', require('laravel-vue-pagination'));

// Restaurant 
Vue.component('front-restaurant-list', restaurantView);
Vue.component('front-detailed-list', detailedView);
Vue.component('front-cart-checkout', cartCheckout);

// Merchant 
Vue.component('merchant-register-form', merchantForm);
Vue.component('merchant-product-edit', productsEditForm);
Vue.component('merchant-forgot-form', merchantForgotForm);
Vue.component('merchant-products-view', productsViewForm);
// Order 
Vue.component('merchant-order-view', OrderView);
Vue.component('order-listing-view', OrderListing);
// Category 
Vue.component('merchant-category-view', CategoryViewForm);
Vue.component('merchant-location-view', LocationViewForm);
Vue.component('merchant-voucher-view', VoucherViewForm);
Vue.component('merchant-profile-view', ProfileViewForm);
Vue.component('order-summary-view', OrderSummary);
Vue.component('store-online-button', StoreOnline);
Vue.component('today-report', TodayReport);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.

 */
window.base_url = process.env.BASE_URL

const app = new Vue({
    el: '#app',
});



