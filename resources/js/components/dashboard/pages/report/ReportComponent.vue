<template>
  <div class="card admin-card dashboard-data-card">
    <div class="admin-card-header">
      <div><h2>Sales report</h2><p>Filter order value, commission, and net revenue by merchant and date.</p></div>
    </div>

    <div class="dashboard-filter-bar">
      <div><label for="report-merchant">Merchant</label><select id="report-merchant" v-model="merchant" class="form-control"><option value="">All merchants</option><option v-for="partner in partners" :key="partner.id" :value="partner.id">{{ partner.restaurant_name }}</option></select></div>
      <div><label for="reservationtime">Date and time range</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="far fa-clock"></i></span></div><input id="reservationtime" type="text" class="form-control"></div></div>
      <div class="dashboard-filter-actions"><button type="button" class="btn admin-btn-secondary" @click="searchToday">Today</button><button type="button" class="btn admin-btn-primary" @click="searchSubmit"><i class="fas fa-filter mr-1"></i>Apply</button></div>
    </div>

    <div v-if="loading" class="dashboard-loading"><i class="fas fa-circle-notch fa-spin"></i> Loading sales report…</div>
    <div v-else class="table-responsive">
      <table class="table dashboard-data-table dashboard-report-table">
        <thead><tr><th>Date/time</th><th>Merchant</th><th>Order</th><th>Qty</th><th>Sub total</th><th>Delivery fee</th><th>Discount</th><th>Total</th><th>Commission</th><th>Net</th><th>Rider</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-for="order in paginatedOrders" :key="order.id">
            <td>{{ order.submitted_date }}</td>
            <td><strong>{{ order.partner ? order.partner.restaurant_name : 'Unavailable' }}</strong></td>
            <td><strong>Order #{{ order.cart.order_no }}</strong><small>{{ order.cart.fullname }}</small></td>
            <td>{{ order.summary.qty }}</td>
            <td><span class="dashboard-money">₱{{ order.summary.sub_total }}</span></td>
            <td><span class="dashboard-money">₱{{ order.summary.delivery_fee }}</span></td>
            <td><span class="dashboard-money">{{ order.summary.discount_amount > 0 ? '₱' + order.summary.discount_amount : '—' }}</span></td>
            <td><span class="dashboard-money">₱{{ order.summary.total }}</span></td>
            <td><span class="dashboard-money">₱{{ order.summary.total_comm }}</span></td>
            <td><span class="dashboard-money">₱{{ Number(order.summary.total - order.summary.total_comm).toFixed(2) }}</span></td>
            <td>{{ order.rider ? order.rider.name : 'Not assigned' }}</td>
            <td><span v-if="order.status" class="dashboard-status-pill">{{ order.status.title }}</span></td>
          </tr>
          <tr v-if="!paginatedOrders.length"><td colspan="12" class="dashboard-table-empty">No sales records found for this filter.</td></tr>
        </tbody>
        <tfoot v-if="orders.length">
          <tr><td colspan="3"><strong>Filtered totals</strong></td><td>{{ summary.qty }}</td><td>₱{{ summary.sub_total }}</td><td>₱{{ summary.fee }}</td><td>₱{{ summary.discount }}</td><td>₱{{ summary.total }}</td><td>₱{{ summary.total_comm }}</td><td>₱{{ summary.total_net }}</td><td colspan="2"></td></tr>
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
      summary: { qty: 0, sub_total: 0, discount: 0, fee: 0, total: 0, total_comm: 0, total_net: 0 },
      partners: [],
      merchant: '',
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
    setPage(page) {
      this.currentPage = page;
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
      this.loadReport({ dateFilter: $('#reservationtime').val(), merchant: this.merchant });
    },
    searchToday() {
      this.loadReport({ merchant: this.merchant });
    },
  },
};
</script>
