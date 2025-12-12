import 'bootstrap';
import axios from 'axios'
import Vue from 'vue';
import VueSimpleAlert from 'vue-simple-alert';
import Swal from 'sweetalert2'

import 'sweetalert2/themes/bootstrap-5.css'


// Global Event Bus
window.Event = new Vue();
window.axios = axios;

// import VueGeolocation from 'vue-browser-geolocation';

// Optional global base URL
window.base_url = import.meta.env.VITE_APP_URL || ''; // Vite environment variable

// Disable production tip
Vue.config.productionTip = false;

/**
 * Plugins
 */
// Vue.use(VueGeolocation);

// Vue.use(VueGoogleMaps, {
//   load: {
//     key: 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk', // 
//     // key: 'AIzaSyCBU3bFyvWzW3R0g0kqQsZxTaay7ImkO14', //  - DEMO TESTINHG HERE 
//     libraries: 'places', // This is required if you use the Autocomplete plugin
//     // OR: libraries: 'places,drawing'
//     // OR: libraries: 'places,drawing,visualization'
//     // (as you require)

//     //// If you want to set the version, you can do so:
//      // v: '3.28',

//   },
//   autobindAllEvents: true,
// 	installComponents: true
// })


/**
 * Import Components
 */
// Restaurants
import detailedView from '@/components/restaurant/DetailedComponent.vue';
import restaurantView from '@/components/restaurant/ViewComponent.vue';
import restaurantSearch from '@/components/restaurant/SearchViewComponent.vue';

// Flowerstore
import flowerView from '@/components/flowerstore/FlowerstoreComponent.vue';

// Checkout
import summaryBasket from '@/components/basket/SummaryBasketComponent.vue';
import summaryCheckout from '@/components/checkout/SummaryComponent.vue';
import cartCheckout from '@/components/checkout/CheckoutComponent.vue';

// Reporting
import OrderListing from '@/components/merchant/pages/report/OrderListComponent.vue';

// Frontend
import partnerForm from '@/components/PartnerRegistrationComponent.vue';
import menuHeaderList from '@/components/MenuComponents.vue';
import registerForm from '@/components/includes/RegisterComponent.vue';
import loginForm from '@/components/includes/LoginComponent.vue';

// Booking
import bookingForm from '@/components/booking/BookingComponent.vue';

// Map
import userCurrentLocation from '@/components/map/UserCurrentLocationComponent.vue';
import userCurrentLocationDisplay from '@/components/map/UserCurrentLocationDisplayComponent.vue';
import simpleGeoLocation from '@/components/map/SimpleGeoLocationComponent.vue';

// Cart
import cartBasket from '@/components/cart/BasketComponent.vue';

/**
 * Register Global Components
 */
// Pagination

import dempMap from '@/components/map/demomap.vue';
import demoMap2 from '@/components/map/demo.vue';

// Vue.component('pagination-display', require('laravel-vue-pagination'));

// Flowerstore
Vue.component('front-flower-list', flowerView);

// Restaurant
Vue.component('front-search-list', restaurantSearch);
Vue.component('front-restaurant-list', restaurantView);
Vue.component('front-detailed-list', detailedView);
Vue.component('front-cart-checkout', summaryCheckout);
Vue.component('checkout-form', cartCheckout);
Vue.component('summary-basket-mobile', summaryBasket);

// Order
Vue.component('order-listing-view', OrderListing);

// User Frontend
Vue.component('partner-form', partnerForm);
Vue.component('menu-list', menuHeaderList);
Vue.component('register-form', registerForm);
Vue.component('login-form', loginForm);

// Booking
Vue.component('booking-form', bookingForm);
Vue.component('user-current-location', userCurrentLocation);
Vue.component('user-current-location-display', userCurrentLocationDisplay);
Vue.component('cart-basket-summary', cartBasket);
Vue.component('map-demo', dempMap);
Vue.component('sample-map', demoMap2);
Vue.component('get-current-location', simpleGeoLocation);


/**
 * Mount Vue App
 */
// window.base_url = process.env.BASE_URL

Vue.config.ignoredElements = [
  'gmp-map',
  'gmp-advanced-marker',
  'gmp-zoomchange',
  'gmp-internal-pinchange',
];

Vue.use(VueSimpleAlert);
window.vm = new Vue({
     el: '#app',
});
