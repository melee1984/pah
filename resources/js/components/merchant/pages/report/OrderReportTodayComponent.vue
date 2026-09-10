<template>
  <div class="merchant-sales-report">
    <div class="admin-stat-grid merchant-sales-stats">
      <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-coins"></i></span><div><small>Gross completed sales</small><strong>{{ money(summary.total) }}</strong><em>{{ orderCountLabel }}</em></div></article>
      <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-receipt"></i></span><div><small>Completed orders</small><strong>{{ orders.length }}</strong><em>{{ integer(summary.qty) }} items sold</em></div></article>
      <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-percentage"></i></span><div><small>Commission</small><strong>{{ money(summary.total_comm) }}</strong><em>Platform commission</em></div></article>
      <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-wallet"></i></span><div><small>Net earnings</small><strong>{{ money(summary.total_net) }}</strong><em>After commission</em></div></article>
    </div>

    <div class="card admin-card merchant-sales-filter-card">
      <div class="admin-card-header">
        <div><span class="admin-eyebrow">Report period</span><h2>Choose a date range</h2><p>Sales are grouped by the date and time each order was completed.</p></div>
      </div>
      <div class="merchant-sales-filter">
        <div class="form-group">
          <label for="reservationtime">Date and time range</label>
          <div class="merchant-date-input"><i class="far fa-calendar-alt"></i><input id="reservationtime" type="text" class="form-control" aria-label="Sales report date and time range"></div>
        </div>
        <div class="merchant-sales-filter-actions">
          <button type="button" class="btn admin-btn-secondary" :disabled="isLoading" @click="searchToday"><i class="fas fa-calendar-day mr-2"></i>Today</button>
          <button type="button" class="btn admin-btn-primary" :disabled="isLoading" @click="searchSubmit"><i class="fas fa-search mr-2"></i>{{ isLoading ? 'Loading…' : 'Run report' }}</button>
        </div>
      </div>
    </div>

    <div class="card admin-card dashboard-data-card merchant-sales-table-card">
      <div class="admin-card-header">
        <div><h2>{{ reportTitle }}</h2><p>Only completed orders are included in sales totals.</p></div>
        <span class="dashboard-soft-badge">{{ orders.length }} {{ orders.length === 1 ? 'order' : 'orders' }}</span>
      </div>
      <div v-if="isLoading" class="dashboard-loading"><i class="fas fa-circle-notch fa-spin"></i>Loading completed sales…</div>
      <div v-else class="card-body table-responsive p-0">
        <table class="table dashboard-data-table merchant-sales-table">
          <thead><tr><th>Completed</th><th>Order</th><th>Items</th><th>Subtotal</th><th>Delivery fee</th><th>Discount</th><th>Gross total</th><th>Commission</th><th>Net earnings</th><th>Rider</th><th>Status</th></tr></thead>
          <tbody>
            <tr v-if="orders.length === 0"><td colspan="11" class="dashboard-table-empty">No completed sales were found for this period.</td></tr>
            <tr v-for="order in orders" :key="order.id">
              <td><strong>{{ order.completed_date }}</strong><small>Completion time</small></td>
              <td><strong>#{{ order.cart.order_no }}</strong><small>{{ order.cart.fullname || 'Customer not available' }}</small></td>
              <td><span class="admin-number-pill">{{ order.summary.qty }}</span></td>
              <td><span class="dashboard-money">{{ money(order.summary.sub_total) }}</span></td>
              <td><span class="dashboard-money">{{ money(order.summary.delivery_fee) }}</span></td>
              <td><span class="merchant-sales-discount">{{ number(order.summary.discount) > 0 ? money(order.summary.discount) : '—' }}</span></td>
              <td><span class="dashboard-money">{{ money(order.summary.total) }}</span></td>
              <td><span class="merchant-sales-commission">{{ money(order.summary.total_comm) }}</span></td>
              <td><strong class="merchant-sales-net">{{ money(order.summary.net) }}</strong></td>
              <td><strong v-if="order.rider">{{ order.rider.name }}</strong><span v-else class="text-muted">Not assigned</span></td>
              <td><span v-if="order.status" class="dashboard-status-pill is-success"><i class="fas fa-check mr-1"></i>{{ order.status.title }}</span></td>
            </tr>
          </tbody>
          <tfoot v-if="orders.length">
            <tr><td colspan="2">Report totals</td><td>{{ integer(summary.qty) }}</td><td>{{ money(summary.sub_total) }}</td><td>{{ money(summary.fee) }}</td><td>{{ money(summary.discount) }}</td><td>{{ money(summary.total) }}</td><td>{{ money(summary.total_comm) }}</td><td>{{ money(summary.total_net) }}</td><td colspan="2"></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      orders: [],
      summary: this.emptySummary(),
      isLoading: false,
      reportTitle: "Today's completed sales",
    };
  },
  computed: {
    orderCountLabel() {
      return `${this.orders.length} completed ${this.orders.length === 1 ? 'order' : 'orders'}`;
    },
  },
  mounted() {
    this.searchToday();
  },
  methods: {
    emptySummary() {
      return { qty: 0, sub_total: 0, discount: 0, fee: 0, total: 0, total_comm: 0, total_net: 0 };
    },
    loadReport(dateFilter) {
      this.isLoading = true;
      const payload = dateFilter ? { dateFilter } : {};

      axios.post(`/api/merchant/order/search/list?api_token=${api_token}`, payload)
        .then((response) => {
          this.orders = response.data.orders || [];
          this.summary = response.data.totalSummary || this.emptySummary();
        })
        .catch((error) => {
          this.orders = [];
          this.summary = this.emptySummary();
          toastr.error(error.response?.data?.message || 'Unable to load the sales report.');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    searchSubmit() {
      const dateFilter = $('#reservationtime').val();
      this.reportTitle = dateFilter || 'Completed sales';
      this.loadReport(dateFilter);
    },
    searchToday() {
      this.reportTitle = "Today's completed sales";
      this.loadReport();
    },
    number(value) {
      const parsed = Number(String(value ?? 0).replace(/,/g, ''));
      return Number.isFinite(parsed) ? parsed : 0;
    },
    money(value) {
      return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(this.number(value));
    },
    integer(value) {
      return new Intl.NumberFormat('en-PH', { maximumFractionDigits: 0 }).format(this.number(value));
    },
  },
};
</script>
