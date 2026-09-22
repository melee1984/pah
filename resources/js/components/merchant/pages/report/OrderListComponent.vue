<template>
  <div>
     <div class="card admin-card dashboard-data-card merchant-order-card">
      <div class="admin-card-header">
        <div><h2>Order management</h2><p>Review pending, completed, and cancelled orders.</p></div>
        <span
          class="dashboard-reload-chip merchant-order-refresh"
          :class="refreshIndicatorClass"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        >
          <i v-if="isRefreshing" class="fas fa-sync-alt fa-spin" aria-hidden="true"></i>
          <i v-else-if="refreshError" class="fas fa-exclamation-circle" aria-hidden="true"></i>
          <i v-else class="fas fa-check-circle" aria-hidden="true"></i>
          {{ refreshIndicatorText }}
        </span>
      </div>
      <div class="card-body">
        <div class="dashboard-table-controls"><ul class="nav nav-tabs dashboard-table-tabs" role="tablist">
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'pending' }"
              @click="activeList = 'pending'"
            >
              Pending <span class="badge badge-danger ml-1">{{ pendingOrders.length }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'completed' }"
              @click="activeList = 'completed'"
            >
              Completed <span class="badge badge-success ml-1">{{ completedOrders.length }}</span>
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
        </ul></div>
        <div class="tab-content p-0">
          <!-- Morris chart - Sales -->
          <div class="chart tab-pane active" id="revenue-chart">
            <div class="table-responsive">
              <table class="table dashboard-data-table merchant-orders-table">
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
                  <tr v-if="!hasLoaded && isRefreshing">
                    <td colspan="9" class="dashboard-table-empty">
                      Loading orders…
                    </td>
                  </tr>
                  <tr v-else-if="!hasLoaded && refreshError">
                    <td colspan="9" class="dashboard-table-empty merchant-order-load-error">
                      Unable to load orders. Retrying automatically.
                    </td>
                  </tr>
                  <tr v-else-if="displayedOrders.length === 0">
                    <td colspan="9" class="dashboard-table-empty">
                      No {{ activeListLabel.toLowerCase() }} orders found.
                    </td>
                  </tr>
                  <tr v-for="order in displayedOrders" :key="order.id">
                    <td width="15%">
                        {{ order.submitted_date }}<br>
                        <button type="button" class="dashboard-order-link" v-on:click="displayOrderDetails(order)"><i class="fas fa-receipt"></i> Order #{{ order.cart.order_no }}</button>
                    </td>
                    <td width="25%">
                        Restaurant: <strong>{{ order.partner.restaurant_name }} </strong> <br>
                        <span><strong>Branch:</strong> {{ storeAddress(order) }}</span><br>
                        Delivery Date/Time: {{ order.cart.delivery_time }}
                        <br>
                        Customer: {{ order.cart.fullname }} <br>
                        <span v-if="order.cart.address">Address: {{ order.cart.address.address_1 }}</span> <br>
                        Mobile: {{ order.cart.mobile }} <br>

                    </td>
                    <td width="5%">{{ order.summary.qty }}</td>
                    <td width="5%"><span class="dashboard-money">₱{{ order.summary.sub_total }}</span></td>
                    <td width="5%"><span class="dashboard-money">₱{{ order.summary.discount }}</span></td>
                    <td width="8%"><span class="dashboard-money">₱{{ order.summary.delivery_fee }}</span></td>
                    <td width="10%"><span class="dashboard-money">₱{{ order.summary.total }}</span></td>
                     <td width="10%">
                      <p v-if="order.rider">{{ order.rider.name }}</p>
                    </td>
                    <td width="10%">
                      <span v-if="order.order_status">
                        <span class="dashboard-status-pill" :class="statusBadgeClass(order.order_status_id)">{{ order.order_status.title }}</span>
                       </span>
                    </td>

                  </tr>
                </tbody>
              </table>
            </div>
           </div>
        </div>
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
              <span v-if="selectedOrder?.order_status?.title" class="order-detail-status" :class="statusClassForModal(selectedOrder?.order_status_id)">{{ selectedOrder?.order_status?.title }}</span>
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
                      <strong class="order-detail-item-price">₱{{ Number(item.price || 0) + Number(item.variance_total || 0) }}</strong>
                    </div>
                  </div>
                  <p v-else class="order-detail-muted">No items are available for this order.</p>
                </section>
                <div class="order-detail-people">
                  <section class="order-detail-panel">
                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-store"></i></span><div><h3>Merchant</h3><p>Preparing this order</p></div></div>
                    <strong>{{ selectedOrder?.partner?.restaurant_name || 'Merchant unavailable' }}</strong>
                    <p class="order-detail-muted">{{ storeAddress(selectedOrder) }}</p>
                    <p v-if="storeContact(selectedOrder)" class="order-detail-muted">{{ storeContact(selectedOrder) }}</p>
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
     const REFRESH_INTERVAL_SECONDS = 10;

     export default {
       data() {
            return {
                field: {
                },
                errors: {},
                orders: [],
                activeList: 'pending',
                timerInterval: REFRESH_INTERVAL_SECONDS,
                refreshTimer: null,
                isRefreshing: false,
                hasLoaded: false,
                refreshError: '',
                lastUpdatedAt: null,
                riders: [],
                selectedOrder: {},
                statuses: [],
                modalInstance: null,
            }
        },
        computed: {
          pendingOrders: function() {
            return this.orders.filter(order => {
              const statusId = Number(order.order_status_id);

              return statusId >= 1 && statusId <= 6;
            });
          },
          completedOrders: function() {
            return this.orders.filter(order => Number(order.order_status_id) === 7);
          },
          cancelledOrders: function() {
            return this.orders.filter(order => Number(order.order_status_id) === 8);
          },
          displayedOrders: function() {
            if (this.activeList === 'completed') {
              return this.completedOrders;
            }

            if (this.activeList === 'cancelled') {
              return this.cancelledOrders;
            }

            return this.pendingOrders;
          },
          activeListLabel: function() {
            if (this.activeList === 'completed') {
              return 'Completed';
            }

            if (this.activeList === 'cancelled') {
              return 'Cancelled';
            }

            return 'Pending';
          },
          selectedOrderIsCompleted: function() {
            return this.selectedOrder && Number(this.selectedOrder.order_status_id) === 7;
          },
          refreshIndicatorClass: function() {
            return {
              'is-loading': this.isRefreshing,
              'is-error': Boolean(this.refreshError),
              'is-loaded': this.hasLoaded && !this.isRefreshing && !this.refreshError,
            };
          },
          refreshIndicatorText: function() {
            if (this.isRefreshing) {
              return this.hasLoaded ? 'Refreshing orders…' : 'Loading orders…';
            }

            if (this.refreshError) {
              return 'Refresh failed · retry in ' + this.timerInterval + 's';
            }

            if (this.hasLoaded && this.lastUpdatedAt) {
              return 'Loaded ' + this.lastUpdatedAt.toLocaleTimeString('en-PH')
                + ' · refresh in ' + this.timerInterval + 's';
            }

            return 'Waiting to refresh';
          },
        },
        mounted() {
            this.fetchData();
            this.startTimer();
            this.initModal();

        },
        beforeDestroy() {
            window.clearInterval(this.refreshTimer);
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
          statusClassForModal: function(statusId) {
            if (Number(statusId) === 7) return 'is-success';
            if (Number(statusId) === 8) return 'is-danger';
            return 'is-progress';
          },
          statusBadgeClass: function(statusId) {
            statusId = Number(statusId);

            if (statusId === 1) {
              return 'is-warning';
            }

            if (statusId === 2) {
              return 'is-success';
            }

            if (statusId === 7) {
              return 'is-success';
            }

            if (statusId === 8) {
              return 'is-danger';
            }

            return 'is-info';
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
            if (!value) {
              return '';
            }

            const date = new Date(value);

            return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
          },
          startTimer: function () {
            window.clearInterval(this.refreshTimer);
            this.refreshTimer = window.setInterval(() => {
              if (this.isRefreshing) {
                return;
              }

              if (this.timerInterval > 1) {
                this.timerInterval--;
                return;
              }

              this.fetchData(true);
            }, 1000);
          },
          fetchData: function(reloadSummary = false) {
            if (this.isRefreshing) {
              return Promise.resolve(false);
            }

            this.isRefreshing = true;
            this.refreshError = '';

            return axios.get('/api/merchant/order/list', {
              params: {
                api_token: api_token,
                _refresh: Date.now(),
              },
            }).then((response) => {
              const orders = Array.isArray(response.data.orders) ? response.data.orders : [];
              const selectedOrderId = this.selectedOrder ? this.selectedOrder.id : null;

              this.orders = orders;
              this.hasLoaded = true;
              this.lastUpdatedAt = new Date();

              if (selectedOrderId) {
                this.selectedOrder = orders.find(order => order.id === selectedOrderId) || {};
              } else {
                this.selectedOrder = orders[0] || {};
              }

              if (reloadSummary) {
                window.AppEvents.$emit('reloadMerchantOrderSummary');
              }

              return true;
            }).catch((error) => {
              this.refreshError = 'Unable to refresh orders.';
              console.error('Unable to refresh merchant orders.', error);

              return false;
            }).finally(() => {
              this.isRefreshing = false;
              this.timerInterval = REFRESH_INTERVAL_SECONDS;
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

<style scoped>
.merchant-order-refresh {
  justify-content: center;
  min-width: 190px;
}

.merchant-order-refresh.is-loading {
  background: #eef5ff;
  color: #2463a8;
}

.merchant-order-refresh.is-loaded {
  background: #eaf8ef;
  color: #197548;
}

.merchant-order-refresh.is-error,
.merchant-order-load-error {
  color: #b42318;
}

@media (max-width: 767px) {
  .merchant-order-refresh {
    margin-top: 10px;
    width: 100%;
  }
}
</style>
