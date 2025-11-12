<template>
  <div>
     <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
          <h3 class="card-title">
            <i class="fas fa-chart-pie mr-1"></i>
            Job Order 
          </h3>
        </div> 
         <div class="col-md-6 text-right">
            Reload {{ timerInterval }}
         </div>  
        </div> 
      </div><!-- /.card-header -->

     <div class="col-md-12">
        <div class="card-body">
        <div class="tab-content p-0">
          <!-- Morris chart - Sales -->
          <div class="chart tab-pane active" id="revenue-chart">
              <table class="table table-border">
                <thead>
                  <tr>
                    <th>Date/Time</th>
                    <th>Booking Details</th>
                    <th>COD</th>
                    <th nowrap="">Duration</th>
                    <th>Delivery Fee</th>
                    <th>Status</th>
                     <th>Rider</th>
                  </tr> 
                </thead>
                <tbody>
                  <tr v-if="bookings.length <= 0">
                    <td>No record found</td>
                  </tr>
                  <tr v-for="booking in bookings"  v-bind:class="{ inactive: !booking.rider_id}"> 
                    <td width="10%"> 
                       
                        <a href="javascript:void(0)" class="btn btn-xs btn-danger" v-on:click="displayOrderDetails(booking)"><strong>{{ booking.job_order_format }}  </strong></a>
                    </td>
                    <td width="25%">
                        <p>Pickup Date/Time:<br><b>{{ booking.booking_date_and_time_format }}</b></p>
                    </td>
                    <td width="5%">{{ booking.cod }}</td>
                    <td width="5%">{{ booking.duration }}</td>
                    <td width="5%">{{ booking.delivery_rate_format }} PHP</td>
                    <td width="8%">
                      <span v-if="booking.status">
                        <a href="javascript:void(0)" class="btn btn-xs btn-danger">{{ booking.status.title }}</a>
                       </span>
                    </td>
                    <td width="10%" v-if="booking.status">
                        <span v-if="booking.status.id!=4">
                          <select class="form-control" v-model="booking.rider_id" style="font-size:12px;" @change="updateRider(booking.id)">
                            <option value="null">Select Rider</option>
                            <option v-for="rider in riders" :value="rider.id">{{ rider.name }}</option>
                          </select>
                        </span>
                        <span v-if="booking.status.id==4" >
                            <p v-if="booking.rider">{{ booking.rider.name }}</p>
                        </span>
                    </td>
                  </tr>
                </tbody>
              </table> 
           </div>
        </div>
      </div><!-- /.card-body -->

     </div>
    </div>

     <div v-if="selectedOrder" class="modal fade" id="bookingDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content modal-lg">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLongTitle">Booking Information</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="container bootdey">
                <div class="row invoice row-printable">
                    <div class="col-md-12">
                        <!-- col-lg-12 start here -->
                        <div class="panel panel-default plain" id="dash_0">
                            <!-- Start .panel -->
                            <div class="panel-body p30" v-if="selectedOrder">
                                <div class="row">
                                    <!-- Start .row -->
                                    <div class="col-lg-6">
                                      <br>
                                         <div v-if="selectedOrder.pickup">
                                          <p>Pickup Date/Time:<br><b>{{ selectedOrder.booking_date_and_time_format }}</b></p>
                                          <b>Pickup</b> 
                                            <p>Name: {{ selectedOrder.pickup.name }} <br>
                                            Address: {{ selectedOrder.pickup.address }} <br>
                                            Mobile: {{ selectedOrder.pickup.mobile }} <br></p>
                                          </div> 

                                           <div v-if="selectedOrder.dropoff">
                                            <b>Drop-off</b> 
                                            <p>Name: {{ selectedOrder.dropoff.name }} <br>
                                            Address: {{ selectedOrder.dropoff.address }} <br>
                                            Mobile: {{ selectedOrder.dropoff.mobile }} <br></p>
                                          </div> 

                                          <p>
                                            COD: {{ selectedOrder.cod }} <br>
                                            Info: {{ selectedOrder.item_info }} 
                                            <hr>
                                            Instruction: {{ selectedOrder.instruction }} <br>
                                          </p>
                                    </div>
                                    <!-- col-lg-6 end here -->
                                    <div class="col-lg-12">
                                        <!-- col-lg-12 start here -->
                                        <div class="invoice-footer mt25">
                                              <label for="">Booking Status</label>
                                               <select class="form-control"  id="optStatus" v-if="statuses" v-model="selectedOrder.status_id" @change="updateStatus($event)">
                                                    <option value="0">Select Status</option>
                                                    <option v-for="status in statuses" :value="status.id">{{ status.title }}</option>
                                              </select>
                                              <br>
                                        </div>
                                    </div>
                                    <!-- col-lg-12 end here -->
                                </div>
                                <!-- End .row -->
                            </div>
                        </div>
                        <!-- End .panel -->
                    </div>
                    <!-- col-lg-12 end here -->
                </div>
                </div>
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn" data-dismiss="modal">Close</button>
            </div>
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
                },
                errors: {},
                bookings: {},
                timerInterval: 20,
                riders: {},
                selectedOrder: {},
                statuses: {},
            }
        },
        mounted() {
            this.fetchData();
            // this.selectedOrder = this.bookings[0];  
            this.startTimer();
        },
        
        methods: {
          startTimer: function () {
           setInterval(() => {
                this.timerInterval--;
                if (this.timerInterval ==0) {
                  this.timerInterval = 20;
                  this.fetchData();
                }
           }, 1000)
          },
          fetchData: function() {
              var self = this;
              axios.get('/api/dashboard/booking/list?api_token='+api_token).then(function (response) {

                self.bookings = response.data.bookings;
                self.riders = response.data.riders;
                self.statuses = response.data.statuses;
                
              }).catch(function (error) {
                  console.log(error);
              });
          },
          updateRider:function(orderid) {

             let formData = new FormData();
                formData.append('rider_id', event.target.value)

                axios.post('/api/data/dashboard/booking/update/'+orderid+'/rider/submit?api_token='+api_token, formData).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);
                      this.fetchData();
                  }
                  else {
                    toastr.error(response.data.message);
                  }
                }).catch((errors) => {
                    toastr.error(errors);
                }); 
          },
            updateStatus:function(event) {
              
             let formData = new FormData();
                formData.append('cart_id', this.selectedOrder.id);
                formData.append('status_id', event.target.value);

                axios.post('/api/data/dashboard/booking/update/'+this.selectedOrder.id+'/status/submit?api_token='+api_token, formData).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);

                      this.fetchData();
                  }
                  else {
                    toastr.error(response.data.message);
                  }
                }).catch((errors) => {
                    toastr.error(errors);
                }); 
          },
          displayOrderDetails: function(order) {
              this.selectedOrder = order;
              $('#bookingDetails').modal('toggle');
          }
          
        }
    }

</script>

