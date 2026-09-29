<template>
  <div class="card admin-card dashboard-data-card">
    <div class="admin-card-header">
      <div><h2>Sales report</h2><p>Filter order value, commission, and net revenue by merchant and date.</p></div>
    </div>

    <div class="dashboard-filter-bar sales-order-filter-bar">
      <div><label for="report-merchant">Merchant</label><select id="report-merchant" v-model="merchant" class="form-control"><option value="">All merchants</option><option v-for="partner in partners" :key="partner.id" :value="partner.id">{{ partner.restaurant_name }}</option></select></div>
      <div><label for="report-order-option">Order option</label><select id="report-order-option" v-model="fulfillmentType" class="form-control"><option value="">All order options</option><option value="delivery">Delivery</option><option value="pickup">Pickup</option><option value="dine_in">Dine-in</option></select></div>
      <div><label for="reservationtime">Date and time range</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="far fa-clock"></i></span></div><input id="reservationtime" type="text" class="form-control"></div></div>
      <div class="dashboard-filter-actions"><button type="button" class="btn admin-btn-secondary" @click="searchToday">Today</button><button type="button" class="btn admin-btn-primary" @click="searchSubmit"><i class="fas fa-filter mr-1"></i>Apply</button></div>
    </div>

    <div v-if="loading" class="dashboard-loading"><i class="fas fa-circle-notch fa-spin"></i> Loading sales report…</div>
    <div v-else class="table-responsive">
      <table class="table dashboard-data-table dashboard-report-table">
        <thead><tr><th>Date/time</th><th>Merchant</th><th>Order</th><th>Order option</th><th>Qty</th><th>Sub total</th><th>Convenience fee</th><th>VAT</th><th>Delivery fee</th><th>Discount</th><th>Total</th><th>Commission</th><th>Net</th><th>Rider</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-for="order in paginatedOrders" :key="order.id">
            <td>{{ order.submitted_date }}</td>
            <td><strong>{{ order.partner ? order.partner.restaurant_name : 'Unavailable' }}</strong></td>
            <td><a class="dashboard-order-link" :href="orderDetailsUrl(order)"><i class="fas fa-receipt"></i> Order #{{ order.cart.order_no }}</a><small>{{ order.cart.fullname }}</small></td>
            <td><span class="order-option-badge" :class="'is-' + fulfillmentTypeFor(order)"><i :class="fulfillmentIcon(order)" aria-hidden="true"></i>{{ fulfillmentLabel(order) }}</span></td>
            <td>{{ order.summary.qty }}</td>
            <td><span class="dashboard-money">₱{{ order.summary.sub_total }}</span></td>
            <td><span class="dashboard-money">₱{{ order.summary.convenience_fee }}</span></td>
            <td><span class="dashboard-money">₱{{ order.summary.vat_amount }}</span></td>
            <td><span v-if="fulfillmentTypeFor(order) === 'delivery'" class="dashboard-money">₱{{ order.summary.delivery_fee }}</span><span v-else class="text-muted">—</span></td>
            <td><span class="dashboard-money">{{ Number(order.summary.discount) > 0 ? '₱' + order.summary.discount : '—' }}</span></td>
            <td><span class="dashboard-money">₱{{ order.summary.total }}</span></td>
            <td><span class="dashboard-money">₱{{ order.summary.total_comm }}</span></td>
            <td><span class="dashboard-money">₱{{ netAmount(order) }}</span></td>
            <td>{{ fulfillmentTypeFor(order) === 'delivery' ? (order.rider ? order.rider.name : 'Not assigned') : 'Not required' }}</td>
            <td><span v-if="order.status" class="dashboard-status-pill">{{ order.status.title }}</span></td>
          </tr>
          <tr v-if="!paginatedOrders.length"><td colspan="15" class="dashboard-table-empty">No sales records found for this filter.</td></tr>
        </tbody>
        <tfoot v-if="orders.length">
          <tr><td colspan="4"><strong>Filtered totals</strong></td><td>{{ summary.qty }}</td><td>₱{{ summary.sub_total }}</td><td>₱{{ summary.convenience_fee }}</td><td>₱{{ summary.vat_amount }}</td><td>₱{{ summary.fee }}</td><td>₱{{ summary.discount }}</td><td>₱{{ summary.total }}</td><td>₱{{ summary.total_comm }}</td><td>₱{{ summary.total_net }}</td><td colspan="2"></td></tr>
        </tfoot>
      </table>
    </div>
    <admin-pagination v-if="!loading" :pagination="paginationMeta" @pagination-change-page="setPage" />
  </div>
</template>

<script>
export default {
  data() {
    return {
      orders: [],
      summary: { qty: 0, sub_total: 0, convenience_fee: 0, vat_amount: 0, discount: 0, fee: 0, total: 0, total_comm: 0, total_net: 0 },
      partners: [],
      merchant: '',
      fulfillmentType: '',
      loading: true,
      currentPage: 1,
      pageSize: 25,
    };
  },
  computed: {
    paginatedOrders() {
      const start = (this.currentPage - 1) * this.pageSize;
      return this.orders.slice(start, start + this.pageSize);
    },
    paginationMeta() {
      const total = this.orders.length;
      return { current_page: this.currentPage, last_page: Math.max(1, Math.ceil(total / this.pageSize)), from: total ? ((this.currentPage - 1) * this.pageSize) + 1 : 0, to: Math.min(this.currentPage * this.pageSize, total), total };
    },
  },
  mounted() {
    this.searchToday();
  },
  methods: {
    orderDetailsUrl(order) {
      return `/data/dashboard/orders/${order.id}`;
    },
    setPage(page) {
      this.currentPage = page;
    },
    fulfillmentTypeFor(order) {
      return order?.cart?.fulfillment_type || 'delivery';
    },
    fulfillmentLabel(order) {
      return { delivery: 'Delivery', pickup: 'Pickup', dine_in: 'Dine-in' }[this.fulfillmentTypeFor(order)] || 'Delivery';
    },
    fulfillmentIcon(order) {
      return { delivery: 'fas fa-motorcycle', pickup: 'fas fa-shopping-bag', dine_in: 'fas fa-utensils' }[this.fulfillmentTypeFor(order)] || 'fas fa-motorcycle';
    },
    number(value) {
      const parsed = Number(String(value ?? 0).replace(/,/g, ''));
      return Number.isFinite(parsed) ? parsed : 0;
    },
    netAmount(order) {
      return (this.number(order?.summary?.total) - this.number(order?.summary?.total_comm)).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    loadReport(payload) {
      this.loading = true;
      this.currentPage = 1;
      axios.post(`/api/data/order/search/list?api_token=${api_token}`, payload)
        .then((response) => {
          this.orders = response.data.orders || [];
          this.summary = response.data.totalSummary;
          this.partners = response.data.partners || [];
        })
        .catch(() => toastr.error('Unable to load the sales report.'))
        .finally(() => {
          this.loading = false;
        });
    },
    searchSubmit() {
      this.loadReport({ dateFilter: $('#reservationtime').val(), merchant: this.merchant, fulfillment_type: this.fulfillmentType });
    },
    searchToday() {
      this.loadReport({ merchant: this.merchant, fulfillment_type: this.fulfillmentType });
    },
  },
};
</script>
