<template>
  <div>
    <div class="admin-stat-grid merchant-primary-stats">
      <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-coins"></i></span><div><small>Sales today</small><strong>{{ money(record.salesToday) }}</strong><em>Completed orders</em></div></article>
      <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-calendar-week"></i></span><div><small>This week</small><strong>{{ money(record.salesWeek) }}</strong><em>Sales since Monday</em></div></article>
      <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-calendar-alt"></i></span><div><small>This month</small><strong>{{ money(record.salesMonth) }}</strong><em>Current month sales</em></div></article>
      <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-shopping-bag"></i></span><div><small>Total orders</small><strong>{{ integer(record.totalOrders) }}</strong><em>{{ integer(record.pendingOrder) }} awaiting action</em></div></article>
    </div>

    <div class="merchant-dashboard-grid">
      <article class="card admin-card dashboard-overview-card">
        <div class="admin-card-header"><div><span class="admin-eyebrow">Sales pulse</span><h2>Last 7 days</h2></div><strong class="dashboard-card-total">{{ money(trendTotal) }}</strong></div>
        <div class="dashboard-chart" role="img" aria-label="Completed order sales over the last seven days">
          <div v-for="day in record.salesTrend" :key="day.label" class="dashboard-chart-column">
            <span class="dashboard-chart-value">{{ day.sales ? money(day.sales, 0) : '0' }}</span>
            <div class="dashboard-chart-track"><i :style="{ height: chartHeight(day.sales) }"></i></div><small>{{ day.label }}</small>
          </div>
        </div>
      </article>

      <article class="card admin-card dashboard-overview-card">
        <div class="admin-card-header"><div><span class="admin-eyebrow">Order health</span><h2>Fulfilment overview</h2></div><span class="dashboard-soft-badge">{{ integer(record.totalOrders) }} total</span></div>
        <div class="merchant-status-summary">
          <div><span class="is-warning"><i class="fas fa-clock"></i></span><div><small>Pending</small><strong>{{ integer(record.pendingOrder) }}</strong></div></div>
          <div><span class="is-info"><i class="fas fa-motorcycle"></i></span><div><small>In progress</small><strong>{{ integer(record.onGoingOrder) }}</strong></div></div>
          <div><span class="is-success"><i class="fas fa-check"></i></span><div><small>Completed</small><strong>{{ integer(record.completed) }}</strong></div></div>
          <div><span class="is-danger"><i class="fas fa-times"></i></span><div><small>Cancelled</small><strong>{{ integer(record.cancelled) }}</strong></div></div>
        </div>
        <div class="merchant-completion"><div><span>Completion rate</span><strong>{{ percentage(record.completionRate) }}</strong></div><div class="dashboard-progress"><i class="is-success" :style="{ width: Math.min(100, Number(record.completionRate || 0)) + '%' }"></i></div></div>
      </article>
    </div>

    <div class="dashboard-finance-strip merchant-finance-strip">
      <div><small>Gross completed revenue</small><strong>{{ money(record.grossRevenue) }}</strong></div>
      <div><small>Platform commission</small><strong>{{ money(record.commission) }}</strong></div>
      <div><small>Estimated net revenue</small><strong>{{ money(record.netRevenue) }}</strong></div>
      <div><small>Average completed order</small><strong>{{ money(record.averageOrder) }}</strong></div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      record: {
        pendingOrder: 0, onGoingOrder: 0, completed: 0, cancelled: 0, totalOrders: 0,
        salesToday: 0, salesWeek: 0, salesMonth: 0, grossRevenue: 0, commission: 0,
        netRevenue: 0, averageOrder: 0, completionRate: 0, salesTrend: [],
      },
    };
  },
  computed: {
    trendTotal() { return this.record.salesTrend.reduce((total, day) => total + Number(day.sales || 0), 0); },
    trendMax() { return Math.max(1, ...this.record.salesTrend.map(day => Number(day.sales || 0))); },
  },
  created() { Event.$on('reloadMerchantOrderSummary', this.fetchData); },
  mounted() { this.fetchData(); },
  beforeDestroy() { Event.$off('reloadMerchantOrderSummary', this.fetchData); },
  methods: {
    fetchData() {
      axios.get('/api/merchant/dashboard/order/summary?api_token=' + api_token)
        .then(response => { this.record = { ...this.record, ...response.data.record }; })
        .catch(() => { toastr.error('Unable to refresh dashboard statistics.'); });
    },
    money(value, decimals = 2) { return '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }); },
    integer(value) { return Number(value || 0).toLocaleString('en-PH'); },
    percentage(value) { return Number(value || 0).toFixed(1) + '%'; },
    chartHeight(value) { return Math.max(4, (Number(value || 0) / this.trendMax) * 100) + '%'; },
  },
};
</script>
