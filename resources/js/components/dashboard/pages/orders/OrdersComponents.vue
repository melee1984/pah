<template>
  <div>
     <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
          <h3 class="card-title">
            <i class="fas fa-chart-pie mr-1"></i>
            Orders
          </h3>
        </div> 
         <div class="col-md-6 text-right">
            Reload {{ timerInterval }}
         </div>  
        </div> 
      </div><!-- /.card-header -->
      <div class="card-body">
        <div class="tab-content p-0">
          <!-- Morris chart - Sales -->
          <div class="chart tab-pane active" id="revenue-chart">
              <table class="table table-border">
                <thead>
                  <tr>
                    <th>Date/Time</th>
                    <th>Order Information</th>
                    <th>Qty</th>
                    <th nowrap="">Sub Total</th>
                    <th n>Discount</th>
                    <th nowrap="">Delivery Fee</th>
                    <th>Total</th>
                    <th>Status</th>
                     <th>Rider</th>
                  </tr> 
                </thead>
                <tbody>
                  <tr v-for="order in orders"  v-bind:class="{ inactive: !order.rider_id}"> 
                    <td width="15%">
                        {{ order.submitted_date }}<br>
                        <a href="javascript:void(0)" class="btn btn-xs btn-danger" v-on:click="displayOrderDetails(order)"><strong>Order # {{ order.cart.order_no }}  </strong></a>
                    </td>
                    <td width="25%" v-if="order.partner">
                        Estimated Date/Time: <br><b> {{ order.cart.delivery_date }} - {{ order.cart.delivery_time }}</b> <br>
                        Merchant: <br>
                        <strong>{{ order.partner.restaurant_name }} </strong> <br>
                        {{ order.cart.partnerlocation.address_1 }}, {{ order.cart.partnerlocation.address_2 }}, <br>{{ order.cart.partnerlocation.mobile }} 
                        
                       
                        <br><br>
                    </td>
                    <td width="5%">{{ order.summary.qty }}</td>
                    <td width="5%">{{ order.summary.sub_total }}</td>
                    <td width="5%">{{ order.summary.discount }} PHP</td>
                    <td width="8%">{{ order.summary.delivery_fee }} PHP</td>
                    <td width="10%">{{ order.summary.total }} PHP</td>
                    <td width="10%">
                      <span v-if="order.status">
                        <a href="javascript:void(0)" class="btn btn-xs btn-danger">{{ order.status.title }}</a>
                       </span>
                    </td>
                    <td width="10%" v-if="order.status">
                        <span v-if="order.status.id!=4">
                          <select class="form-control" v-model="order.rider_id" style="font-size:12px;" id="optRider" @change="updateRider(order.id)">
                            <option value="0">Select Rider</option>
                            <option v-for="rider in riders" :value="rider.id">{{ rider.name }}</option>
                          </select>
                        </span>
                        <span v-if="order.status.id==4" >
                            <p v-if="order.rider">{{ order.rider.name }}</p>
                        </span>
                    </td>
                  </tr>
                </tbody>
              </table> 
           </div>
        </div>
      </div><!-- /.card-body -->
    </div>

     <div v-if="selectedOrder" class="modal fade" id="orderDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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
                                        <!-- col-lg-6 start here -->
                                        <div class="invoice-logo" v-if="selectedOrder.partner">
                                          <img width="100" :src="'/uploads/user/'+selectedOrder.partner.id+'/'+selectedOrder.partner.img" alt="Invoice logo">
                                        </div>
                                    </div>
                                    <!-- col-lg-6 end here -->
                                    <div class="col-lg-6">
                                        <!-- col-lg-6 start here -->
                                        <div class="invoice-from">
                                            <ul class="list-unstyled text-right"v-if="selectedOrder.partner">
                                                <li>{{ selectedOrder.partner.restaurant_name }}</li>
                                                <li v-if="selectedOrder.cart.partner_location_address_id">
                                                  <p >{{ selectedOrder.cart.partnerlocation.address_1 }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.address_2 }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.mobile }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.telephone }}</p>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!-- col-lg-6 end here -->
                                    <div class="col-lg-12">
                                        Estimated Date/Time: <br><b> {{ selectedOrder.cart.delivery_date }} - {{ selectedOrder.cart.delivery_time }}</b> <br><br>

                                        Merchant: <br>
                                        <strong>{{ selectedOrder.partner.restaurant_name }} </strong> <br>
                                        {{ selectedOrder.cart.partnerlocation.address_1 }}, {{ selectedOrder.cart.partnerlocation.address_2 }}, <br>{{ selectedOrder.cart.partnerlocation.mobile }} 
                                        <br>
                                        <br>
                                        <p><b>Details</b></p>
                                        <table class="table">
                                           <tr v-for="item in selectedOrder.cart.details">
                                            <td>
                                              {{ item.item.title }}
                                               <span v-for="it in item.variance_content">
                                                  <small>+ {{ it.title }}</small> <br>
                                              </span>
                                              <p v-if="item.instruction">
                                               Note: {{ item.instruction }}
                                               </p>
                                            </td>
                                            <td class="text-center">
                                              {{ item.qty }}
                                            </td>
                                            <td class="text-center">{{ item.price }} PHP</td>
                                        </tr>
                                         </table>
                                         <br>

                                          <p><b>Customer:</b> <br>

                                          {{ selectedOrder.cart.fullname }} <br>
                                          <span v-if="selectedOrder.cart.address">Address: {{ selectedOrder.cart.address.address_1 }}</span> <br>
                                          <span v-if="selectedOrder.cart.mobile">Mobile: {{ selectedOrder.cart.mobile }}</span>
                                        </p>

                                        <p> 
                                            <b>Summary:</b> <br>
                                            Sub Total: {{ selectedOrder.summary.sub_total }} <br>
                                            Delivery Fee: {{ selectedOrder.summary.delivery_fee }} <br>
                                            Discount: {{ selectedOrder.summary.discount }} <br>
                                          Total: {{ selectedOrder.summary.total }} <br>
                                        </p>

                                        <div class="invoice-footer mt25">
                                              <label for="">Delivery Status</label>
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
                orders: {},
                timerInterval: 10,
                riders: {},
                selectedOrder: {},
                statuses: {},
            }
        },
        mounted() {
            console.log('Mounted Order List View Component')
              this.fetchData();
              this.selectedOrder = this.orders[0];  
             this.startTimer();
        },
        
        methods: {
          startTimer: function () {
           setInterval(() => {
                this.timerInterval--;
                if (this.timerInterval ==0) {
                  this.timerInterval = 10;
                  toastr.info("Refreshing...");
                  this.fetchData();
                  Event.$emit('reloadDashboardOrderSummary');
                }
           }, 1000)
          },
          fetchData: function() {
              var self = this;
              axios.get('/api/dashboard/order/list?api_token='+api_token).then(function (response) {
                self.orders = response.data.orders;
                self.riders = response.data.riders;
                self.statuses = response.data.statuses;
                
              }).catch(function (error) {
                  console.log(error);
              });
          },
          updateRider:function(orderid) {

             let formData = new FormData();
                formData.append('rider_id', $('#optRider').val())

                axios.post('/api/data/dashboard/update/'+orderid+'/rider/submit?api_token='+api_token, formData).then((response) => {
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
                formData.append('cart_id', this.selectedOrder.cart_id);
                formData.append('status_id', event.target.value);

                axios.post('/api/data/dashboard/update/'+this.selectedOrder.id+'/status/submit?api_token='+api_token, formData).then((response) => {
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
              $('#orderDetails').modal('toggle');
          }
          
        }
    }

</script>

