<template>
  <div class="col-md-12">
     <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
          <h3 class="card-title">
            <i class="fas fa-chart-pie mr-1"></i>
            Orders Today
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
                    <th>Discount</th>
                    <th nowrap="">Delivery Fee</th>
                    <th>Total</th>
                    <th>Rider</th>
                    <th>Status</th>
                  </tr> 
                </thead>
                <tbody>
                  <tr v-if="orders.length <=0">
                    <td colspan="9">No record found...</td>
                  </tr>
                  <tr v-for="order in orders"  v-bind:class="{ inactive: !order.rider_id}"> 
                    <td width="15%">
                        {{ order.submitted_date }}<br>
                        <a href="javascript:void(0)" class="btn btn-xs btn-danger" v-on:click="displayOrderDetails(order)"><strong>Order # {{ order.cart.order_no }}  </strong></a>
                    </td>
                    <td width="25%">
                        Restaurant: <strong>{{ order.partner.restaurant_name }} </strong> <br> <br>
                        Delivery Date/Time: {{ order.cart.delivery_time }}
                        <br>
                        Customer: {{ order.cart.fullname }} <br>
                        <span v-if="order.cart.address">Address: {{ order.cart.address.address_1 }}</span> <br>
                        Mobile: {{ order.cart.mobile }} <br>
                        
                    </td>
                    <td width="5%">{{ order.summary.qty }}</td>
                    <td width="5%">{{ order.summary.sub_total }}</td>
                    <td width="5%">{{ order.summary.discount }} PHP</td>
                    <td width="8%">{{ order.summary.delivery_fee }} PHP</td>
                    <td width="10%">{{ order.summary.total }} PHP</td>
                     <td width="10%" v-if="order.status">
                        <span v-if="order.status.id!=4">
                          - 
                        </span>
                        <span v-if="order.status.id==4" >
                            <p v-if="order.rider">{{ order.rider.name }}</p>
                        </span>
                    </td>
                    <td width="10%">
                      <span v-if="order.status">
                        <a href="javascript:void(0)" class="btn btn-xs btn-danger" v-if="order.status.id==1">{{ order.status.title }}</a>
                        <a href="javascript:void(0)" class="btn btn-xs btn-success" v-if="order.status.id==2">{{ order.status.title }}</a>
                        <a href="javascript:void(0)" class="btn btn-xs btn-warning" v-if="order.status.id==3">{{ order.status.title }}</a>
                         <a href="javascript:void(0)" class="btn btn-xs btn-secondary" v-if="order.status.id==4">{{ order.status.title }}</a>
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
                            <div class="panel-body p30">
                                <div class="row">
                                    <!-- Start .row -->
                                    <div class="col-lg-6">
                                        <!-- col-lg-6 start here -->
                                        <div class="invoice-logo" v-if="selectedOrder.partner.img">
                                          <img width="100" :src="'/uploads/user/'+selectedOrder.partner.id+'/'+selectedOrder.partner.img" alt="Invoice logo">
                                        </div>
                                    </div>
                                    <!-- col-lg-6 end here -->
                                    <div class="col-lg-6">
                                        <!-- col-lg-6 start here -->
                                        <div class="invoice-from">
                                            <ul class="list-unstyled text-right">
                                                 <li>{{ selectedOrder.partner.restaurant_name }}</li>
                                                <li v-if="selectedOrder.partner">
                                                  {{ selectedOrder.cart.partnerlocation.address_1 }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.address_2 }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.mobile }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.telephone }} <br>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!-- col-lg-6 end here -->
                                    <div class="col-lg-12">
                                        <!-- col-lg-12 start here -->
                                        <div class="invoice-details mt25">
                                            <div class="well">
                                                <ul class="list-unstyled mb0">
                                                    <li><strong>Order #</strong> #{{ selectedOrder.cart.order_no }}</li>
                                                    <li><strong>Date/Time:</strong> {{ selectedOrder.cart.delivery_time }}</li>
                                                    <li><strong>Status:</strong> 
                                                        <span class="label label-danger">{{ selectedOrder.status.title }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="invoice-to mt25">
                                            <ul class="list-unstyled">
                                                <li><strong>Invoiced To</strong></li>
                                                <li>{{ selectedOrder.cart.fullname }}</li>
                                                <li>{{ selectedOrder.cart.address.address_1 }}</li>
                                                <li>{{ selectedOrder.cart.mobile }}</li>
                                            </ul>
                                        </div>
                                        <div class="invoice-items">
                                            <div class="table-responsive" style="overflow: hidden; outline: none;" tabindex="0">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr >
                                                            <th class="per70 text-center">Description</th>
                                                            <th class="per5 text-center">Qty</th>
                                                            <th class="per25 text-center">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
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
                                                              {{ item.qty }} 123123124rdfgfb
                                                            </td>
                                                            <td class="text-center">{{ item.price }} PHP </td>
                                                        </tr>
                                                       
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Sub Total:</th>
                                                            <th class="text-center">{{ selectedOrder.summary.sub_total }} PHP</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Delivery Fee:</th>
                                                            <th class="text-center">{{ selectedOrder.summary.delivery_fee }} PHP</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Discount:</th>
                                                            <th class="text-center">{{ selectedOrder.summary.discount }} PHP</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Total:</th>
                                                            <th class="text-center">{{ selectedOrder.summary.total }} PHP</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="invoice-footer mt25">
                                           
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
                timerInterval: 60,
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
                  this.timerInterval = 60;
                  toastr.info("Refreshing...");
                  this.fetchData();
                }
           }, 1000)
          },
          fetchData: function() {
              var self = this;
              axios.get('/api/merchant/order/list?api_token='+api_token).then(function (response) {
                self.orders = response.data.orders;
              
              }).catch(function (error) {
                  console.log(error);
              });
          },
          updateRider:function(orderid) {

             let formData = new FormData();
                formData.append('rider_id', $('#optRider').val())

                axios.post('/api/data/merchant/update/'+orderid+'/rider/submit?api_token='+api_token, formData).then((response) => {
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

                axios.post('/api/data/merchant/update/'+this.selectedOrder.id+'/status/submit?api_token='+api_token, formData).then((response) => {
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

