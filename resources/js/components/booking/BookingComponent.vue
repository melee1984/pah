<template>
   <section class="popular-foods">
        <div class="blog-section blog-single recepi-single padding-tb">
            <div class="container-fluid">
            <div class="row">
              <div class="col-lg-4 col-md-4">
                  <div class="card">
                    <div class="card-body" v-if="mapPinSelection =='pickup'">
                      <h5>Pickup Information</h5>
                      <form>
                        
                        <div class="input-group form-group">
                          <div class="col-12 p-0 m-0">
                            <label for="name" class="mb-0">Name</label>
                          </div>
                          <input type="text" class="form-control" placeholder="Pickup eg. Junie Delacruz" v-model="pickup.name"aria-describedby="basic-addon2">
                          <div class="input-group-append">
                            <span class="input-group-text" id="basic-addon2">
                              <a href="javascript:void(0)" class="">
                                <i class="icofont-search-user"></i>
                              </a>
                            </span>
                          </div>
                        </div>

                        <div class="form-group">
                          <label for="name" class="mb-0">Mobile Number</label>
                          <input type="text" class="form-control" placeholder="eg. 09162396547" v-model="pickup.mobile">
                        </div>
                         <div class="form-group">
                          <a href="javascript:void(0)" v-on:click="mapDisplay" class="btn btn-block btn-primary btn-sm">Pin Pickup Location</a>
                          <span class="text-muted"><small>This will calculate the delivery rate.</small></span>
                        </div>
                        <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Pickup Address</label>
                          <textarea class="form-control" rows="3" placeholder="Barangay/Street/Room No/Bldg No." v-model="pickup.address" disabled></textarea>
                        </div>
                         <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Landmark</label>
                          <textarea class="form-control" rows="2" placeholder="" v-model="pickup.landmark" disabled></textarea>
                        </div>

                         <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Item Description</label>
                          <textarea class="form-control"  rows="2" placeholder="eg. Charger, Laptop, Documents or Cake, Milk Tea" v-model="pickup.item_description"></textarea>
                        </div>
                        <a href="javscript:void(0)" class="btn btn-block btn-pahatud" v-on:click="next">NEXT</a>
                      </form>
                    </div>

                     <div class="card-body" v-if="mapPinSelection =='dropoff'">
                      <h5>Receiver Information</h5>
                      <form>
                          <div class="input-group form-group">
                          <div class="col-12 p-0 m-0">
                            <label for="name" class="mb-0">Name</label>
                          </div>
                          <input type="text" class="form-control" placeholder="Drop-off eg. Junie Delacruz" v-model="dropoff.name"aria-describedby="basic-addon2">
                          <div class="input-group-append">
                            <span class="input-group-text" id="basic-addon2">
                              <a href="javascript:void(0)" class="">
                                <i class="icofont-search-user"></i>
                              </a>
                            </span>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="name" class="mb-0">Mobile Number</label>
                          <input type="tezt" class="form-control" placeholder="eg. 09162396547" v-model="dropoff.mobile">
                        </div>
                         <div class="form-group">
                          <a href="javascript:void(0)" v-on:click="mapDisplay" class="btn btn-block btn-primary btn-sm">Pin Drop-off Location</a>
                          <span class="text-muted">This will calculate the delivery rate</span>
                        </div>
                        <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Drop-off Address</label>
                          <textarea class="form-control" rows="3" placeholder="Barangay/Street/Room No/Bldg No." v-model="dropoff.address" disabled=""></textarea>
                        </div>
                         <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Landmark</label>
                          <textarea class="form-control" id="landmark" rows="2" placeholder="" v-model="dropoff.landmark" disabled=""></textarea>
                        </div>
                          <a href="javscript:void(0)" class="mb-2" v-on:click="back">Back Pickup</a>

                        <a href="javscript:void(0)" class="btn btn-block btn-pahatud" v-on:click="summary" >PROCEED SUMMARY</a>
                      </form>
                    </div>

                     <div class="card-body" v-if="mapPinSelection =='summary'">
                      <h5>Customer Informaton</h5>


                    <div class="card bg-light mb-3">
                        <div class="card-header">Pickup Information</div>
                        <div class="card-body">
                          <p class="card-text">
                              <address>
                              <strong>{{ pickup.name }}</strong><br>
                              Mobile: {{ pickup.mobile }}<br>
                              Address: {{ pickup.address }} <br>
                              Landmark: {{ pickup.landmark }}<br>
                              </address>
                              <br>
                              <strong>Item Description: {{ pickup.item_description }}</strong>
                          </p>
                        </div>
                      </div>

                       <div class="card bg-light mb-3">
                        <div class="card-header">Drop-off Information</div>
                        <div class="card-body">
                          <p class="card-text">
                              <address>
                              <strong>{{ dropoff.name }}</strong><br>
                              Mobile: {{ dropoff.mobile }}<br>
                              Address: {{ dropoff.address }} <br>
                              Landmark: {{ dropoff.landmark }}<br>
                              </address>
                          </p>
                        </div>
                      </div>

                       <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Payment Option</label>
                          <select class="form-control">
                              <option value="">Item is Paid</option>
                              <option value="">COD - Cash on Delivery</option>
                          </select>
                        </div>

                        <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Amount</label>
                          <input type="text" class="form-control" placeholder="">
                          <strong><small>If COD please input the amount</small></strong>
                        </div>


                         <div class="form-group">
                          <label for="exampleFormControlTextarea1" class="mb-0">Delivery Fee </label>
                          <select class="form-control">
                              <option value="">Pickup will pay the Delivery Fee</option>
                              <option value="">Receiver will pay the Delivery Fee</option>
                          </select>
                        </div>

                      <a href="javscript:void(0)" class="btn btn-block btn-pahatud" v-on:click="summary" >PROCEED SUMMARY</a>

                    </div>


                  </div>
              </div>
              <div class="col-lg-8 col-md-8 ">
                  <div class="card">
                    <div class="card-body">
                       <div class="form-group">
                          <label for="name">Location</label>
                          <input type="tezt" class="form-control" id="name" placeholder="eg. Junie Delacruz">
                        </div>
                    </div>
                  </div>
              </div>

            </div>
            </div>
        </div>

          <div id="displayMapModal" class="modal fade">
          <div class="modal-dialog modal-confirm">
            <div class="modal-content">
              <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              </div>
              <div class="modal-body text-center">
                  <h6 class="title" v-if="mapPinSelection == 'pickup'">Set Pickup Location</h6>
                  <h6 class="title" v-if="mapPinSelection == 'dropoff'">Set Drop-off Location</h6>
                  <div id="map">
                      <gmap-map
                        :center="coordinates"
                        :zoom="zoom"
                        style="width:100%; height: 400px;"
                        ref="mapRef"
                        @dragend="handleDrag"
                        >
                         <GmapMarker
                          :position="mapCoordinates"
                          :clickable="false"
                          :draggable="true" />
                      </gmap-map>
                      <div class="form-group text-left mt-4">
                        <label for="exampleFormControlTextarea1"  v-if="mapPinSelection == 'pickup'">Pickup Address</label>
                        <label for="exampleFormControlTextarea1"  v-if="mapPinSelection == 'dropoff'">Drop-off Address</label>
                        <textarea class="form-control" rows="3" placeholder="Barangay/Street/Room No/Bldg No." v-model="fields.address"></textarea>
                      </div>
                       <div class="form-group text-left">
                        <label for="exampleFormControlTextarea1">Landmark</label>
                        <textarea class="form-control" id="landmark" rows="2" placeholder="" v-model="fields.landmark"></textarea>
                      </div>
                  </div>
                  <button class="btn btn-pahatu btn-block mt-4" v-on:click="setPin"><span>SET PIN</span> </button>
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
              map: null,
              data: {},
              coordinates: {
                lat: 0,
                lng: 0,
              },
              zoom: 13,
              pickup: {
                name: "",
                mobile: "",
                address: "",
                landmark: "",
                coodinates: {
                  lat: 0,
                  lng: 0,
                },
                item_description: "",
              },
              dropoff: {
                name: "",
                mobile: "",
                address: "",
                landmark: "",
                coodinates: {
                  lat: 0,
                  lng: 0,
                }
              },
              fields: {
                address: "",
                landmark: "",
                coordinates: {
                  lat: 0,
                  lng: 0,
                }
              },
              mapPinSelection: 'pickup',
          }
        },
       
        created() {

          if (localStorage.center) {
            this.coordinates = JSON.parse(localStorage.center);
          }
          else {
            this.$getLocation({})
              .then(coordinates => {
                this.coordinates = coordinates;
              })
              .catch(error => console.log(error));
          }
          if (localStorage.zoom) {
            this.zoom = parseInt(localStorage.zoom);
          }

        },
        mounted() {
           this.$refs.mapRef.$mapPromise.then((map) => {
              this.map = map;
          });
        },
        computed: {
            mapCoordinates() {
              if (!this.map) {
                return {
                  lat: 0,
                  lng: 0
                }
              }
              return {
                lat: this.map.center.lat(),
                lng: this.map.center.lng(),
              }
            }
        },
        methods: {
            next: function() {
                
                var isNext = true;

                if (this.pickup.name =="") {
                  isNext = false;
                }
                else if (this.pickup.mobile =="") {
                  isNext = false;
                }
                else if (this.pickup.address =="") {
                  isNext = false;
                }
                // else if (!this.pickup.coordinates.lat =="") {
                //   isNext = false;
                // }
                // else if (!this.pickup.coordinates.lng =="") {
                //   isNext = false;
                // }
                if (isNext) {
                  this.mapPinSelection = 'dropoff';  
                }
            },
            back: function() {
              this.mapPinSelection = 'pickup';
            },
            summary: function() {
              this.mapPinSelection = 'summary';
            },
            mapDisplay: function() {

              if (this.mapPinSelection == 'pickup') {
                  this.fields.address = this.pickup.address;
                  this.fields.landmark =  this.pickup.landmark;
              }
              else {
                  this.fields.address = this.dropoff.address;
                  this.fields.landmark =  this.dropoff.landmark;
              }
              $('#displayMapModal').modal();

            },
            validate: function() {
            },
            openMap: function() {
               console.log('open Map');
            },
            handleDrag: function() {
              let center = {
                lat: this.map.center.lat(),
                lng: this.map.center.lng(),
              }
              let zoom = this.map.getZoom();
              localStorage.center = JSON.stringify(center);
              localStorage.zoom = zoom;
               if (this.mapPinSelection == 'pickup') {
                 this.pickup.coodinates.lat = this.map.center.lat();
                 this.pickup.coodinates.lng = this.map.center.lng();
               }
               else if (this.mapPinSelection == 'dropoff') {
                this.dropoff.coodinates.lat = this.map.center.lat();
                this.dropoff.coodinates.lng = this.map.center.lng();
               }
            },
            setPin: function() {

               if (this.mapPinSelection == 'pickup') {
                 this.pickup.coodinates.lat = this.map.center.lat();
                 this.pickup.coodinates.lng = this.map.center.lng();

                 this.pickup.address = this.fields.address;

                 if(this.fields.landmark!="") {
                  this.pickup.landmark = this.fields.landmark; 
                 }
                 
               }
               else if (this.mapPinSelection == 'dropoff') {
                this.dropoff.coodinates.lat = this.map.center.lat();
                this.dropoff.coodinates.lng = this.map.center.lng();
                this.dropoff.address = this.fields.address;

                if(this.fields.landmark!="") {
                  this.dropoff.landmark = this.fields.landmark; 
                 }
               }


                $('#displayMapModal').modal('hide');

            }
        }
    }

</script>

