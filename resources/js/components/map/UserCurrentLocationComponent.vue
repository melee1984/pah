<template>
  <section>
    <!-- Modal HTML -->
    <div id="pinLocation" class="modal fade">
      <div class="modal-dialog modal-confirm modal-lg">
        <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title">Let us know your Location</h5>
              <a v-on:click="close">
                <span aria-hidden="true">&times;</span>
              </a>
          </div>
          <div class="modal-body text-center">
                <div class="input-group p-0 mb-2">
                        <gmap-autocomplete
                        id="inputAuto"
                         ref="mapRef"
                        @input="value = $event.target.value"
                        placeholder="Enter your Location"
                        class="form-control"
                        aria-label="Username" 
                        aria-describedby="basic-addon1"
                         @keyup.enter="usePlace" @place_changed="setPlace" 
                         style="z-index: 9999;"
                         :options="{fields: ['geometry']}"
                        ></gmap-autocomplete>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">
                            <a href="javascript:void(0)" v-on:click="getCurrentLocation"><i class="icofont-location-pin"></i></a>
                          </span>

                        </div>
                    </div>
                  <div id="map">
                      <gmap-map
                        :center="coordinates"
                        :zoom="zoom"
                        style="width:100%; height: 400px;"
                        ref="mapRef"
                        @dragend="handleDrag"
                        :options="{
                           zoomControl: true,
                           mapTypeControl: false,
                           scaleControl: false,
                           streetViewControl: false,
                           rotateControl: false,
                           fullscreenControl: true,
                           disableDefaultUi: false
                         }"
                        >
                          <GmapMarker
                          :position="mapCoordinates"
                          :clickable="false"
                          :draggable="true" 
                          />
                      </gmap-map>
                     <!--  <div class="form-group text-left mt-2 mb-1 mb-0">
                        <textarea class="form-control" rows="3" placeholder="Barangay/Street/Room No/Bldg No." v-model="fields.address" v-on:change="validateFields"></textarea>
                      </div>
                      <div class="form-group text-left">
                        <label for="exampleFormControlTextarea1" class="mb-1"><small>Notes</small></label>
                        <textarea class="form-control" id="landmark" rows="2" placeholder="" v-model="fields.notes" v-on:change="validateFields"></textarea>
                        <span class="text-muted"><small><i>You may address any landmark or notes to locate easily your delivery address</i></small></span>
                      </div> -->
                  </div>

                  <button class="btn btn-pahatud btn-block mt-4 " v-on:click="usePlace" v-if="PinEnabled"><span>Pin Location</span> </button>
                  <button class="btn  btn-block mt-4" v-if="!PinEnabled" disabled=""><span>Pin Location</span> </button>
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
          position: null,
          map: null,
          zoom: 10,
          autocomplete: null,
          center: {
            lat: 10.0,
            lng: 10.0,
          },
          coordinates: {
            lat: 7.0816953475528255,
            lng: 125.5778156310179,
          },
          fields: {
            title: "",
            address: "",
            notes: "",
            coordinates: {
              lat: 0,
              lng: 0,
            }
          },
          value: "demo",
         markers: [{
              position: {lat: 11.0, lng: 11.0}
          }],
          place: null,
          PinEnabled: false,
          modalIsPop: false,
        }
      },
      created() {

        if (!localStorage.center) {
          this.getCurrentLocation();
        }

        if (localStorage.center) {
          this.coordinates = JSON.parse(localStorage.center);
        }
        if (localStorage.zoom) {
          this.zoom = parseInt(localStorage.zoom);
        }
        if (localStorage.address) {
          this.fields.address = localStorage.address;
        }
        if (localStorage.notes) {
          this.fields.notes = localStorage.notes;
        }

        Event.$on('PinMapUserLocation', () => {
          $('#pinLocation').modal();
        });

        Event.$on('CheckUserLocation', () => {
          this.checkUserLocationAddress();
        });

        Event.$on('updateUserCartCoordinate', () => {
          this.updateUserCartLocation();
        });

        // Not sure if I will add this on the line // 

        this.validateFields();

      },
      mounted() {
        
          this.$refs.mapRef.$mapPromise.then((map) => {
              this.map = map;
          });

          this.updateUserCartLocation();
          
      },
      computed: {
        mapCoordinates() {
          if (!this.map) {
            return {
              lat: 7.0816953475528255,
              lng: 125.5778156310179,
            }
          }
          return {
            lat: this.map.center.lat(),
            lng: this.map.center.lng(),
          }
        }
      },
      methods: {
          getCurrentLocation: function() {
            this.$getLocation({})
              .then(coordinates => {
                let center = {
                  lat: coordinates.lat,
                  lng: coordinates.lng,
                }
                localStorage.center = JSON.stringify(center);
              })
              .catch(error => console.log(error));
          },
          getAddressData: function (addressData, placeResultData, id) {
              console.log("Samke --  " + addressData);
          },
          mapDisplay: function() {
            
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
            // localStorage.address = this.fields.address;
            // localStorage.notes = this.fields.notes;
            this.validateFields();
            this.updateUserCartLocation();

          },
          setPin: function() {
              localStorage.address = $('#inputAuto').val();
              Event.$emit('updateLocationAddress');
              
              this.updateUserCartLocation();
              $('#pinLocation').modal('hide');
          },
          setPlace(place) {
              this.place = place;
              this.coordinates.lat = this.place.geometry.location.lat();
              this.coordinates.lng = this.place.geometry.location.lng();

              Event.$emit('updateLocationAddress');
              this.updateUserCartLocation();

           },
           updateUserCartLocation: function() {
            
            if (!localStorage.center) {
              return;
            }
            this.coordinates = JSON.parse(localStorage.center);
            if ((this.coordinates.lat!="") && (this.coordinates.lng!=""))  {
                let formData = new FormData();

                formData.append('lat', this.coordinates.lat);
                formData.append('long', this.coordinates.lng);

                 axios.post('/api/location/submit?api_token='+api_token, formData).then((response) => {
                  Event.$emit('reloadSummary');
                  Event.$emit('reloadRestaurants');
                }).catch((errors) => {
                  toastr.error(errors);
                }); 
            }

          },
          validateFields: function() {
            let isValidated = true;

            if (this.mapCoordinates.lat =="") {
              isValidated = false;
            }
            else if (this.mapCoordinates.lng =="") {
              isValidated = false;
            }
            if (isValidated) {
              this.PinEnabled = true;
            }
          },
           usePlace(place) {

            if (this.place) {
              this.markers.push({
                position: {
                  lat: this.place.geometry.location.lat(),
                  lng: this.place.geometry.location.lng(),
                }
              })
              let center = {
                lat: this.place.geometry.location.lat(),
                lng: this.place.geometry.location.lng(),
              }
              let zoom = this.map.getZoom();
              localStorage.center = JSON.stringify(center);
              localStorage.zoom = zoom;
              this.place = null;


            }
            this.setPin();
          },
          close: function() {
              this.modalIsPop = false;
              $('#pinLocation').modal('hide');
          },
          checkUserLocationAddress: function() {
              let popupUserLocation = false;
              if ((localStorage.center) || (localStorage.address)) {
                  popupUserLocation = true;
              }
              this.validateFields();
              if (!popupUserLocation) {
                $('#pinLocation').modal();
              }
              else {
               
              }
          },
          
      }

    }
</script>

