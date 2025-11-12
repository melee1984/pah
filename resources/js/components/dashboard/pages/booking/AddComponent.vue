<template>
  <div class="row">
     <div class="col-md-6">
       <div class="card">
          <div class="card-header">
            <div class="row">
              <div class="col-md-6">
              <h3 class="card-title">
                <i class="fas fa-chart-pie mr-1"></i>
                New Booking 
              </h3>
            </div> 
             <div class="col-md-6 text-right"></div>  
            </div> 
          </div><!-- /.card-header -->
          <div class="card-body">
             <label>Pick-up Address:</label>
            <div class="form-group">
              <select class="form-control"  v-model="fieldBooking.pickup_id" >
                <option>Select Pickup Address</option>
                <option v-for="address in pickup" :value="address.id">{{ address.name }} - {{ address.address }} - {{ address.mobile }}</option>
              </select>
              <a href="javascript:void(0)" v-on:click="addAddress(1)" class="btn btn-xs btn-primary">Add new Pickup Address</a>
            </div>
            <div class="form-group">
              <label>Drop-off Address:</label>
              <select class="form-control" v-model="fieldBooking.dropoff_id">
                <option>Select Drop-off Address</option>
                 <option  v-for="address in dropoff" :value="address.id">{{ address.name }} - {{ address.address }} - {{ address.mobile }}</option>
              </select>
              <a href="javascript:void(0)" v-on:click=" addAddress(2)" class="btn btn-xs btn-primary">Add new Drop-off Address</a>
            </div>
            <hr>
             <div class="form-group">
                <label>Date:</label>
                <div class="input-group date" id="reservationdate" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#reservationdate" id="dateselected" name="dateselected">
                    <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                </div>
            </div>
            <div class="form-group">
               <label>Pickup Time:</label>
              <input class="form-control" placeholder="Pickup time eg. 02:30 AM" v-model="fieldBooking.timeselected"> 
            </div>
            <div class="form-group">
              <label>Delivery Fee:</label>
              <input type="number" class="form-control" placeholder="Delivery Fee" v-model="fieldBooking.delivery_fee"> 
            </div>
            <div class="form-group">
              <label>Payment Type:</label>
              <input class="form-control" placeholder="Payment eg. Gcash/Cash" value="Cash" v-model="fieldBooking.payment"> 
            </div>
            <div class="form-group">
              <label>Item Information:</label>
              <input class="form-control" placeholder="Item Information" v-model="fieldBooking.item_info"> 
            </div>
            <div class="form-group">
              <label>COD:</label>
              <input type="number" class="form-control" placeholder="COD" v-model="fieldBooking.cod"> 
            </div>
            <div class="form-group">
              <label>Instruction:</label>
              <input class="form-control" placeholder="Instruction" v-model="fieldBooking.instruction"> 
            </div>
            <a href="javascript:void(0)" v-on:click="submitBooking()" class="btn btn-primary">Submit</a>
          </div><!-- /.card-body -->
        </div>
     </div>

    <div class="col-md-6" v-if="formDisplay">
      <div class="card">
           <div class="card-header">
              {{ addressLabel }}
           </div>

           <div class="card-body">
              <form>
                <div class="form-group">
                  <label for="exampleInputEmail1">Name</label>
                  <input type="text" class="form-control"  placeholder="Enter customer name" v-model="field.name">
                </div>
                <div class="form-group">
                  <label for="exampleInputEmail1">Mobile</label>
                  <input type="text" class="form-control" placeholder="Enter customer mobile" v-model="field.mobile">
                </div>
                <div class="form-group">
                  <label for="exampleInputEmail1">Address</label>
                  <input type="text" class="form-control" a placeholder="Enter customer address" v-model="field.address">
                </div>

                 <div class="form-group">
                  <label for="exampleInputEmail1">Landmark</label>
                  <input type="text" class="form-control"  placeholder="Landmark" v-model="field.landmark">
                </div>
                <button type="button" class="btn btn-primary" v-on:click="submitAddress()">Submit</button>
                <a href="javascript:void(0)" v-on:click="cancelForm()">Cancel</a>
              </form>
           </div>
      </div>
    </div>
  </div>
</template>
<script>
     export default {
       data() {
            return {
                field: {
                  name: "",
                  address: "",
                  mobile: "",
                  landmark: "",
                  option: "",
                },
                fieldBooking: {
                  pickup_id: "",
                  dropoff_id: "",
                  dateselected: "",
                  timeselected: "",
                  delivery_fee: "",
                  payment: "Cash",
                  cod: "",
                  item_info: "",
                  instruction: "",
                },
                errors: {},
                orders: {},
                timerInterval: 10,
                riders: {},
                selectedOrder: {},
                statuses: {},
                addressLabel: "",
                formDisplay: false,
                dropoff: {},
                pickup: {},
            }
        },
        mounted() {
          this.fetchData();
          
        },
        
        methods: {
          fetchData: function() {
              var self = this;
              axios.get('/api/data/dashboard/booking/new?api_token='+api_token).then(function (response) {
                self.dropoff = response.data.dropoff;
                self.pickup = response.data.pickup;
              }).catch(function (error) {
                  console.log(error);
              });
          },
          submitBooking() {
            var self = this;
            let formData = new FormData();
              formData.append('booking_date', $('#dateselected').val())
            formData.append('booking_time', this.fieldBooking.timeselected)
            formData.append('delivery_rate', this.fieldBooking.delivery_fee)
            formData.append('pickup_id', this.fieldBooking.pickup_id)
            formData.append('dropoff_id', this.fieldBooking.dropoff_id)
            formData.append('cod', this.fieldBooking.cod)
            formData.append('item_info', this.fieldBooking.item_info)
            formData.append('instruction', this.fieldBooking.instruction)
            formData.append('payment', this.fieldBooking.payment)
            
            axios.post('/api/data/dashboard/boooking/submit?api_token='+api_token, formData).then(function (response) {
              if (response.status) {
                toastr.success(response.data.message);

                window.location.href = MAINURL+"/data/dashboard";

              }
              else {
                toastr.error(response.data.message);
              }
            }).catch(function (error) {
                toastr.error(error);
            });
          },
          submitAddress() {
            var self = this;
            let formData = new FormData();

            formData.append('name', this.field.name)
            formData.append('address', this.field.address)
            formData.append('mobile', this.field.mobile)
            formData.append('landmark', this.field.landmark)
            formData.append('option', this.field.option)
            
            axios.post('/api/data/dashboard/address/submit?api_token='+api_token, formData).then(function (response) {
              toastr.success(response.data.message);
              self.cancelForm();
              self.fetchData();
            }).catch(function (error) {
                console.log(error);
            });
          },
          displayOrderDetails: function(order) {
              this.selectedOrder = order;
              $('#orderDetails').modal('toggle');
          },
          cancelForm: function() {
            this.formDisplay = false;
            this.field.option =  "";
          },
          addAddress: function(option) {

            this.formDisplay = true;
            this.field.option =  option;

            if (option == 1) {
              this.addressLabel = "Add new Pick-up Address";
            }
            else if (option == 2) {
              this.addressLabel = "Add new Drop-off Address"; 
            }

          }
          
        }
    }

</script>

