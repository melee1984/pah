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
// Reporting
import OrderListing from './components/dashboard/pages/orders/OrdersComponents.vue';
import OrderSummary from './components/dashboard/pages/orders/SummaryComponent.vue';
import Report from './components/dashboard/pages/report/ReportComponent.vue';
import Member from './components/dashboard/pages/users/MemberComponent.vue';
import Merchant from './components/dashboard/pages/users/MerchantComponents.vue';
import BookingListing from './components/dashboard/pages/report/BookingComponent.vue';
import bookingAdd from './components/dashboard/pages/booking/AddComponent.vue';


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
// Order 
Vue.component('order-listing-view', OrderListing);
Vue.component('order-summary-view', OrderSummary);
Vue.component('report-view', Report);
Vue.component('member-view', Member);
Vue.component('merchant-view', Merchant);
Vue.component('booking-listing-view', BookingListing);
Vue.component('booking-add-form', bookingAdd);


/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.

 */
window.base_url = process.env.BASE_URL

const app = new Vue({
    el: '#app',
});



