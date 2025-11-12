<template>
    <div class="container" id="checkout-cart">
      <section class="popular-foods style-2 mt-4">
          <div class="container-fluid">
            <div class="row">
              <div class="col-lg-8">
                <div class="card mb-3 checkout-box">
                  <div class="card-body">
                    <h2 class="mb-3">Delivery Information</h2>
                     <div class="row">
                       <div class="mb-3 col-6">
                          <label for="pickupLocation" class="form-label mb-0">Date</label>
                          <select name="datepickup" v-model="pickup.date" class="form-control" @change="fetchTimings($event, $event.target.selectedIndex)">
                              <option value="">Date Pickup</option>
                              <option v-for="datePicker in dateArray" :value="datePicker.date">{{datePicker.date}}</option>
                          </select>
                        </div>
                          <div class="mb-3 col-6">
                          <label for="pickupLocation" class="form-label mb-0">Time</label>
                          <select name="timePickup" class="form-control" v-model="pickup.time">
                              <option value="">Time Pickup</option>
                              <option v-for="timer in timingsArray" :value="timer.time" :disabled="timer.disabled">{{timer.time}}</option>
                          </select>
                        </div>
                     </div>
                    <!-- Address -->
                    <user-Address v-bind:addresses="customer.addresses" v-bind:selectedaddressid="cart.address_id"  @onDelete="removeAddress" @onUpdateCartAddress="updateCartAddress" @onUpdateCustomer="updateCustomer"></user-Address>
                    <!-- End of the Address --> 
                  </div>
                </div>
                <!-- User Profile --> 
                  <user-Profile v-bind:customer="customer"></user-Profile>
                <!-- end of the User Profile --> 
                <div class="card mb-3 checkout-box">
                  <div class="card-body">
                      <div class="row">
                      <div class="col-md-12" id="payment_option">
                        <h2 class="mt-3">Payment</h2>
                         <div v-for="payment in payments" class="card mr-3 mb-3" v-bind:class="{ active: payment.id ==cart.payment_id }" style="width: 14rem;float:left;" :id="'payment_'+payment.id" v-on:click="selectPaymentOption(payment.id)">
                          <div class="card-body p-2">
                          <h6 class="card-title">{{ payment.title }}</h6>
                            <p class="card-text">
                              {{ payment.description }}
                            </p>
                            <span class="material-icons">{{ payment.class }}</span>
                          </div>
                        </div>
                    
                      </div>
                      <div class="col-md-12 text-checkout-danger" v-if="cart.payment_id==null"> 
                        Note: Please select payment gateway
                      </div>

                      <div class="col-md-4 text-right">&nbsp;</div>
                  </div>
                    <p>By making this purchase you agree to our <a href="#">terms and conditions</a>.</p>
                    <span v-if="!formOkay">
                      <a class="btn btn-block btn-secondary btn-disabled" disabled>CONFIRM ORDER</a>
                    </span>
                    <span v-if="formOkay">
                      <a  class="btn btn-block btn-pahatud" v-on:click="confirm">CONFIRM ORDER</a>
                    </span>
                    
                  </div>
                </div>
              </div>
              <div class="col-lg-4">
                  <!-- Cart Summary --> 
                  <cart-summary v-bind:cart="cart" v-bind:summary="summary"></cart-summary>
                  <!-- End of the Cart Summary --> 
              </div>
            </div>
              </div>
      </section>
      <!-- Modal -->
      <div class="modal fade" id="smsNotification" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLongTitle">SMS Confirmation</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>We have sent the OTP to your registered mobile no. ({{ customer.mobile }}). Please check to confirm the order. Thank you.</p>
<!--               <a href="" class="btn btn-pahatud btn-block">Send OTP to my mobile</a>
 -->              <input type="number" maxlength="5" class="form-control" name="opt" placeholder="e.g 09743" v-model="smsCode">
              <p ><a class="mt-3 btn btn-sm" disabled v-if="timerInterval >0" >Re-send OTP Password <span v-if="timerInterval >0">({{ timerInterval }})</span></a>
                <a class="mt-3 btn btn-sm text-danger" v-if="timerInterval <=0" v-on:click="resendOTP">Re-send OTP Password <span v-if="timerInterval >0">({{ timerInterval }})</span></a>
              </p>
              
            </div>
            <div class="modal-footer">
              <button type="button" class="btn" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-pahatud" v-on:click="proceed">Proceed</button>
            </div>
          </div>
        </div>
      </div>

  </div>
</template>

<script>
    import userAddress from '@/components/checkout/includes/AddressComponent.vue';
    import userProfile from '@/components/checkout/includes/UserProfileComponent.vue';
    import cartSummary from '@/components/checkout/includes/CartSummaryComponent.vue';

    export default {
        components: {
           'user-Address': userAddress,
           'user-Profile': userProfile,
           'cart-summary': cartSummary,
        },
        data() {
          return {
              data: {},
              customer: {},
              addresses: {},
              cart: {},
              summary: {},
              payments: {},
              delivery_time: {},
              profileAction: 'view',
              addressAction: 'view',
              selectedAddressId: "",
              timerInterval: 0,
              smsCode: "",
              dateArray: {},
              timingsArray: {},
              pickup: {
                date: "",
                time: "",
              }
          }
        },
          created() {
          Event.$on('reloadCheckout', () => {
            this.fetchData();
          });
         
        },
        mounted() {
            this.fetchData();
        },
        computed: {
            formOkay: function () {
              if (!this.cart.payment_id)  {
                return false;
              }
              else if(!this.cart.address_id) {
               return false;
              }
              else if(!this.pickup.date) {
                return false;
              }
              else if(!this.pickup.time) {
                return false;
              }
              else if (!this.customer.mobile) {
                return false;
              }
              return true;
            }
        },
        methods: {
            smsTimer: function () {
             setInterval(() => {
                  if (this.timerInterval ==0) { 
                    return 0;
                  }
                  else {
                    this.timerInterval--;
                  }
             }, 1000)
            },
            editProfile: function() {
            },
            resetOTP() {
                this.timerInterval = 180;
                this.smsTimer();
            },
            resendOTP() {
              this.confirm();
            },
            confirm: function() { 
              self = this;
              if (this.timerInterval == 0) {
                  axios.post('/api/checkout/sms/submit?api_token='+api_token).then((response) => {
                    if (response.data.status) {
                      $('#smsNotification').modal(); this.resetOTP();
                    }
                    else {
                      toastr.error(response.data.message);
                    }
                  }).catch((errors) => {
                    toastr.error(errors);
                  }); 
              }
              else {
                  $('#smsNotification').modal();
              }
            },
            actionProfile: function(action) {
                this.profileAction = action;
            },
            fetchData: function() {
              var self = this;
              axios.post('/api/checkout?api_token='+api_token).then(function (response) {
                  self.customer = response.data.customer;
                  self.addresses = response.data.customer.addresses;
                  self.cart = response.data.cart;
                  self.summary = response.data.summary;
                  self.payments = response.data.payment;
                  self.delivery_time = response.data.delivery_time;

                  self.dateArray = response.data.timings;
                  // Set by Default 
                  self.pickup.date = "";
                  self.pickup.time = "";
                  
                  if (self.cart.delivery_date) {
                    self.pickup.date = self.cart.delivery_date;
                  }
                  else {
                    self.pickup.date = self.dateArray[0].date;
                  }
                  self.timingsArray = self.dateArray[0].timings;
                  if (self.cart.delivery_time) {
                    self.pickup.date = self.cart.delivery_time;
                  }
                  else {
                    self.pickup.time = self.timingsArray[0].time;
                  }
                  
              })
              .catch(function (error) {
                  console.log(error);
              });

            },
            validate: function() {
            },
            editAccount: function() {
            },
            removeAddress: function(address) {
              this.addresses.splice(address, 1);
              this.fetchData();
            },
            updateCartAddress: function(address_id) {
                this.cart.address_id = address_id;
                this.selectedAddressId = address_id;
                this.fetchData();
            },
            updateCustomer: function(addresses) {
                this.customer.addresses = addresses;
                this.fetchData();
            },
            selectPaymentOption: function(payment_id) {

              $('#payment_option .card').removeClass('border-danger active');
              $('#payment_'+payment_id).addClass('border-danger active');

                let formData = new FormData();
                formData.append('payment', payment_id)
              
                axios.post('/api/checkout/payment/update/submit?api_token='+api_token, formData).then((response) => {
                  if (response.data.status) {
                    this.cart.payment_id = response.data.payment_id;
                    toastr.success(response.data.message);
                  }
                  else {
                    toastr.error(response.data.message);
                  }
                }).catch((errors) => {
                    toastr.error(errors);
                }); 
            },
            proceed: function() {
                let formData = new FormData();
                formData.append('smsCode', this.smsCode)
                formData.append('delivery_date', this.pickup.date);
                formData.append('delivery_time', this.pickup.time);
                axios.post('/api/checkout/submit?api_token='+api_token, formData).then((response) => {
                  if (response.data.status) {
                      window.location.href = response.data.redirectTo;
                  }
                  else {
                    toastr.error(response.data.message);
                  }
                }).catch((errors) => {
                    toastr.error(errors);
                }); 
            },
            fetchTimings: function(event, selectedIndex) {
              this.timingsArray = this.dateArray[selectedIndex-1].timings;
              this.pickup.time = this.timingsArray[0].time;
          },
            
        }
    }

</script>

