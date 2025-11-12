/**
 * Load JavaScript dependencies for this project, including Vue.
 * This is a great starting point for building robust, powerful applications.
 */
import 'bootstrap';
import Vue from 'vue';
import * as VueGoogleMaps from 'vue2-google-maps';
// import VueGeolocation from 'vue-browser-geolocation';
import VueSimpleAlert from 'vue-simple-alert';

// Global Event Bus
window.Event = new Vue();

// Optional global base URL
window.base_url = import.meta.env.VITE_APP_URL || ''; // Vite environment variable

// Disable production tip
Vue.config.productionTip = false;

/**
 * Plugins
 */
Vue.use(VueGeolocation);
Vue.use(VueSimpleAlert);
// Vue.use(VueGoogleMaps, {
//   load: {
//     key: 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk',
//     libraries: 'places',
//   },
//   autobindAllEvents: true,
//   installComponents: true,
// });

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
import summaryBasket from '@/components/basket/SummarybasketComponent.vue';
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

// Cart
import cartBasket from '@/components/cart/BasketComponent.vue';

/**
 * Register Global Components
 */
// Pagination
import LaravelPagination from 'laravel-vue-pagination';
Vue.component('pagination-display', LaravelPagination);

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

/**
 * Mount Vue App
 */
import App from '@/components/App.vue';

new Vue({
  render: h => h(App),
}).$mount('#app-layout');
