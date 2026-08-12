<template>
  <div>
     <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
          <h3 class="card-title">
            <i class="fas fa-chart-pie mr-1"></i>
            Orders Dashboard
          </h3>
        </div> 
         <div class="col-md-6 text-right">
            Reload {{ timerInterval }}
         </div>  
        </div> 
      </div><!-- /.card-header -->
      <div class="card-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'pending' }"
              @click="activeList = 'pending'"
            >
              Pending Orders <span class="badge badge-danger ml-1">{{ pendingOrders.length }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'accepted' }"
              @click="activeList = 'accepted'"
            >
              Accepted Orders <span class="badge badge-success ml-1">{{ acceptedOrders.length }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'cancelled' }"
              @click="activeList = 'cancelled'"
            >
              Cancelled <span class="badge badge-secondary ml-1">{{ cancelledOrders.length }}</span>
            </button>
          </li>
        </ul>
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
                  <tr v-if="displayedOrders.length === 0">
                    <td colspan="9" class="text-center text-muted py-4">
                      No {{ activeListLabel.toLowerCase() }} orders found.
                    </td>
                  </tr>
                  <tr v-for="order in displayedOrders" :key="order.id" v-bind:class="{ inactive: activeList === 'accepted' && !order.rider_id}">
                    <td width="15%">
                        {{ order.submitted_date }}<br>
                        <a href="javascript:void(0)" class="btn btn-xs btn-danger" v-on:click="displayOrderDetails(order)"><strong>Order # {{ order.cart.order_no }}  </strong></a>
                    </td>
                    <td width="25%">
                        Restaurant: <strong>{{ order.partner.restaurant_name }} </strong> <br>
                        <span><strong>Store Location:</strong> {{ storeAddress(order) }}</span><br>
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
                      <p v-if="order.rider">{{ order.rider.name }}</p>
                    </td>
                    <td width="10%">
                      <span v-if="order.status">
                        <span class="badge" :class="statusBadgeClass(order.status.id)">{{ order.status.title }}</span>
                       </span>
                    </td>
                   
                  </tr>
                </tbody>
              </table> 
           </div>
        </div>
      </div><!-- /.card-body -->
    </div>

     <div  class="modal fade" id="orderDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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
                                        <div class="invoice-logo" v-if="selectedOrder?.partner?.img">
                                          <img width="100" :src="'/uploads/user/'+selectedOrder.partner.id+'/'+selectedOrder.partner.img" alt="Invoice logo">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="invoice-from">
                                            <ul class="list-unstyled text-right">
                                                <li><strong>{{ selectedOrder?.partner?.restaurant_name }}</strong></li>
                                                <li><strong>Store Location</strong></li>
                                                <li>{{ storeAddress(selectedOrder) }}</li>
                                                <li v-if="storeContact(selectedOrder)">{{ storeContact(selectedOrder) }}</li>
                                                <li v-if="storeCoordinates(selectedOrder)">
                                                  Coordinates: {{ storeCoordinates(selectedOrder) }}
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
                                                    <li><strong>Order #</strong> #{{ selectedOrder?.cart?.order_no }}</li>
                                                    <li><strong>Processed at:</strong>{{ selectedOrder?.cart?.processed_at }}</li>

                                                    <li><strong>Date/Time:</strong> {{ selectedOrder?.cart?.delivery_date }} @ {{ selectedOrder?.cart?.delivery_time }}</li>
                                                    <li><strong>Status:</strong> 
                                                        <span class="label label-danger">{{ selectedOrder?.status?.title }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="invoice-to mt25">
                                            <ul class="list-unstyled">
                                                 <li><strong>Invoiced To</strong></li>
                                                <li>Fullname: {{ selectedOrder?.cart?.fullname }}</li>
                                                <li>Address: {{ selectedOrder?.cart?.address?.address_1 }}</li>
                                                <li>Mobile: {{ selectedOrder?.cart?.mobile }}</li>
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
                                                        <tr v-for="item in selectedOrder?.cart?.details">
                                                            <td>
                                                              {{ item.item.title }}
                                                              <br>
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
                                                            <td class="text-center">{{ item.price + item.variance_total}} PHP</td>
                                                        </tr>
                                                       
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Sub Total:</th>
                                                            <th class="text-center">{{ selectedOrder?.summary?.sub_total }} PHP</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Delivery Fee:</th>
                                                            <th class="text-center">{{ selectedOrder?.summary?.delivery_fee }} PHP</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Discount:</th>
                                                            <th class="text-center">{{ selectedOrder?.summary?.discount }} PHP</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Total:</th>
                                                            <th class="text-center">{{ selectedOrder?.summary?.total }} PHP</th>
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
                orders: [],
                activeList: 'pending',
                timerInterval: 60,
                riders: [],
                selectedOrder: {},
                statuses: [],
                modalInstance: null,
            }
        },
        computed: {
          pendingOrders: function() {
            return this.orders.filter(order => Number(order.status_id) === 1);
          },
          acceptedOrders: function() {
            return this.orders.filter(order => {
              const statusId = Number(order.status_id);

              return statusId >= 2 && statusId <= 6;
            });
          },
          cancelledOrders: function() {
            return this.orders.filter(order => Number(order.status_id) === 8);
          },
          displayedOrders: function() {
            if (this.activeList === 'accepted') {
              return this.acceptedOrders;
            }

            if (this.activeList === 'cancelled') {
              return this.cancelledOrders;
            }

            return this.pendingOrders;
          },
          activeListLabel: function() {
            if (this.activeList === 'accepted') {
              return 'Accepted';
            }

            if (this.activeList === 'cancelled') {
              return 'Cancelled';
            }

            return 'Pending';
          },
        },
        mounted() {
            console.log('Mounted Order List View Component')
            this.fetchData();
            this.selectedOrder = this.orders[0];  
            this.startTimer();
            this.initModal();

        },
        
        methods: {
          storeLocation: function(order) {
            return order && order.cart ? order.cart.partnerlocation : null;
          },
          storeAddress: function(order) {
            const location = this.storeLocation(order);

            if (location) {
              return [location.address_1, location.address_2, location.city, location.zip_code]
                .filter(Boolean)
                .join(', ') || 'Not available';
            }

            const partner = order ? order.partner : null;

            return partner
              ? [partner.address, partner.city].filter(Boolean).join(', ') || 'Not available'
              : 'Not available';
          },
          storeContact: function(order) {
            const location = this.storeLocation(order);

            if (!location) {
              return '';
            }

            return [location.mobile, location.telephone].filter(Boolean).join(' / ');
          },
          storeCoordinates: function(order) {
            const location = this.storeLocation(order);

            if (!location || !location.latitude || !location.longtitude) {
              return '';
            }

            return location.latitude + ', ' + location.longtitude;
          },
          statusBadgeClass: function(statusId) {
            statusId = Number(statusId);

            if (statusId === 1) {
              return 'badge-danger';
            }

            if (statusId === 2) {
              return 'badge-success';
            }

            if (statusId === 8) {
              return 'badge-secondary';
            }

            return 'badge-warning';
          },
          startTimer: function () {
           setInterval(() => {
                this.timerInterval--;
                if (this.timerInterval ==0) {
                  this.timerInterval = 60;
                  toastr.info("Refreshing...");
                  this.fetchData();
                  Event.$emit('reloadMerchantOrderSummary');
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
            this.openOrderDetails();
          },
          openOrderDetails() {
            if (!this.modalInstance) {
              const modalEl = document.getElementById("orderDetails");
              this.modalInstance = new bootstrap.Modal(modalEl);
            }
            this.modalInstance.show();
          },
          closeOrderDetails() {
            if (this.modalInstance) {
              this.modalInstance.hide();
            }
          },
          initModal() {
            const modalEl = document.getElementById("orderDetails");
            this.modalInstance = new bootstrap.Modal(modalEl, {
              backdrop: "static", // optional
              keyboard: true,
            });
          },

        }
    }
</script>
