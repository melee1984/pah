<template>
  <div>
     <div class="card admin-card dashboard-data-card">
      <div class="admin-card-header">
        <div><h2>Order management</h2><p>Review active, completed, and cancelled marketplace orders.</p></div>
        <span class="dashboard-reload-chip"><i class="fas fa-sync-alt"></i> Refresh in {{ timerInterval }}s</span>
      </div>
      <div class="card-body">
        <div class="dashboard-table-controls"><ul class="nav nav-tabs dashboard-table-tabs" role="tablist">
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'orders' }"
              @click="selectActiveList('orders')"
            >
              Orders <span class="badge badge-primary ml-1">{{ orders.length }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'completed' }"
              @click="selectActiveList('completed')"
            >
              Completed <span class="badge badge-success ml-1">{{ completedOrders.length }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'cancelled' }"
              @click="selectActiveList('cancelled')"
            >
              Cancelled <span class="badge badge-danger ml-1">{{ cancelledOrders.length }}</span>
            </button>
          </li>
        </ul></div>
        <div class="tab-content p-0">
          <!-- Morris chart - Sales -->
          <div class="chart tab-pane active" id="revenue-chart">
            <div class="table-responsive">
              <table class="table dashboard-data-table dashboard-orders-table">
                <thead>
                  <tr>
                    <th>Date/Time</th>
                    <th>Order Information</th>
                    <th>Qty</th>
                    <th nowrap="">Sub Total</th>
                    <th nowrap="">Discount</th>
                    <th nowrap="">Delivery Fee</th>
                    <th>Total</th>
                    <th>Status</th>
                     <th>Rider</th>
                  </tr> 
                </thead>
                <tbody>
                  <tr v-for="order in displayedOrders" :key="order.id" v-bind:class="{ inactive: activeList === 'orders' && !order.rider_id}">
                    <td width="15%">
                        {{ order.submitted_date }}<br>
                        <button type="button" class="dashboard-order-link" v-on:click="displayOrderDetails(order)"><i class="fas fa-receipt"></i> Order #{{ order.cart.order_no }}</button>
                    </td>
                    <td width="25%" v-if="order.partner">
                        Estimated Date/Time: <br><b> {{ order.cart.delivery_date }} - {{ order.cart.delivery_time }}</b> <br>
                        Merchant: <br>
                        <strong>{{ order.partner.restaurant_name }} </strong> <br>
                        <template v-if="order.cart && order.cart.partnerlocation">
                          {{ order.cart.partnerlocation.address_1 }}, {{ order.cart.partnerlocation.address_2 }},
                          <br>{{ order.cart.partnerlocation.mobile }}
                        </template>
                        <span v-else class="text-muted">Merchant location unavailable</span>
                        
                       
                        <br><br>
                    </td>
                    <td width="5%">{{ order.summary.qty }}</td>
                    <td width="5%"><span class="dashboard-money">₱{{ order.summary.sub_total }}</span></td>
                    <td width="5%"><span class="dashboard-money">₱{{ order.summary.discount }}</span></td>
                    <td width="8%"><span class="dashboard-money">₱{{ order.summary.delivery_fee }}</span></td>
                    <td width="10%"><span class="dashboard-money">₱{{ order.summary.total }}</span></td>
                    <td width="10%">
                      <span v-if="order.status">
                        <span class="dashboard-status-pill" :class="statusClass(order.status)">{{ order.status.title }}</span>
                       </span>
                    </td>
                    <td width="10%">
                      <template v-if="order.status">
                        <span v-if="activeList === 'orders' && order.status.id!=5">
                          <select class="form-control" v-model="order.rider_id" style="font-size:12px;" @change="updateRider(order.id, order.rider_id)">
                            <option value="0">Select Rider</option>
                            <option v-for="rider in riders" :value="rider.id">{{ rider.name }}</option>
                          </select>
                        </span>
                        <span v-else>
                            <p v-if="order.rider">{{ order.rider.name }}</p>
                            <span v-else class="text-muted">Not assigned</span>
                        </span>
                      </template>
                    </td>
                  </tr>
                  <tr v-if="displayedOrders.length === 0">
                    <td colspan="9" class="dashboard-table-empty">
                      No {{ activeListLabel.toLowerCase() }} orders found.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
           </div>
        </div>
        <admin-pagination :pagination="paginationMeta" @pagination-change-page="setPage" />
      </div><!-- /.card-body -->
    </div>

     <div class="modal fade" id="orderDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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
                                                <li v-if="selectedOrder.cart && selectedOrder.cart.partnerlocation">
                                                  <p >{{ selectedOrder.cart.partnerlocation.address_1 }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.address_2 }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.mobile }} <br>
                                                  {{ selectedOrder.cart.partnerlocation.telephone }}</p>
                                                </li>
                                                <li v-else class="text-muted">Merchant location unavailable</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!-- col-lg-6 end here -->
                                    <div class="col-lg-12">
                                        Estimated Date/Time: <br><b> {{ selectedOrder?.cart?.delivery_date }} - {{ selectedOrder?.cart?.delivery_time }}</b> <br><br>

                                        Merchant: <br>
                                        <strong>{{ selectedOrder?.partner?.restaurant_name }} </strong> <br>
                                        <template v-if="selectedOrder?.cart?.partnerlocation">
                                          {{ selectedOrder.cart.partnerlocation.address_1 }}, {{ selectedOrder.cart.partnerlocation.address_2 }},
                                          <br>{{ selectedOrder.cart.partnerlocation.mobile }}
                                        </template>
                                        <span v-else class="text-muted">Merchant location unavailable</span>
                                        <br>
                                        <br>
                                        <p><b>Details</b></p>
                                        <table class="table">
                                           <tr v-for="item in selectedOrder?.cart?.details">
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

                                          {{ selectedOrder?.cart?.fullname }} <br>
                                          <span v-if="selectedOrder?.cart?.address">Address: {{ selectedOrder.cart.address.address_1 }}</span> <br>
                                          <span v-if="selectedOrder?.cart?.mobile">Mobile: {{ selectedOrder?.cart?.mobile }}</span>
                                        </p>

                                        <p> 
                                            <b>Summary:</b> <br>
                                            Sub Total: {{ selectedOrder?.summary?.sub_total }} <br>
                                            Delivery Fee: {{ selectedOrder?.summary?.delivery_fee }} <br>
                                            Discount: {{ selectedOrder?.summary?.discount }} <br>
                                          Total: {{ selectedOrder?.summary?.total }} <br>
                                        </p>

                                        <div class="invoice-footer mt25" v-if="selectedOrderIsActive">
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
                completedOrders: [],
                cancelledOrders: [],
                activeList: 'orders',
                timerInterval: 10,
                riders: [],
                selectedOrder: {},
                statuses: [],
                currentPage: 1,
                pageSize: 10,
            }
        },
        computed: {
          allDisplayedOrders: function() {
            if (this.activeList === 'completed') {
              return this.completedOrders;
            }

            if (this.activeList === 'cancelled') {
              return this.cancelledOrders;
            }

            return this.orders;
          },
          displayedOrders: function() {
            const start = (this.currentPage - 1) * this.pageSize;
            return this.allDisplayedOrders.slice(start, start + this.pageSize);
          },
          paginationMeta: function() {
            const total = this.allDisplayedOrders.length;

            return {
              current_page: this.currentPage,
              last_page: Math.max(1, Math.ceil(total / this.pageSize)),
              from: total ? ((this.currentPage - 1) * this.pageSize) + 1 : 0,
              to: Math.min(this.currentPage * this.pageSize, total),
              total,
            };
          },
          activeListLabel: function() {
            if (this.activeList === 'completed') {
              return 'Completed';
            }

            if (this.activeList === 'cancelled') {
              return 'Cancelled';
            }

            return 'Active';
          },
          selectedOrderIsActive: function() {
            return this.selectedOrder
              && Number(this.selectedOrder.status_id) !== 7
              && Number(this.selectedOrder.status_id) !== 8;
          }
        },
        mounted() {
            console.log('Mounted Order List View Component')
              this.fetchData();
              this.selectedOrder = this.orders[0];  
             this.startTimer();
        },
        
        methods: {
          selectActiveList: function(list) {
            this.activeList = list;
            this.currentPage = 1;
          },
          setPage: function(page) {
            this.currentPage = page;
          },
          statusClass: function(status) {
            if (this.activeList === 'completed') return 'is-success';
            if (this.activeList === 'cancelled') return 'is-danger';
            return status && Number(status.id) >= 5 ? 'is-warning' : '';
          },
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
                self.completedOrders = response.data.completedOrders;
                self.cancelledOrders = response.data.cancelledOrders;
                self.riders = response.data.riders;
                self.statuses = response.data.statuses;
                
              }).catch(function (error) {
                  console.log(error);
              });
          },
          updateRider:function(orderid, riderId) {

             let formData = new FormData();
                formData.append('rider_id', riderId)

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
               // Show Bootstrap modal
                const modalEl = document.getElementById('orderDetails');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();

          }
        }
    }

</script>
