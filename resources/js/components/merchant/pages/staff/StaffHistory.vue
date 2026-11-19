<template>
  <section class="panel staff-history" aria-label="Order history">
    <form class="history-filters" @submit.prevent="page = 1; load()"><label>Placed from<input v-model="from" type="date" required></label><label>Through<input v-model="to" type="date" :min="from" required></label><label>Sort<select v-model="sort"><option value="newest">Newest first</option><option v-if="config.abilities.reports" value="fastest">Fastest served first</option></select></label><button class="btn primary" :disabled="loading">{{ loading ? 'Loading…' : 'Apply filters' }}</button></form>
    <div class="history-context"><span>{{ config.branch.name }} · {{ total }} orders</span><p>{{ sort === 'fastest' ? 'Ranked by placement to staff-recorded serving time. Orders without valid serving timestamps are excluded from this ranking.' : 'A dash means no reliable timestamp was recorded. Delivery completion is not treated as a staff serving time.' }}</p></div>
    <p v-if="error" class="banner error" role="alert">{{ error }}</p>
    <div class="history-scroll"><table><thead><tr><th>Order / branch</th><th>Payment · Kitchen</th><th>Placed</th><th>Paid</th><th>Served</th><th>Time to serve</th><th>Details</th></tr></thead><tbody><tr v-for="order in orders" :key="order.id"><td><strong>#{{ order.number }}</strong><small>{{ config.branch.name }}</small><small>{{ typeName(order.type) }} · {{ order.table_name }}</small></td><td><span class="badge" :class="order.status">{{ order.paid ? 'Paid' : 'Unpaid' }} · {{ statusName(order.status) }}</span><button v-if="config.abilities.pay && order.bill_id && !order.paid && order.status !== 'cancelled'" class="textbtn history-pay" @click="$emit('collect', order)">Pay Now</button><button v-if="config.abilities.cashier && order.paid && order.bill_id" class="textbtn history-pay" @click="$emit('receipt', order.bill_id)">Receipt</button></td><td>{{ timestamp(order.placed_at) }}</td><td>{{ timestamp(order.paid_at) }}</td><td>{{ timestamp(order.served_at) }}</td><td><strong>{{ duration(order.time_to_serve_seconds) }}</strong></td><td><details><summary>Items</summary><div class="history-items"><article v-for="(item, index) in order.items" :key="index"><b>{{ item.qty }}× {{ item.name }}</b><small v-if="item.variants">{{ item.variants }}</small><small v-if="item.note">Item note: {{ item.note }}</small></article><p v-if="order.notes"><b>Order notes:</b> {{ order.notes }}</p></div></details></td></tr></tbody></table></div>
    <div v-if="!orders.length && !loading" class="empty-state">No orders match this branch and date range.</div>
    <nav class="history-pagination" aria-label="History pages"><button class="btn" :disabled="loading || page <= 1" @click="page--; load()">Previous</button><span>Page {{ page }} of {{ lastPage }}</span><button class="btn" :disabled="loading || page >= lastPage" @click="page++; load()">Next</button></nav>
  </section>
</template>
<script>
import axios from 'axios';
function dateString(value) { return `${value.getFullYear()}-${String(value.getMonth()+1).padStart(2,'0')}-${String(value.getDate()).padStart(2,'0')}`; }
export default {
  props: ['config'],
  data() { const today = new Date(), start = new Date(); start.setDate(start.getDate()-6); const params = new URLSearchParams(window.location.search); return {from: params.get('from') || dateString(start), to: params.get('to') || dateString(today), sort: this.config.abilities.reports && params.get('sort') === 'fastest' ? 'fastest' : 'newest', page: 1, lastPage: 1, total: 0, orders: [], loading: false, error: '', sequence: 0}; },
  mounted() { this.load(); },
  beforeDestroy() { this.sequence++; },
  methods: {
    timestamp(value) { return value ? new Date(value).toLocaleString('en-PH', {month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'}) : '—'; },
    duration(seconds) { if (seconds === null || seconds === undefined) return '—'; const s = Number(seconds); return s >= 3600 ? `${Math.floor(s/3600)}h ${Math.floor((s%3600)/60)}m ${s%60}s` : `${Math.floor(s/60)}m ${s%60}s`; },
    statusName(status) { return {new:'New order',preparing:'Preparing',ready:'Ready',served:'Served',cancelled:'Cancelled',on_the_way:'With rider',arrived:'At customer'}[status] || status; },
    typeName(type) { return {dine_in:'Dine-in',delivery:'Delivery',pickup:'Pickup'}[type] || type; },
    async load() { const sequence = ++this.sequence; this.loading = true; this.error = ''; try { const response = await axios.get(this.config.base + '/history', {params:{branch_id:this.config.branch.id,from:this.from,to:this.to,sort:this.sort,page:this.page},headers:{Accept:'application/json'}}); if (sequence !== this.sequence) return; this.orders = response.data.orders; this.page = response.data.page; this.lastPage = response.data.last_page; this.total = response.data.total; } catch (e) { if (sequence === this.sequence) this.error = e.response?.data?.message || 'History could not load. Apply the filters to retry.'; } finally { if (sequence === this.sequence) this.loading = false; } },
  },
};
</script>
