<template>
   <section >
        <div id="booking" class="blog-section blog-single recepi-single padding-tb" style="background-image: url(../../images/booking/banner1.jpg);">
            <div class="container">
              <div class="row">
                <div class="col-xs-12 col-sm-12  col-md-12 col-lg-6">
                    <div class="card" v-if="bookingform">
                      <div class="card-header">
                        Booking Form
                      </div>
                      <div class="card-body">
                           <div class="input-group p-0 mb-2">
                      <gmap-autocomplete
                        id="origin"
                         ref="mapRef"
                        @input="value = $event.target.value"
                        placeholder="Pickup Address"
                        class="form-control"
                        aria-label="Username" 
                        aria-describedby="basic-addon1"
                         @keyup.enter="usePlace" @place_changed="setPlace" 
                         style="z-index: 9;"
                         :options="{fields: ['geometry']}"
                        ></gmap-autocomplete>
                        <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">
                            <a href="javascript:void(0)"><i class="icofont-location-pin"></i></a>
                          </span>
                        </div>
                      </div>
                      <div class="input-group p-0 mb-2">
                      <gmap-autocomplete
                        id="destination"
                         ref="mapRef"
                        @input="value = $event.target.value"
                        placeholder="Drop-off Address"
                        class="form-control"
                        aria-label="Username" 
                        aria-describedby="basic-addon1"
                         @keyup.enter="usePlaceDropOff" @place_changed="setPlaceDropOff" 
                         style="z-index: 9;"
                         :options="{fields: ['geometry']}"
                        ></gmap-autocomplete>
                        <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">
                            <a href="javascript:void(0)"><i class="icofont-location-pin"></i></a>
                          </span>
                        </div>
                      </div>
                       <a href="javascript:void(0)" class="btn btn-pahatud btn-sm btn-block" v-on:click="checkdestiantion">Check Rate</a>
                      </div>
                  </div>
                
                  <!-- Display Rate --> 
                  <div class="card" v-if="displayRate">
                    <div class="card-header">
                      RATE
                    </div>
                    <div class="card-body">
                      <h3 style="font-size: 21px;"><strong>Distance: {{ distance }} </strong> </h3>
                      <h3 style="font-size: 21px;"><strong>Delivery Fee: {{ rate }} </strong> </h3>

                      <a href="javascript:void(0)" v-on:click="booknow" class="btn btn-pahatud btn-sm btn-block">Book Now</a>
                    </div>
                  </div>
                  <!-- End Display Rate --> 
                  <div class="card" id="pickupform" v-if="pickupform">
                    <div class="card-header">
                      <h4>Pickup</h4>
                    </div>
                    <div class="card-body">

                      <div class="card mb-2">
                        <div class="card-header">
                          Pickup Date & Time
                        </div>
                        <div class="card-body">
                           <div class="row">
                              <div class="mb-3 col-6">
                              <label for="pickupLocation" class="form-label">Date Pickup</label>
                              <select name="datepickup" v-model="pickup.date" class="form-control" @change="fetchTimings($event, $event.target.selectedIndex)">
                                  <option v-for="datePicker in dateArray" :value="datePicker.date">{{datePicker.date}}</option>
                              </select>
                            </div>

                              <div class="mb-3 col-6">
                              <label for="pickupLocation" class="form-label">Time Pickup</label>
                              <select name="timePickup" class="form-control" v-model="pickup.time">
                                  <option v-for="timer in timingsArray" :value="timer.time" :disabled="timer.disabled">{{timer.time}}</option>
                              </select>
                            </div>

                              <div class="mb-3 col-12">
                              <label for="itemdescription" class="form-label">Errand Type</label>
                              <select name="errand_type" id="errand_type" class="form-control" v-model="pickup.errand_type" @change="changeErrandType($event, $event.target.selectedIndex)">
                                  <option v-for="errand in errandType" :value="errand.id" :disabled="errand.disabled">{{errand.title}}</option>
                              </select>
                            </div>

                           </div>
                        </div>
                    </div>

                     <div class="card mb-2">
                        <div class="card-header">
                          Pickup Information
                        </div>
                        <div class="card-body">

                             <div class="mb-3">
                              <label for="sendername" class="form-label">Sender Name</label>
                              <input type="text" class="form-control" id="sendername" placeholder="Sender Name" v-model="pickup.name">
                            </div>


                            <div class="mb-3">
                              <div class="alert alert-secondary text-right" role="alert">
                                 {{ pickup.address_location }} <a href="javascript:void(0)"><span class="material-icons" style="color:#f03b32;float: right;position: relative;top: 5px;">my_location</span></a>
                              </div>
                              <label for="pickupLocation" class="form-label">Sender Pickup Address</label>
                              <textarea class="form-control"  id="pickupLocation"  v-model="pickup.address"placeholder="Complete Pickup Address"></textarea>
                            </div>
                           
                            <div class="mb-3">
                              <label for="sendermobile" class="form-label">Mobile</label>
                              <input type="number" class="form-control" id="sendermobile" placeholder="eg. 09162986541" v-model="pickup.mobile">
                            </div>  

                             <div class="mb-3">
                              <label for="itemdescription" class="form-label">Who will pay the delivery fee</label>
                              <select name="who_will_pay" id="who_will_pay" v-model="pickup.who_will_pay" class="form-control">
                                <option value="1">Sender will pay the Delivery Fee</option>
                                <option value="2">Receiver will pay the Delivery Fee</option>
                              </select>
                            </div>

                              <div class="mb-3">
                              <label for="itemdescription" class="form-label">Cash on Hand</label>
                               <input type="text" placeholder="Enter the amount to pay" class="form-control" id="sendermobile" v-model="pickup.cod">
                               <span class="text-muted"><small>Rider will pay the the sender and collect the payment during Drop-off</small></span>
                            </div>

                            <div class="mb-3">
                              <label for="itemdescription" class="form-label">Item Description</label>
                               <input type="text" placeholder="eg. Food, Document, Plant, XL Food Trays" class="form-control" id="sendermobile" v-model="pickup.itemdescription">
                            </div>

                            <div class="mb-3">
                              <label for="senderlandmark" class="form-label">Landmark</label>
                                <input type="text" placeholder="Landmark, front of Durian Hotel" class="form-control" id="sendermobile" v-model="pickup.landmark">
                            </div>
                            
                            <a href="javascript:void(0)" v-on:click="nextDropoff" class="btn btn-pahatud btn-sm btn-block">Next</a>
                          </div>
                        </div>
                    </div>

                  </div>
                  <br>
                   <div class="card" id="dropoffform" v-if="dropoffform">
                    <div class="card-header">
                      <h4>Drop-off</h4>
                    </div>
                    <div class="card-body">
                      <div class="mb-3">

  <div class="alert alert-secondary text-right" role="alert">
                           {{ delivery.address_location }} <a href="javascript:void(0)"><span class="material-icons" style="color:#f03b32;float: right;position: relative;top: 5px;">my_location</span></a>
                        </div>
                        <label for="pickupLocation" class="form-label">Drop Off Location</label>
                        <textarea class="form-control" id="deliveryAddress" v-model="delivery.address" placeholder="Complete Drop Off Address" autofocus></textarea>
                      </div>
                      <div class="mb-3">
                        <label for="exampleFormControlTextarea1" class="form-label">Receiver Name</label>
                        <input type="text" class="form-control" id="" placeholder="Drop Off Location" v-model="delivery.name">
                      </div>
                       <div class="mb-3">
                        <label for="exampleFormControlTextarea1" class="form-label">Mobile</label>
                        <input type="number" class="form-control" id="" placeholder="eg. 09162986541 " v-model="delivery.mobile">
                      </div>
                      <div class="mb-3">
                        <label for="exampleFormControlTextarea1" class="form-label">Landmark/Note</label>
                        <textarea class="form-control" v-model="delivery.landmark" placeholder="Landmark"></textarea>
                      </div>
                      <a href="javascript:void(0)" v-on:click="proceedBooking" class="btn btn-pahatud btn-sm btn-block">Proceed Booking</a>
                      <a href="javascript:void(0)" v-on:click="backpickup" class="btn">Back</a>
                    </div>
                    </div>

                     <div class="card" id="summaryform" v-if="summaryform">
                    <div class="card-header">
                      <h4>Booking form Summary</h4>
                    </div>
                    <div class="card-body">
                      <div class="card mb-2">
                        <div class="card-header">
                          Errand
                        </div>
                        <div class="card-body">
                             <div class="bd-example">
                                <strong>Date and Time</strong>
                                <p>{{ pickup.date }} @ {{ pickup.time }} </p>
                                <strong>Errand Type:</strong> 
                                <p>{{ errandTypeText }}  </p>
                            </div>
                        </div>
                    </div>


                      <div class="card mb-2">
                        <div class="card-header">
                          Pickup Information
                        </div>
                        <div class="card-body">
                             <div class="bd-example">
                                <strong>Pickup Location</strong><br>
                                {{ pickup.address }}<br>
                                <strong>Sender:</strong> <br>{{ pickup.name }}<br>
                               <strong>Mobile: </strong><br>{{ pickup.mobile }} <br>
                                <strong>Landmark</strong>
                                <p>{{ pickup.landmark }}</p>
                                <hr>
                                <strong>Cash on hand</strong>
                                <p>{{ pickup.cod }}</p>
                                <strong>Who will pay the delivery fee</strong>
                                <p v-if="pickup.who_will_pay==1">
                                  Sender will pay the Delivery Fee  
                                </p>
                                <p v-if="pickup.who_will_pay==2">
                                  Receiver will pay the Delivery Fee  
                                </p>

                            </div>
                        </div>
                    </div>

                     <div class="card mb-2">
                        <div class="card-header">
                          Drop-off Information
                        </div>
                        <div class="card-body">
                            <div class="bd-example">
                              <address>
                                <strong>Drop-off Location</strong><br>
                               {{ delivery.address }}<br>
                                <strong>Receiver: </strong><br>{{ delivery.name }}<br>
                               <strong>Mobile:</strong> <br>{{ delivery.mobile }} <br>
                                <strong>Landmark</strong><br>
                                <p>{{ delivery.landmark }}</p>

                              </address>
                              <hr>
                              <div class="row">
                                <div class="col-md-6" >
                                  <address>
                                    <strong>Item Description </strong>
                                    <p>{{ pickup.itemdescription }}</p>
                                     <strong>Distance: </strong>
                                    <p>{{ distance }}</p> 
                                    <strong>Duration </strong><br>
                                    <p>45mins</p>
                                  </address>
                                </div>
                                 <div class="col-md-6" id="delivery_fee">
                                    <label class="mb-1">Delivery Fee</label>
                                    <h2>PHP</h2>
                                    <h3>{{ rate }} </h3>
                                 </div>
                              </div>
                            </div>
                        </div>
                    </div>


                     <p class="mt-2">By confirming the booking below, I agree with the Privacy Policy and Terms and Conditions of Pahatud Delivery Services.</p>
                      <a href="javascript:void(0)" v-on:click="confirmBooking" class="btn btn-pahatud btn-sm btn-block">CONFIRM BOOKING</a>
                      <a href="javascript:void(0)" v-on:click="backpickup" class="btn" style="font-size: 14px;font-weight: 100;">Cancel</a>
                    </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
        </div>
    </section>
</template>

<script>
 
    export default {
      
        data() {
          return {
              data: {},
              place: null,
              pickup: {
                coordinates: {
                  lat: 0,
                  lng: 0,
                },
                address_location: '',
                name: '',
                address: '',
                mobile: '',
                landmark: '',
                itemdescription: '',
                cod: 0,
                who_will_pay: 1,
                time: '',
                date: 'Today',
                errand_type: 1,
              },
              delivery: {
                coordinates: {
                  lat: 0,
                  lng: 0,
                },
                address_location: '',
                name: '',
                address: '',
                mobile: '',
                landmark: '',
              },
              rate: 0,
              duration: 0,
              distance:0,
              pickupform: false,
              dropoffform: false,
              bookingform: true,
              displayRate: false,
              summaryform: false,

              dateArray: {},
              timingsArray: {},
              errandType: {},
              timerCount: 3,
              isLogged: false,
              errandTypeText: "Pickup and Delivery",
          }
        },
        created() {
        },
        mounted() {
            if (!isLogged) {
                $('#myModalLogin').modal();
            }

            this.gettimings();
        },
        watch: {
            timerCount: {
                handler(value) {
                    if (value >= 0) {
                        setTimeout(() => {
                            this.timerCount--;
                        }, 1000);
                    }
                    else {
                        this.timerCount = 3;

                        if (!isLogged) {
                          $('#myModalLogin').modal();
                        }
                    }
                },
                immediate: true // This ensures the watcher is triggered upon creation
            }
        },
        computed: {
        // 
        },
        methods: {
          gettimings() {

             var self = this;
              axios.get('/api/getschedule').then(function (response) {
                  self.dateArray = response.data.schedule;
                  self.timingsArray  = self.dateArray[0].timings;
                  self.errandType = response.data.errandType;

                  self.pickup.time = self.timingsArray[0].time;

              })
              .catch(function (error) {
                  console.log(error);
              });

          },
          setPlace(place) {
            this.place = place;
            this.pickup.coordinates.lat = this.place.geometry.location.lat();
            this.pickup.coordinates.lng = this.place.geometry.location.lng();
          },
           usePlace(place) {
              this.markers = [];
              if (this.place) {
              let center = {
                lat: this.place.geometry.location.lat(),
                lng: this.place.geometry.location.lng(),
              }
              this.pickup.coordinates.lat = this.place.geometry.location.lat();
              this.pickup.coordinates.lng = this.place.geometry.location.lng();
              localStorage.pickupCenter = JSON.stringify(center);
             }
          },
          setPlaceDropOff(place) {
            this.place = place;
            this.delivery.coordinates.lat = this.place.geometry.location.lat();
            this.delivery.coordinates.lng = this.place.geometry.location.lng();
          },
           usePlaceDropOff(place) {
              this.markers = [];
              if (this.place) {
              let center = {
                lat: this.place.geometry.location.lat(),
                lng: this.place.geometry.location.lng(),
              }
              this.delivery.coordinates.lat = this.place.geometry.location.lat();
              this.delivery.coordinates.lng = this.place.geometry.location.lng();

              localStorage.deliveryCenter = JSON.stringify(center);
             }

          },
          checkRate: function() {

            this.displayRate = false;
             axios.post('/api/distance?api_token='+api_token, { 
                pick_lat: this.pickup.coordinates.lat,
                pick_lng: this.pickup.coordinates.lng,
                del_lat: this.delivery.coordinates.lat,
                del_lng: this.delivery.coordinates.lng,
              }).then((response) => {
                if (response.data.status) {
                  this.duration = response.data.duration;
                  this.distance = response.data.distance;
                  this.rate = response.data.rate;
                    this.displayRate = true;
                }
                else {
                  toastr.info(response.data.message);
                }
              }).catch((errors) => {
                  toastr.error(errors);
              }); 
          },
          booknow: function() {

            this.$alert("We are still on progress doing the Booking Form. As of now, only rate inquiry is available. To complete your booking please send the booking form to our facebook messenger. Thank you");


            // this.pickup.address_location = $('#origin').val();
            // this.delivery.address_location =  $('#destination').val();
            // this.displayRate = false;
            // this.pickupform = true;
            // this.bookingform = false;
          },
          nextDropoff: function() {
            this.pickupform = false;
            this.dropoffform = true;

            $('html, body #dropoffform').animate({scrollTop : 0}, 200);
            $('#deliveryAddress').focus();

          },
          backpickup: function() {
            this.pickupform = true;
            this.dropoffform = false;
            this.summaryform = false;
          },
          proceedBooking: function() {
            this.dropoffform = false;
            this.summaryform = true;
          },
          checkdestiantion: function() {
            this.displayRate = false;
            if (this.validateForm()) {
             axios.post('/api/getdistance?api_token='+api_token, { 
                origin: $('#origin').val(),
                destination: $('#destination').val()
              }).then((response) => {
                if (response.data.status) {
                  this.duration = response.data.duration;
                  this.distance = response.data.distance;
                  this.rate = response.data.rate;
                    this.displayRate = true;
                }
                else {
                  toastr.info(response.data.message);
                }
              }).catch((errors) => {
                  toastr.error(errors);
              }); 

            }
          },
          validateForm: function() {
            $('#origin').removeClass('is-invalid');
            $('#destination').removeClass('is-invalid');

            if ($('#origin').val() == "") {
              $('#origin').addClass('is-invalid');
              return false;
            }

            if ($('#destination').val() == "") {
              $('#destination').addClass('is-invalid');
              return false;
            }

            return true;
          },
          reValidatedAgainFields: function() {
            $('#origin').removeClass('is-invalid');
            $('#destination').removeClass('is-invalid');

            if ($('#origin').val() == "") {
              $('#origin').addClass('is-invalid');
              return false;
            }

            if ($('#destination').val() == "") {
              $('#destination').addClass('is-invalid');
              return false;
            }

            return true;
          },
          fetchTimings: function(event, selectedIndex) {
              this.timingsArray = this.dateArray[selectedIndex].timings;
              this.pickup.time = this.timingsArray[0].time;
          },
          changeErrandType: function(event, selectedIndex) { 
              this.errandTypeText = this.errandType[selectedIndex].title;
          },
          confirmBooking: function() {
              
              if (this.reValidatedAgainFields()) {

              }


          }
        }
    }

</script>

