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
                        <div v-if="canRetryRiderOffers(order)" class="mt-2">
                          <small v-if="order.rider_dispatch.pending_offers" class="d-block text-muted mb-1">{{ order.rider_dispatch.pending_offers }} active rider offer(s)</small>
                          <button type="button" class="btn btn-sm btn-outline-primary" :disabled="retryingOrderId === order.id" @click="retryRiderOffers(order)">
                            {{ retryingOrderId === order.id ? 'Checking riders…' : 'Check available riders' }}
                          </button>
                        </div>
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

     <div class="modal fade order-detail-modal" id="orderDetails" tabindex="-1" role="dialog" aria-labelledby="orderDetailsTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header order-detail-header">
            <div class="order-detail-heading">
              <span class="order-detail-eyebrow"><i class="fas fa-receipt"></i> Order overview</span>
              <h2 id="orderDetailsTitle">Order #{{ selectedOrder?.cart?.order_no || '—' }}</h2>
              <p>Placed {{ selectedOrder?.submitted_date || selectedOrder?.cart?.processed_at || 'Date unavailable' }}</p>
            </div>
            <div class="order-detail-header-actions">
              <span v-if="selectedOrder?.status?.title" class="order-detail-status" :class="statusClassForModal(selectedOrder?.status_id)">{{ selectedOrder?.status?.title }}</span>
              <button type="button" class="order-detail-close" @click="closeOrderDetails" aria-label="Close order details"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div v-if="selectedOrder && selectedOrder.cart" class="modal-body order-detail-body">
            <div class="order-detail-highlights">
              <div><span>Scheduled delivery</span><strong>{{ selectedOrder.cart.delivery_date || 'Date unavailable' }}</strong><small>{{ selectedOrder.cart.delivery_time || 'Time unavailable' }}</small></div>
              <div><span>Items</span><strong>{{ selectedOrder?.summary?.qty || 0 }}</strong><small>in this order</small></div>
              <div class="order-detail-highlight-total"><span>Order total</span><strong>₱{{ selectedOrder?.summary?.total || '0.00' }}</strong><small>including delivery</small></div>
            </div>
            <div class="order-detail-layout">
              <div class="order-detail-main">
                <section class="order-detail-panel">
                  <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-utensils"></i></span><div><h3>Items ordered</h3><p>A breakdown of the customer's order</p></div></div>
                  <div v-if="selectedOrder.cart.details && selectedOrder.cart.details.length" class="order-detail-items">
                    <div v-for="(item, index) in selectedOrder.cart.details" :key="item.id || index" class="order-detail-item">
                      <span class="order-detail-item-qty">{{ item.qty }}×</span>
                      <div class="order-detail-item-copy"><strong>{{ item.item?.title || 'Item unavailable' }}</strong><span v-for="(variation, variationIndex) in item.variance_content" :key="variationIndex">+ {{ variation.title }}</span><em v-if="item.instruction">Note: {{ item.instruction }}</em></div>
                      <strong class="order-detail-item-price">₱{{ item.price }}</strong>
                    </div>
                  </div>
                  <p v-else class="order-detail-muted">No items are available for this order.</p>
                </section>
                <div class="order-detail-people">
                  <section class="order-detail-panel">
                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-store"></i></span><div><h3>Merchant</h3><p>Preparing this order</p></div></div>
                    <strong>{{ selectedOrder?.partner?.restaurant_name || 'Merchant unavailable' }}</strong>
                    <p class="order-detail-muted">{{ selectedOrder?.cart?.partnerlocation?.address_1 }}<template v-if="selectedOrder?.cart?.partnerlocation?.address_2">, {{ selectedOrder.cart.partnerlocation.address_2 }}</template></p>
                    <p v-if="selectedOrder?.cart?.partnerlocation?.mobile" class="order-detail-muted">{{ selectedOrder.cart.partnerlocation.mobile }}</p>
                  </section>
                  <section class="order-detail-panel">
                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-user"></i></span><div><h3>Customer</h3><p>Delivery recipient</p></div></div>
                    <strong>{{ selectedOrder.cart.fullname || 'Name unavailable' }}</strong>
                    <p v-if="selectedOrder.cart.address" class="order-detail-muted">{{ selectedOrder.cart.address.address_1 }}</p>
                    <p v-if="selectedOrder.cart.mobile" class="order-detail-muted">{{ selectedOrder.cart.mobile }}</p>
                  </section>
                </div>

          <section v-if="selectedOrderIsCompleted" class="order-detail-panel order-detail-proof-panel">
            <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-camera"></i></span><div><h3>Proof of delivery</h3><p>Confirmation provided by the rider</p></div></div>
            <div v-if="selectedOrder.delivery_proofs && selectedOrder.delivery_proofs.length" class="order-detail-proof-grid">
              <a v-for="proof in selectedOrder.delivery_proofs" :key="proof.id" :href="proof.file_url || undefined" :target="proof.file_url ? '_blank' : undefined" :class="['order-detail-proof', { 'order-detail-proof--static': !proof.file_url }]" rel="noopener">
                <img v-if="proof.file_url" :src="proof.file_url" alt="Proof of delivery">
                <span v-else class="order-detail-proof-placeholder"><i class="fas fa-check-circle"></i></span>
                <span class="order-detail-proof-caption"><strong>{{ proofMethodLabel(proof.method) }}</strong><small>{{ formatProofDate(proof.created_at) }}</small></span>
              </a>
            </div>
            <p v-else class="order-detail-muted">No proof of delivery was submitted.</p>
          </section>
              </div>
              <aside class="order-detail-side">
                <section class="order-detail-panel order-detail-summary">
                  <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-file-invoice"></i></span><div><h3>Payment summary</h3><p>Order charges at a glance</p></div></div>
                  <div class="order-detail-summary-row"><span>Subtotal</span><strong>₱{{ selectedOrder?.summary?.sub_total || '0.00' }}</strong></div>
                  <div class="order-detail-summary-row"><span>Delivery fee</span><strong>₱{{ selectedOrder?.summary?.delivery_fee || '0.00' }}</strong></div>
                  <div class="order-detail-summary-row"><span>Discount</span><strong>− ₱{{ selectedOrder?.summary?.discount || '0.00' }}</strong></div>
                  <div class="order-detail-summary-total"><span>Total</span><strong>₱{{ selectedOrder?.summary?.total || '0.00' }}</strong></div>
                </section>

            <section class="order-detail-panel">
              <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-motorcycle"></i></span><div><h3>Delivery partner</h3><p>Assigned rider for this order</p></div></div>
              <div v-if="selectedOrder.rider" class="order-detail-person"><strong>{{ selectedOrder.rider.name }}</strong><span v-if="selectedOrder.rider.mobile">{{ selectedOrder.rider.mobile }}</span></div>
              <p v-else class="order-detail-muted">No rider assigned yet.</p>
            </section>

            <section v-if="selectedOrderIsActive" class="order-detail-panel order-detail-action">
              <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-truck"></i></span><div><h3>Update delivery status</h3><p>Choose the next status for this order.</p></div></div>
              <label class="order-detail-label" for="orderDeliveryStatus">Delivery status</label>
              <select id="orderDeliveryStatus" class="form-control" v-model="selectedOrder.status_id" @change="updateStatus($event)">
                <option value="0">Select status</option>
                <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.title }}</option>
              </select>
            </section>
              </aside>
            </div>
          </div>
          <div class="modal-footer order-detail-footer"><button type="button" class="btn order-detail-done" @click="closeOrderDetails">Done</button></div>
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
                modalInstance: null,
                statuses: [],
                currentPage: 1,
                pageSize: 10,
                retryingOrderId: null,
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
          },
          selectedOrderIsCompleted: function() {
            return this.selectedOrder && Number(this.selectedOrder.status_id) === 7;
          }
        },
        mounted() {
            console.log('Mounted Order List View Component')
              this.fetchData();
              this.selectedOrder = this.orders[0];
             this.startTimer();
        },

        methods: {
          canRetryRiderOffers: function(order) {
            return this.activeList === 'orders'
              && Boolean(order.store_accepted_at)
              && [2, 3, 4].includes(Number(order.order_status_id))
              && !order.rider_dispatch?.assigned;
          },
          retryRiderOffers: function(order) {
            this.retryingOrderId = order.id;
            axios.post('/api/data/dashboard/orders/' + order.id + '/retry-rider-offers?api_token=' + api_token)
              .then((response) => {
                if (response.data.new_offers > 0) {
                  toastr.success(response.data.message);
                } else {
                  toastr.info(response.data.message);
                }
                this.fetchData();
              })
              .catch((error) => {
                toastr.error(error.response?.data?.message || 'Could not check available riders. Please try again.');
                this.fetchData();
              })
              .finally(() => {
                this.retryingOrderId = null;
              });
          },
          selectActiveList: function(list) {
            this.activeList = list;
            this.currentPage = 1;
          },
          setPage: function(page) {
            this.currentPage = page;
          },
          statusClassForModal: function(statusId) {
            if (Number(statusId) === 7) return 'is-success';
            if (Number(statusId) === 8) return 'is-danger';
            return 'is-progress';
          },
          statusClass: function(status) {
            if (this.activeList === 'completed') return 'is-success';
            if (this.activeList === 'cancelled') return 'is-danger';
            return status && Number(status.id) >= 5 ? 'is-warning' : '';
          },
          proofMethodLabel: function(method) {
            const labels = {
              photo: 'Delivery photo',
              pin: 'PIN verification',
              qr: 'QR verification',
              signature: 'Customer signature',
            };

            return labels[method] || 'Delivery proof';
          },
          formatProofDate: function(value) {
            if (!value) return '';

            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
          },
          startTimer: function () {
           setInterval(() => {
                this.timerInterval--;
                if (this.timerInterval ==0) {
                  this.timerInterval = 10;
                  toastr.info("Refreshing...");
                  this.fetchData();
                  window.AppEvents.$emit('reloadDashboardOrderSummary');
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
              if (!this.modalInstance) {
                this.modalInstance = new bootstrap.Modal(document.getElementById('orderDetails'));
              }
              this.modalInstance.show();
          },
          closeOrderDetails: function() {
              if (this.modalInstance) {
                this.modalInstance.hide();
              }
          }
        }
    }

</script>
