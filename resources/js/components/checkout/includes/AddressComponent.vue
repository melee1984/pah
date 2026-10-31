<template>
<div class="row">
  <div class="col-md-12" id="delivery_address">
  	<div class="row">
  		<div class="col-8 col-sm-9">
  		  <h4 class="mt-3">Delivery Address</h4>
  		</div>
  		<div class="col-4 col-sm-3 text-right">
  		    <a href="javascript:void(0)" v-on:click="actionAddress('add')" class="mt-3 text-danger action-link" v-if="addressAction=='view'">Add Address</a>
  		    <a href="javascript:void(0)" v-on:click="actionAddress('view')" class="mt-3 text-danger action-link" v-if="addressAction!='view'">Cancel</a>
  		</div>
  	</div>

    <!-- address --> 

     <div v-if="addressAction === 'view'">
    <ul class="list-group">
      <li 
        v-for="(address, index) in addresses" 
        :key="address.id"
        class="list-group-item d-flex justify-content-between align-items-start"
        :class="{ active: address.id === selectedaddressid }"
      >
        <!-- Left: Address Info -->
        <div @click="selectAddress(address)" class="flex-grow-1">
          <h5 class="mb-1">{{ address.title }}</h5>
          <p class="mb-1">{{ address.address_1 }}</p>
          <small v-if="address.city"><strong>City:</strong> {{ address.city.name }}</small><br v-if="address.city">
          <small v-if="address.city && !address.city.active" class="text-danger">
            Not currently serviceable
          </small><br v-if="address.city && !address.city.active">
          <small><strong>Note:</strong> {{ address.landmark }}</small>
        </div>

        <!-- Right: Actions -->
        <div class="ms-3 text-end">
          <a href="javascript:void(0)" @click.stop="editAddress(address)" class="action-link me-2">
            <span class="material-icons">edit</span>
          </a>
          <a href="javascript:void(0)" @click.stop="deleteAddress(address.id, index)" class="action-link">
            <span class="material-icons">delete</span>
          </a>
        </div>
      </li>
    </ul>

    <!-- Warning if no address selected -->
    <div class="text-checkout-danger mt-2" v-if="selectedaddressid == null">
      Note: Please select a delivery address
    </div>
  </div>

    
   </div>

   <div class="col-md-12 col-sm-12" v-if="addressAction!='view'">
     <form>
      <div class="form-row">
        <div class="form-group col-md-12 mb-1 pb-1">
          <input type="text" class="form-control" name="txtTitle"  id="txtTitle" placeholder="eg. Home, Office" v-model="address.title">
        </div>
        <div class="form-group col-md-12 mb-1 pb-1">
          <a href="javascript:void(0)" v-on:click="popMap" class="mapPin">
            <span class="material-icons">map</span> <span>Pin location</span>
          </a>
        </div>
         <div class="form-group col-md-12 mb-0 pb-0">
          <label for="" class="text-checkout-danger">Complete Delivery Address</label>
        </div>
        <div class="form-group col-md-12 mb-1 pb-1">
          <input type="text" class="form-control" name="txtAddress"  id="txtAddress" placeholder="Delivery Address eg. Barangay, Street, lot, building no , room no " v-model="address.address_1">
        </div>
        <div class="form-group col-md-12 mb-1 pb-1">
          <select class="form-control" name="city" id="city" v-model="address.city_name">
            <option value="" disabled>Select city</option>
            <option v-for="city in cities" :key="city.id" :value="city.name">
              {{ city.name }}{{ city.active ? '' : ' — Not currently serviceable' }}
            </option>
          </select>
        </div>
        <div class="form-group col-md-12 mb-1 pb-1">
          <textarea class="form-control" name="specialInstruction" id="specialInstruction" rows="2" placeholder="Special Instruction eg. building, landmark, or you dont have change for 1000" v-model="address.landmark"></textarea>
        </div>
      </div>


      <button type="button" class="btn btn-pahatud btn-block btn-sm" v-bind:class="{ disabled: !validForm }" v-on:click="submit">{{ buttonString }}</button>
        
    </form>
  </div>

	</div>
</div>
</template>

<script>
    export default {
        data() {
          return {
              data: {},
              address: {},
              selectedAddress: {},
              delivery_time: {},
              addressAction: 'view',
              addressList: this.addresses,
              selectedId: "",
              buttonString: "Add Address",
              cities: [],
          }
        },
        props: ['addresses', 'selectedaddressid'],
        mounted() {
          this.fetchData();
          this.fetchCities();
          this.validateSelectedAddress();
        },
        created() {
          // window.AppEvents.$on('updateSelectedAddress', (p_selectedId) => {
          //   console.log('This is the moment');
          //    this.selectedId = p_selectedId
          // });
        },
        computed: {
          validForm: function(){
            const coordinates = localStorage.center ? JSON.parse(localStorage.center) : null;

            if (!this.address.title) {
              return false;
            }
            else if (!this.address.address_1) {
              return false;
            }
            else if (!this.address.city_name) {
              return false;
            }
            else if (!this.address.landmark) {
              return false;
            }
            else if (!coordinates || coordinates.lat === "") {
              return false;
            }
            else if (coordinates.lng === "") {
              return false;
            }
            return true;
          }
        },
        methods: {
            popMap: function() {
              window.AppEvents.$emit('PinMapUserLocation');
            },  
            fetchCities: function() {
              axios.get('/api/address-cities').then((response) => {
                this.cities = response.data.cities || [];
              }).catch((error) => {
                console.log(error);
              });
            },
            actionAddress: function(action) {
              this.address = {};
              this.addressAction = action;
            },
            fetchData: function() {
              var self = this;
              axios.get('/api/checkout/address?api_token='+api_token).then(function (response) {
                self.selectedId = response.data.selectedid;
                this.buttonString = "Add Address";

                if(!self.selectedId) {
                  self.addressAction = "add";
                }
                else  {
                  self.addressAction == "view";
                }

              })
              .catch(function (error) {
                  console.log(error);
              });
            },
            selectAddress: function(address) {
              if (!address.city || !address.city.active) {
                toastr.error('This address is outside our currently active service cities.');
                return;
              }
            
              $('#delivery_address .card').removeClass('border-danger active');
              $('#address_'+address.id).addClass('border-danger active');
            
               let formData = new FormData();
                
                formData.append('address', address.id)
                axios.post('/api/checkout/address/update/submit?api_token='+api_token, formData).then((response) => {
                  if (response.data.status) {

                    
                    this.$emit('onUpdateCartAddress', response.data.addresses.id);
                    toastr.success(response.data.message);
                  }
                  else {
                    toastr.error(response.data.message);
                  }
                }).catch((errors) => {
                    toastr.error(errors);
                }); 

            },
            validate: function() {
             
            },
            editAddress: function(address) {
              this.addressAction = "edit";
              this.buttonString = "Update Address";
              this.address = address;
              this.$set(this.address, 'city_name', address.city ? address.city.name : '');
            },
            addAddress: function() {
               
            },
            validateSelectedAddress: function() {
              
            },
            submit: function() {

              let formData = new FormData();
              let url = "";

              if (localStorage.center) {
                var coordinates = JSON.parse(localStorage.center);
              }

              if (this.validate()) {

                  if (this.addressAction == "add") {

                    formData.append('title', this.address.title);
                    formData.append('address', this.address.address_1);
                    formData.append('city', this.address.city_name);
                    formData.append('landmark', this.address.landmark);
                    formData.append('latitude', coordinates.lat);
                    formData.append('longtitude', coordinates.lng);

                    url = "/api/checkout/address/add/submit?api_token="+api_token;
                  }
                  else if(this.addressAction == "edit") {

                    formData.append('title', this.address.title);
                    formData.append('address', this.address.address_1);
                    formData.append('city', this.address.city_name);
                    formData.append('landmark', this.address.landmark);
                    formData.append('latitude', coordinates.lat);
                    formData.append('longtitude', coordinates.lng);
                    formData.append('id', this.address.id)

                    url = "/api/checkout/address/update/"+ this.address.id +"/submit?api_token="+api_token;
                  }

                  axios.post(url, formData).then((response) => {
                    if (response.data.status) {
                        toastr.success(response.data.message);
                        this.$emit('onUpdateCustomer', response.data.addresses);
                        if (response.data.serviceable !== false) {
                          this.$emit('updateCartAddress', response.data.address_id);
                          this.$emit('onUpdateCartAddress', response.data.address_id);
                        }
                        this.addressAction = "view";
                    }
                    else {
                      toastr.error(response.data.message);
                    }
                  }).catch((errors) => {
                      toastr.error(errors);
                  });
              }

            },
            validate: function () {
              var isValid = true;
              const coordinates = localStorage.center ? JSON.parse(localStorage.center) : null;
              var messageString = "";

              if (!this.address.title) {
                messageString = "title";
                isValid = false;
              }
              else if (!this.address.address_1) {
                messageString = "address";
                isValid = false;
              }
              else if (!this.address.city_name) {
                messageString = "city";
                isValid = false;
              }
              else if (!this.address.landmark) {
                messageString = "landmark";
                isValid = false;
              }
              else if (!coordinates || coordinates.lat === "") {
                messageString = "location";
                isValid = false;
              }
              else if (coordinates.lng === "") {
                 messageString = "location";
                isValid = false;
              }
              if (!isValid) {
                 toastr.error("Sorry, missing information '"+ messageString+"' is required.");
              }
              return isValid;

            },
            deleteAddress: function(address_id, index) {

               let formData = new FormData();
                formData.append('id', address_id)
                axios.post('/api/checkout/address/delete/submit?api_token='+api_token, formData).then((response) => {
                  if (response.data.status) {

                      $('#main_'+address_id).remove();
                      this.$emit('onDelete', index);
                      toastr.success(response.data.message);

                  }
                  else {
                    toastr.error(response.data.message);
                  }
                }).catch((errors) => {
                    toastr.error(errors);
                }); 
            },


        }
    }

</script>


