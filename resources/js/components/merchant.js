/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');
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
// Reporting
import OrderListing from './components/merchant/pages/report/OrderListComponent.vue';
// Frontend 
import partnerform from './components/PartnerRegistrationComponent.vue';
import menuheaderlist from './components/MenuComponents.vue';
import registerform from './components/includes/RegisterComponent.vue';

Vue.use(VueGeolocation);

// Vue.use(VueGoogleMaps, {
//   load: {
//     key: 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk',
//     libraries: 'places', // This is required if you use the Autocomplete plugin
//     // OR: libraries: 'places,drawing'
//     // OR: libraries: 'places,drawing,visualization'
//     // (as you require)

//     //// If you want to set the version, you can do so:
//     // v: '3.26',
//   },
// 	installComponents: true
// })

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

// User Frontend 
Vue.component('partner-form', partnerform);
Vue.component('menu-list', menuheaderlist);
Vue.component('register-form', registerform);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.

 */
window.base_url = process.env.BASE_URL


const app = new Vue({
    el: '#app',
});



