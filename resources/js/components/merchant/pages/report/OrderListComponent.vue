<template>
  <div>
     <div class="card admin-card dashboard-data-card merchant-order-card">
      <div class="admin-card-header">
        <div><h2>Order management</h2><p>Review new, completed, and cancelled orders.</p></div>
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
              @click="selectActiveList('pending')"
            >
              New Orders <span class="badge badge-danger ml-1">{{ pendingOrders.length }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'completed' }"
              @click="selectActiveList('completed')"
            >
              Completed <span class="badge badge-success ml-1">{{ completedOrderCount }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeList === 'cancelled' }"
              @click="selectActiveList('cancelled')"
            >
              Cancelled <span class="badge badge-secondary ml-1">{{ cancelledOrderCount }}</span>
            </button>
          </li>
        </ul></div>
        <section v-if="activeList !== 'pending'" class="order-archive-filter" aria-label="Filter archived orders by order option">
          <div>
            <label for="merchant-order-option-filter">Order option</label>
            <small>Filter the {{ activeListLabel.toLowerCase() }} orders in this tab.</small>
          </div>
          <select id="merchant-order-option-filter" v-model="activeFulfillment" class="form-control">
            <option value="">All order options ({{ activeArchiveOrders.length }})</option>
            <option v-for="option in fulfillmentOptions" :key="option.value" :value="option.value">
              {{ option.label }} ({{ archiveFulfillmentCount(option.value) }})
            </option>
          </select>
        </section>
        <div class="tab-content p-0">
          <!-- Morris chart - Sales -->
          <div class="chart tab-pane active" id="revenue-chart">
            <div class="table-responsive">
              <table class="table dashboard-data-table merchant-orders-table">
                <thead>
                  <tr>
                    <th>Date/Time</th>
                    <th>Order Information</th>
                    <th>Order Option</th>
                    <th>Qty</th>
                    <th nowrap="">Sub Total</th>
                    <th>Discount</th>
                    <th nowrap="">Delivery Fee</th>
                    <th>Total</th>
                    <th>Rider</th>
                    <th>Order Status</th>
                    <th>Delivery Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!hasLoaded && isRefreshing">
                    <td colspan="11" class="dashboard-table-empty">
                      Loading orders…
                    </td>
                  </tr>
                  <tr v-else-if="!hasLoaded && refreshError">
                    <td colspan="11" class="dashboard-table-empty merchant-order-load-error">
                      Unable to load orders. Retrying automatically.
                    </td>
                  </tr>
                  <tr v-else-if="displayedOrders.length === 0">
                    <td colspan="11" class="dashboard-table-empty">
                      No {{ activeListLabel.toLowerCase() }}<template v-if="activeFulfillment"> {{ fulfillmentLabel(activeFulfillment).toLowerCase() }}</template> orders found.
                    </td>
                  </tr>
                  <tr v-for="order in displayedOrders" :key="order.id" :class="['order-fulfillment-row', 'is-' + fulfillmentType(order)]">
                    <td width="15%">
                        {{ order.submitted_date }}<br>
                        <button type="button" class="dashboard-order-link" v-on:click="displayOrderDetails(order)"><i class="fas fa-receipt"></i> Order #{{ order.cart.order_no }}</button>
                    </td>
                    <td width="25%">
                        Restaurant: <strong>{{ order.partner.restaurant_name }} </strong> <br>
                        <span><strong>Branch:</strong> {{ storeAddress(order) }}</span><br>
                        {{ fulfillmentScheduleLabel(order) }}: {{ order.cart.delivery_time }}
                        <br>
                        Customer: {{ order.cart.fullname }} <br>
                        <span v-if="fulfillmentType(order) === 'delivery' && order.cart.address">Address: {{ order.cart.address.address_1 }}</span>
                        <span v-else-if="fulfillmentType(order) === 'dine_in'">Table: {{ diningTableLabel(order) }}</span>
                        <br>
                        Mobile: {{ order.cart.mobile }} <br>

                    </td>
                    <td width="8%"><span class="order-option-badge" :class="'is-' + fulfillmentType(order)"><i :class="fulfillmentIcon(order)" aria-hidden="true"></i>{{ fulfillmentLabel(fulfillmentType(order)) }}</span></td>
                    <td width="5%">{{ order.summary.qty }}</td>
                    <td width="5%"><span class="dashboard-money">₱{{ order.summary.sub_total }}</span></td>
                    <td width="5%"><span class="dashboard-money">₱{{ order.summary.discount }}</span></td>
                    <td width="8%"><span v-if="fulfillmentType(order) === 'delivery'" class="dashboard-money">₱{{ order.summary.delivery_fee }}</span><span v-else class="text-muted">—</span></td>
                    <td width="10%"><span class="dashboard-money">₱{{ order.summary.total }}</span></td>
                     <td width="10%">
                      <p v-if="fulfillmentType(order) === 'delivery' && order.rider">{{ order.rider.name }}</p>
                      <span v-else-if="fulfillmentType(order) === 'delivery'" class="text-muted">Not assigned</span>
                      <span v-else class="text-muted">—</span>
                      <span v-if="fulfillmentType(order) === 'delivery' && order.rider_dispatch" class="dashboard-status-pill d-inline-block mt-1" :class="dispatchStatusClass(order.rider_dispatch.status)">{{ order.rider_dispatch.label }}</span>
                    </td>
                    <td width="10%">
                      <span v-if="order.order_status">
                        <span class="dashboard-status-pill" :class="statusBadgeClass(order.order_status_id)">{{ order.order_status.title }}</span>
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td width="10%">
                      <span v-if="fulfillmentType(order) === 'delivery' && order.status">
                        <span class="dashboard-status-pill" :class="statusBadgeClass(order.status.id)">{{ order.status.title }}</span>
                      </span>
                      <span v-else class="text-muted">—</span>
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
              <div class="order-detail-statuses">
                <span v-if="selectedOrder?.order_status" class="order-detail-status" :class="statusClassForModal(selectedOrder.order_status.id)"><small>Order</small>{{ selectedOrder.order_status.title }}</span>
                <span v-if="selectedOrder?.status" class="order-detail-status" :class="statusClassForModal(selectedOrder.status.id)"><small>Delivery</small>{{ selectedOrder.status.title }}</span>
              </div>
              <button type="button" class="order-detail-close" @click="closeOrderDetails" aria-label="Close order details"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div v-if="selectedOrder && selectedOrder.cart" class="modal-body order-detail-body">
            <div class="order-detail-highlights">
              <div><span>{{ fulfillmentScheduleLabel(selectedOrder) }}</span><strong>{{ selectedOrder.cart.delivery_date || 'Date unavailable' }}</strong><small>{{ selectedOrder.cart.delivery_time || 'Time unavailable' }}</small></div>
              <div><span>Order option</span><strong>{{ fulfillmentLabel(fulfillmentType(selectedOrder)) }}</strong><small v-if="fulfillmentType(selectedOrder) === 'dine_in'">{{ diningTableLabel(selectedOrder) }}</small><small v-else>{{ storeAddress(selectedOrder) }}</small></div>
              <div><span>Items</span><strong>{{ selectedOrder?.summary?.qty || 0 }}</strong><small>in this order</small></div>
              <div class="order-detail-highlight-total"><span>Order total</span><strong>₱{{ selectedOrder?.summary?.total || '0.00' }}</strong><small>{{ fulfillmentType(selectedOrder) === 'delivery' ? 'including delivery' : fulfillmentLabel(fulfillmentType(selectedOrder)) + ' order' }}</small></div>
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
                    <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-user"></i></span><div><h3>Customer</h3><p>{{ fulfillmentCustomerLabel(selectedOrder) }}</p></div></div>
                    <strong>{{ selectedOrder.cart.fullname || 'Name unavailable' }}</strong>
                    <p v-if="fulfillmentType(selectedOrder) === 'delivery' && selectedOrder.cart.address" class="order-detail-muted">{{ selectedOrder.cart.address.address_1 }}</p>
                    <p v-else-if="fulfillmentType(selectedOrder) === 'dine_in'" class="order-detail-muted">{{ diningTableLabel(selectedOrder) }}</p>
                    <p v-if="selectedOrder.cart.mobile" class="order-detail-muted">{{ selectedOrder.cart.mobile }}</p>
                  </section>
                </div>

          <section v-if="selectedOrderIsCompleted && fulfillmentType(selectedOrder) === 'delivery'" class="order-detail-panel order-detail-proof-panel">
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
                  <div class="order-detail-summary-row order-detail-payment-method"><span>Payment method</span><strong>{{ selectedOrder?.cart?.payment?.title || 'Not specified' }}</strong></div>
                  <div class="order-detail-summary-row"><span>Subtotal</span><strong>₱{{ selectedOrder?.summary?.sub_total || '0.00' }}</strong></div>
                  <div class="order-detail-summary-row"><span>Convenience fee</span><strong>₱{{ selectedOrder?.summary?.convenience_fee || '0.00' }}</strong></div>
                  <div class="order-detail-summary-row"><span>VAT</span><strong>₱{{ selectedOrder?.summary?.vat_amount || '0.00' }}</strong></div>
                  <div class="order-detail-summary-row"><span>Delivery fee</span><strong>₱{{ selectedOrder?.summary?.delivery_fee || '0.00' }}</strong></div>
                  <div class="order-detail-summary-row"><span>Discount</span><strong>− ₱{{ selectedOrder?.summary?.discount || '0.00' }}</strong></div>
                  <div class="order-detail-summary-total"><span>Total</span><strong>₱{{ selectedOrder?.summary?.total || '0.00' }}</strong></div>
                  <div class="order-detail-summary-row order-detail-commission"><span>Platform commission</span><strong>− ₱{{ selectedOrder?.summary?.total_comm || '0.00' }}</strong></div>
                </section>

            <section v-if="fulfillmentType(selectedOrder) === 'delivery'" class="order-detail-panel">
              <div class="order-detail-section-title"><span class="order-detail-icon"><i class="fas fa-motorcycle"></i></span><div><h3>Delivery partner</h3><p>Assigned rider for this order</p></div></div>
              <div v-if="selectedOrder.rider" class="order-detail-person"><strong>{{ selectedOrder.rider.name }}</strong><span v-if="selectedOrder.rider.mobile">{{ selectedOrder.rider.mobile }}</span></div>
              <p v-else class="order-detail-muted">No rider assigned yet.</p>
              <span v-if="selectedOrder.rider_dispatch" class="dashboard-status-pill d-inline-block mt-2" :class="dispatchStatusClass(selectedOrder.rider_dispatch.status)">{{ selectedOrder.rider_dispatch.label }}</span>
              <p v-if="selectedOrder.rider_dispatch?.status === 'offer_expired'" class="order-detail-muted mt-2">The rider offer expired before anyone accepted it. Pahatud operations can retry the rider search.</p>
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
                activeFulfillment: '',
                fulfillmentOptions: [
                  { value: 'delivery', label: 'Delivery', description: 'Sent by a rider', icon: 'fas fa-motorcycle' },
                  { value: 'pickup', label: 'Pickup', description: 'Collected in store', icon: 'fas fa-shopping-bag' },
                  { value: 'dine_in', label: 'Dine-in', description: 'Served at a table', icon: 'fas fa-utensils' },
                ],
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
            return this.filterArchivedOrders(this.orders.filter(order => [7, 9].includes(Number(order.order_status_id))));
          },
          cancelledOrders: function() {
            return this.filterArchivedOrders(this.orders.filter(order => Number(order.order_status_id) === 8));
          },
          completedOrderCount: function() {
            return this.orders.filter(order => [7, 9].includes(Number(order.order_status_id))).length;
          },
          cancelledOrderCount: function() {
            return this.orders.filter(order => Number(order.order_status_id) === 8).length;
          },
          activeArchiveOrders: function() {
            const statusIds = this.activeList === 'completed' ? [7, 9] : [8];
            return this.orders.filter(order => statusIds.includes(Number(order.order_status_id)));
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

            return 'New';
          },
          selectedOrderIsCompleted: function() {
            return this.selectedOrder && [7, 9].includes(Number(this.selectedOrder.order_status_id));
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
            document.addEventListener('keydown', this.handleEscapeKey);
        },
        beforeDestroy() {
            window.clearInterval(this.refreshTimer);
            document.removeEventListener('keydown', this.handleEscapeKey);

            if (this.modalInstance) {
              this.modalInstance.dispose();
              this.modalInstance = null;
            }
        },

        methods: {
          fulfillmentType: function(order) {
            return order?.cart?.fulfillment_type || 'delivery';
          },
          fulfillmentLabel: function(type) {
            return { delivery: 'Delivery', pickup: 'Pickup', dine_in: 'Dine-in' }[type] || 'Delivery';
          },
          fulfillmentIcon: function(order) {
            const option = this.fulfillmentOptions.find(item => item.value === this.fulfillmentType(order));
            return option ? option.icon : 'fas fa-motorcycle';
          },
          filterArchivedOrders: function(orders) {
            if (!this.activeFulfillment) {
              return orders;
            }

            return orders.filter(order => this.fulfillmentType(order) === this.activeFulfillment);
          },
          archiveFulfillmentCount: function(type) {
            return this.activeArchiveOrders.filter(order => this.fulfillmentType(order) === type).length;
          },
          selectActiveList: function(list) {
            this.activeList = list;
            this.activeFulfillment = '';
          },
          fulfillmentScheduleLabel: function(order) {
            return this.fulfillmentType(order) === 'delivery' ? 'Delivery Date/Time' : 'Requested Date/Time';
          },
          fulfillmentCustomerLabel: function(order) {
            const type = this.fulfillmentType(order);
            if (type === 'pickup') return 'Picking up this order';
            if (type === 'dine_in') return 'Dining at the restaurant';
            return 'Delivery recipient';
          },
          diningTableLabel: function(order) {
            const table = order?.cart?.dining_table;
            return table ? (table.name || table.table_name || ('Table #' + table.id)) : 'Table not specified';
          },
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
          dispatchStatusClass: function(status) {
            if (['assigned', 'rider_at_merchant', 'in_delivery', 'delivered'].includes(status)) return 'is-success';
            if (status === 'offer_expired' || status === 'no_active_offers') return 'is-danger';
            return 'is-warning';
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
          handleEscapeKey(event) {
            if (event.key !== 'Escape' && event.key !== 'Esc' && event.keyCode !== 27) {
              return;
            }

            const modalEl = document.getElementById('orderDetails');

            if (modalEl && modalEl.classList.contains('show')) {
              event.preventDefault();
              this.closeOrderDetails();
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
