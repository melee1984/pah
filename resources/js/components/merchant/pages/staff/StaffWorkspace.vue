<template>
  <div class="staff-shell" :class="{ kitchen: view === 'kitchen', 'display-mode': fullscreen, 'pos-workspace': view === 'pos', 'cashier-workspace': config.abilities.cashier && view !== 'kitchen' }">
    <div v-show="!fullscreen" class="staff-shared-sidebar"><slot name="sidebar" :view="view"></slot></div>

    <main class="main">
      <header class="topbar staff-header" :aria-label="workspaceTitle + ' header'">
        <div class="staff-header-identity">
          <div class="staff-header-copy"><strong class="staff-header-title">{{ workspaceTitle }}</strong><small class="staff-header-context">{{ config.branch.name }}{{ config.branch.city ? ' · ' + config.branch.city : '' }}</small></div>
        </div>
        <div class="top-right">
          <select v-if="config.branches.length > 1" aria-label="Branch" class="branch-select" :value="config.branch.id" :disabled="!!busy || posBusy" @change="changeBranch($event.target.value)"><option v-for="branch in config.branches" :key="branch.id" :value="branch.id">{{ branch.address_1 }}{{ branch.city ? ' · ' + branch.city : '' }}</option></select>
          <span class="live" :class="{ 'connection-error': !!loadError }"><span class="dot"></span>{{ loadError ? 'Reconnecting' : loading ? 'Refreshing' : 'Live orders' }}</span>
          <span class="clock muted timestamp">{{ clock }}</span>
          <a class="logout" :href="config.logout">Log out</a>
        </div>
      </header>
      <div v-show="!paymentBill" class="content">
        <nav v-if="config.abilities.cashier && view !== 'kitchen' && !fullscreen" class="staff-workspace-tabs" aria-label="Cashier tools"><button v-for="tab in cashierTabs" :key="tab.id" class="btn" :class="{primary: view === tab.id}" @click="changeView(tab.id)">{{ tab.title }}</button></nav>
        <div v-if="view !== 'pos'" class="page-title">
          <div><div class="eyebrow" style="color:#9a9da5;margin-bottom:7px">{{ view === 'kitchen' ? 'KITCHEN OPERATIONS' : view === 'history' ? 'ORDER HISTORY' : 'CASHIER OPERATIONS' }}</div><h1>{{ pageTitle }}</h1><p>{{ view === 'kitchen' ? 'Prepare delivery, pickup and dine-in orders for this branch.' : view === 'history' ? 'Review recorded serving times and payment status for this branch.' : 'Manage tables, review open bills, and record received payments for this branch.' }}</p></div>
          <div class="actions">
            <template v-if="view === 'kitchen'">
              <select v-model="kitchenTable" aria-label="Filter kitchen by table" class="station-select"><option value="all">All orders</option><option v-for="table in kitchenTables" :key="table.id" :value="String(table.id)">{{ table.name }}</option></select>
              <button class="iconbtn" :aria-label="sound ? 'Mute sound alerts' : 'Enable sound alerts'" :title="sound ? 'Mute sound alerts' : 'Enable sound alerts'" :aria-pressed="sound" @click="toggleSound"><staff-icon name="volume" /></button>
              <button class="btn kitchen-fullscreen" :aria-label="fullscreen ? 'Exit full screen' : 'Full screen'" @click="toggleFullscreen"><staff-icon name="expand" />{{ fullscreen ? 'Exit full screen' : 'Full screen' }}</button>
            </template>
            <button class="btn" :disabled="loading || !!busy" @click="view === 'history' ? $refs.history.load() : load()"><staff-icon name="clock" />Refresh</button>
          </div>
        </div>
        <div v-if="loadError" class="banner error" role="alert"><staff-icon name="help" />{{ loadError }}<button @click="load">Retry</button></div>
        <div v-if="actionError" class="banner error" role="alert"><staff-icon name="help" />{{ actionError }}<button aria-label="Dismiss error" @click="actionError = ''"><staff-icon name="close" /></button></div>

        <template v-if="view === 'kitchen'">
          <div class="k-stats"><span>Open tickets <b>{{ kitchenTickets.length }}</b></span><span>Items to prepare <b>{{ preparationItems }}</b></span><span>Service target <b>15 min</b></span></div>
          <div class="k-columns">
            <section v-for="column in columns" :key="column.id" class="column" :aria-label="column.title">
              <div class="column-head"><span class="dot" :style="{color: column.color}"></span>{{ column.title }}<span class="number">{{ ticketsFor(column.id).length }}</span><small>{{ column.subtitle }}</small></div>
              <article v-for="order in ticketsFor(column.id)" :key="order.id" class="k-ticket" :class="{overdue: age(order) >= 15 && order.status !== 'ready'}" :data-order-id="order.id">
                <div class="k-ticket-top"><div><h2>{{ order.type === 'dine_in' ? order.table_name : typeLabel(order) }}</h2><small>#{{ order.number }} · {{ typeLabel(order) }}</small><span class="k-ticket-state" :class="order.paid ? 'is-paid' : 'is-unpaid'">{{ order.paid ? 'Paid' : 'Unpaid' }} · {{ label(order.status) }}</span></div><span class="timer" :class="timerClass(order)" :aria-label="`${age(order)} minutes since order, submitted at ${time(order.created)}`"><staff-icon name="clock" /><span><strong>{{ age(order) }} min</strong><small>{{ time(order.created) }}</small></span></span></div>
                <div class="k-items"><div v-for="(item, index) in order.items" :key="index" class="k-item"><span class="qty">{{ item.qty }}×</span><div class="k-item-body"><strong>{{ item.name }}</strong><div v-if="variantOptions(item).length" class="k-variants" aria-label="Selected variants"><span v-for="(variant, variantIndex) in variantOptions(item)" :key="`${variant}-${variantIndex}`" class="k-variant">+ {{ variant }}</span></div><p v-if="item.note"><b>Note:</b> {{ item.note }}</p></div></div></div>
                <div v-if="order.notes" class="order-notes"><b>Order notes:</b> {{ order.notes }}</div>
                <div class="k-ticket-foot"><button v-if="canAdvance(order)" class="btn" :class="{primary: order.status === 'new', readybtn: order.status === 'preparing', servebtn: order.status === 'ready'}" :disabled="!!busy" @click="advance(order)"><staff-icon :name="order.status === 'new' ? 'chef' : 'check'" />{{ busy === 'order-' + order.id ? 'Updating…' : actionLabel(order) }}</button><p v-else class="bill-note" :class="{'served-note': order.status === 'served'}">{{ order.status === 'served' ? 'Service complete' : order.type === 'delivery' && order.status === 'ready' ? 'Ready for rider handoff' : 'Waiting for the kitchen team' }}</p></div>
              </article>
              <div v-if="!ticketsFor(column.id).length" class="blank-column"><staff-icon name="check" /><p style="margin-top:12px">{{ column.empty }}</p></div>
            </section>
          </div>
        </template>

        <staff-history v-else-if="view === 'history'" ref="history" :config="config" @receipt="receipt" @collect="collectHistorical" />
        <staff-pos v-else-if="view === 'pos' && config.abilities.pos" :data="data" :endpoint="endpoint('/orders')" :disabled="!!busy" :fullscreen="fullscreen" @toggle-fullscreen="toggleFullscreen" @created="posCreated" @receipt="receipt" @busy="posBusy = $event" />
        <template v-else>
          <div class="stats">
            <article v-for="stat in stats" :key="stat.title" class="stat"><div class="stat-head">{{ stat.title }}<div class="stat-icon"><staff-icon :name="stat.icon" /></div></div><div class="stat-number">{{ stat.value }}<span v-if="stat.suffix" style="font-size:13px;font-weight:400;color:#afb1b7">{{ stat.suffix }}</span></div><div class="stat-foot">{{ stat.detail }}</div></article>
          </div>
          <div class="workgrid">
            <section v-if="view === 'overview' || view === 'tables'" class="panel">
              <div class="panel-title"><div><h2>Your floor, at a glance</h2><p>Select a table to view its orders and bill.</p></div><span class="badge served">Main dining</span></div>
              <div class="toolbar"><div class="tabs"><button v-for="f in ['all', 'occupied', 'available']" :key="f" class="tab" :class="{active: tableFilter === f}" @click="tableFilter = f">{{ capitalize(f) }}</button></div><label class="search"><staff-icon name="search" /><input v-model="query" aria-label="Search tables" placeholder="Find a table…"></label></div>
              <div class="tables"><button v-for="table in filteredTables" :key="table.id" class="table-card" :class="{empty: !tableOrders(table.id).length, selected: selected === table.id}" :aria-label="table.name + ', ' + label(tableStatus(table.id))" :aria-pressed="selected === table.id" @click="externalBillId = null; selected = table.id"><div class="table-top"><span class="table-name">{{ table.name }}</span><span class="badge" :class="tableStatus(table.id)">{{ label(tableStatus(table.id)) }}</span></div><div class="seat"><staff-icon name="table" /></div><template v-if="tableOrders(table.id).length"><div class="metadata"><span>{{ tableItemCount(table.id) }} {{ tableItemCount(table.id) === 1 ? 'item' : 'items' }} · {{ tableOrders(table.id).length }} {{ tableOrders(table.id).length === 1 ? 'round' : 'rounds' }}</span><span>{{ tableAge(table.id) }} min</span></div><div class="amount">{{ data.bills.some(b => b.table_id === table.id) ? money(tableTotal(table.id)) : 'Paid' }}<small>{{ data.bills.some(b => b.table_id === table.id) ? 'Open bill' : 'Kitchen work in progress' }}</small></div></template><template v-else><div class="empty-caption">{{ table.active && table.is_available ? 'Ready for your next guests' : 'Ordering paused' }}</div><div class="amount"><small>{{ table.capacity }} seats</small><small>No open bill</small></div></template></button></div>
              <div v-if="!filteredTables.length" class="empty-state">{{ data.tables.length ? 'No matching tables.' : 'No dining tables configured for this branch.' }}</div>
              <div class="legend"><span v-for="status in ['new', 'preparing', 'ready', 'served']" :key="status"><i class="dot" :style="{color: colors[status]}"></i>{{ label(status) }}</span></div>
            </section>
            <section v-else-if="view === 'orders'" class="panel"><div class="panel-title"><div><h2>Order activity</h2><p>Every round, from arrival to the table.</p></div><span class="badge served">{{ data.orders.length }} orders</span></div><div class="toolbar"><div class="tabs"><button v-for="f in ['all', 'new', 'preparing', 'ready', 'served', 'cancelled']" :key="f" class="tab" :class="{active: orderFilter === f}" @click="orderFilter = f">{{ f === 'all' ? 'All orders' : capitalize(f) }}</button></div></div><div class="order-list"><table><thead><tr><th>Order / Table</th><th>Items</th><th>Status</th><th>Total</th><th>Bill</th></tr></thead><tbody><tr v-for="order in filteredOrders" :key="order.id"><td>#{{ order.number }}<small style="display:block;margin-top:4px">{{ order.table_name }} · Round {{ order.round }}</small></td><td>{{ itemCount(order) }} items<small style="display:block;margin-top:4px">{{ age(order) }} min ago</small></td><td><span class="badge" :class="order.status">{{ order.paid ? 'Paid · ' : 'Unpaid · ' }}{{ label(order.status) }}</span></td><td>{{ money(order.total) }}</td><td><button v-if="!order.paid && order.bill_id" class="textbtn" @click="selectOrderBill(order)">View bill →</button><button v-else-if="order.paid" class="textbtn" @click="receipt(order.bill_id)">Receipt →</button><span v-else class="muted">—</span></td></tr></tbody></table><p v-if="!filteredOrders.length" class="empty-state">No orders in this view.</p></div></section>
            <section v-else class="panel"><div class="panel-title"><div><h2>Payments</h2><p>Today's recorded payments for this branch.</p></div></div><div v-if="data.payments.length" class="order-list"><table><thead><tr><th>Table</th><th>Method</th><th>Amount</th><th>Recorded</th><th>Receipt</th></tr></thead><tbody><tr v-for="payment in data.payments" :key="payment.id"><td>{{ payment.table_id ? tableName(payment.table_id) : 'Order #' + (payment.order_number || payment.bill_id) }}</td><td>{{ payment.method }}<small v-if="payment.cash_tendered !== null" class="payment-cash-detail">Tendered {{ money(payment.cash_tendered) }} · Change {{ money(payment.change) }}</small></td><td>{{ money(payment.amount) }}</td><td>{{ time(payment.created) }}</td><td><button class="textbtn" :disabled="!!busy" @click="receipt(payment.bill_id)">Print receipt</button></td></tr></tbody></table></div><div v-else class="empty-state payment-empty"><staff-icon name="wallet" /><p>No payments recorded today.</p><p style="font-size:11px;margin-top:8px">Select an occupied table to review its bill.</p><button class="btn" style="margin-top:20px" @click="changeView('overview')">View tables</button></div></section>

            <aside>
              <section class="panel">
                <template v-if="selectedTable">
                  <div class="ticket-head"><div class="eyebrow">{{ selectedBill && selectedOrders.length ? 'Current bill' : 'Table details' }}</div><h2>{{ selectedTable.name }}<span class="badge" :class="selectedStatus" style="float:right;margin-top:3px">{{ label(selectedStatus) }}</span></h2><div class="sub"><span>{{ selectedTable.capacity }} seats</span><span>{{ selectedOrders.length }} order {{ selectedOrders.length === 1 ? 'round' : 'rounds' }}</span></div></div>
                  <template v-if="selectedBill && selectedOrders.length">
                    <div class="bill-list"><div v-for="order in selectedOrders" :key="order.id"><div class="round"><span>Round {{ order.round }} · #{{ order.number }}</span><span>{{ order.status === 'served' ? 'Served' : label(order.status) }} · {{ order.payment_method || 'No payment selected' }}</span></div><div v-for="(item, index) in order.items" :key="index" class="bill-item"><span class="qty">{{ item.qty }}×</span><div class="name">{{ item.name }}<small v-if="item.variants" class="variants">{{ item.variants }}</small><small v-if="item.note">{{ item.note }}</small></div><span class="price">{{ money(item.qty * item.unit_price) }}</span></div></div></div>
                    <div class="bill-totals"><div class="row"><span>Subtotal</span><span>{{ money(selectedBill.totals.sub_total) }}</span></div><div v-if="billFees(selectedBill)" class="row"><span>Fees</span><span>{{ money(billFees(selectedBill)) }}</span></div><div v-if="selectedBill.totals.discount" class="row"><span>Discount</span><span>−{{ money(selectedBill.totals.discount) }}</span></div><div class="row total"><span>Total due</span><span>{{ money(selectedBill.totals.total) }}</span></div><div class="payment-preference"><span><small>Customer selected</small><strong>{{ selectedBill.payment_method || 'No payment preference' }}</strong></span><span class="payment-state unpaid">Not yet paid</span></div></div>
                    <div class="bill-actions"><button v-for="order in selectedOrders.filter(o => o.status === 'ready' && o.type !== 'delivery' && config.abilities.serve)" :key="order.id" class="btn" :disabled="!!busy" @click="advance(order)"><staff-icon name="check" />Mark #{{ order.number }} served</button><button v-if="config.abilities.pay" class="btn primary" :disabled="!!busy || !data.payment_methods.length" @click="openPayment"><staff-icon name="wallet" />Proceed to Payment</button><button class="btn" @click="printOpenBill"><staff-icon name="print" />Print bill</button><div class="bill-note">Bill #{{ selectedBill.id }} · {{ allServed ? 'Ready for payment' : 'Payment and kitchen service are tracked separately' }}</div></div>
                  </template>
                  <div v-else-if="selectedOrders.length" class="paid-table-orders"><p>Paid orders in preparation</p><article v-for="order in selectedOrders" :key="order.id"><strong>#{{ order.number }}</strong><span>{{ order.paid ? 'Paid' : 'Unpaid' }} · {{ label(order.status) }}</span><button v-if="order.status === 'ready' && config.abilities.serve" class="btn" :disabled="!!busy" @click="advance(order)">Mark served</button></article></div>
                  <div v-else class="empty-state"><staff-icon name="table" /><p style="margin-top:12px">{{ selectedTable.active && selectedTable.is_available ? 'Ready for your next guests.' : 'Ordering is paused for this table.' }}</p><small>Customers can order using this table's QR code.</small></div>
                  <div v-if="selectedBill && !selectedOrders.length && config.abilities.tables" class="bill-actions"><button class="btn" :disabled="!!busy" @click="releaseBill">Release cancelled bill</button></div>
                  <div v-if="view === 'tables' && config.abilities.tables && selected !== null" class="table-controls"><button class="btn" :disabled="!!busy || !selectedTable.active" @click="toggleTable">{{ selectedTable.is_available ? 'Pause ordering for this table' : 'Enable ordering for this table' }}</button></div>
                </template>
                <div v-else class="empty-state">Select a table to view its bill.</div>
              </section>
              <section class="panel requests"><h3>Ready at the pass <span class="badge ready">{{ readyTickets.length }}</span></h3><div v-for="order in readyTickets" :key="order.id" class="request"><div class="request-icon"><staff-icon name="bell" /></div><div><b>{{ order.table_name }}</b><small>#{{ order.number }} · {{ itemCount(order) }} items</small></div><button :disabled="!!busy" :aria-label="'View ' + order.table_name + ' bill'" @click="selectOrderBill(order)">View bill →</button></div><small v-if="!readyTickets.length">Ready dishes will appear here.</small></section>
            </aside>
          </div>
        </template>
        <div class="footer"><span>pahatud. <span style="margin-left:7px">Good food. Smooth service.</span></span><span>Updated {{ time(data.updated_at) }} · {{ config.branch.name }}</span></div>
      </div>
    <staff-checkout v-if="paymentBill" class="content" :bill="paymentBill" :methods="data.payment_methods" :preferred="paymentBill.payment_method_id" :endpoint="endpoint('/bills/' + paymentBill.id + '/payment')" @back="cancelPayment" @done="cancelPayment" @busy="busy = $event ? 'payment' : ''" @paid="paymentSaved" @receipt="receipt" />
    </main>

    <dialog ref="receiptDialog" aria-labelledby="receipt-heading">
      <div class="modal-top"><h2 id="receipt-heading">{{ printData && printData.paid ? 'Payment receipt' : 'Table bill' }}</h2><button aria-label="Close receipt" @click="$refs.receiptDialog.close()"><staff-icon name="close" /></button></div>
      <div class="receipt-settings"><label for="receipt-paper">Receipt paper</label><select id="receipt-paper" v-model="receiptPaper"><option value="80">80 mm thermal</option><option value="58">58 mm thermal</option></select><p>Select the same paper size in your printer settings.</p></div>
      <div v-if="printData" class="receipt-preview"><staff-receipt :receipt="printData" :merchant="config.merchant" :branch="config.branch.name" :paper="receiptPaper" /></div>
      <div class="modal-bottom"><button class="btn" @click="$refs.receiptDialog.close()">Close</button><button class="btn primary receipt-print-button" @click="printReceipt"><staff-icon name="print" />Print receipt</button></div>
    </dialog>
    <div id="toast" role="status" :class="{visible: !!toastMessage}">{{ toastMessage }}</div>
    <section v-if="printData" class="print-receipt"><staff-receipt :receipt="printData" :merchant="config.merchant" :branch="config.branch.name" :paper="receiptPaper" /></section>
  </div>
</template>

<script>
import axios from 'axios';
import paths from './icons';
import StaffPos from './StaffPos.vue';
import StaffReceipt from './StaffReceipt.vue';
import StaffHistory from './StaffHistory.vue';
import StaffCheckout from './StaffCheckout.vue';

const StaffIcon = { functional: true, props: ['name'], render(h, context) { return h('svg', { attrs: {viewBox: '0 0 24 24', 'aria-hidden': 'true'} }, [h('path', {attrs: {d: paths[context.props.name] || paths.orders}})]); } };

export default {
  components: { StaffIcon, StaffPos, StaffHistory, StaffCheckout, StaffReceipt },
  props: { config: { type: Object, required: true } },
  data() { return {
    data: this.config.initial, view: this.config.view, selected: null, externalBillId: null, query: '', tableFilter: 'all', orderFilter: 'all', kitchenTable: 'all',
    sound: false, fullscreen: false, immersive: false, now: Date.now(), loading: false, posBusy: false, busy: '', loadError: '', actionError: '', toastMessage: '',
    paymentBill: null, paymentReturnFocus: null, printData: null, receiptPaper: '80',
    sequence: 0, timer: null, toastTimer: null,
    tabs: [{id: 'pos', title: 'New order / POS', icon: 'orders'}, {id: 'overview', title: 'Overview', icon: 'grid'}, {id: 'orders', title: 'Orders', icon: 'orders'}, {id: 'tables', title: 'Tables', icon: 'table'}, {id: 'payments', title: 'Payments', icon: 'wallet'}, {id: 'history', title: 'History', icon: 'clock'}],
    colors: {new: '#dba044', preparing: '#6c8bd1', ready: '#3ba582', served: '#a7adb4'},
    columns: [{id: 'new', title: 'New orders', subtitle: 'Next up', color: '#d49b3e', empty: 'No new orders. All caught up.'}, {id: 'preparing', title: 'In preparation', subtitle: 'On the fire', color: '#6388cc', empty: 'Your prep queue is clear.'}, {id: 'ready', title: 'Ready to serve', subtitle: 'At the pass', color: '#2c9d7a', empty: 'Ready dishes will appear here.'}, {id: 'served', title: 'Served', subtitle: 'Awaiting payment', color: '#7c838c', empty: 'Served orders will appear here.'}],
  }; },
  computed: {
    initials() { return this.config.user.name.split(' ').filter(Boolean).slice(0, 2).map(s => s[0]).join(''); },
    clock() { return new Date(this.now).toLocaleTimeString('en-PH', {hour: '2-digit', minute: '2-digit'}); },
    cashierTabs() { return this.config.abilities.cashier ? this.tabs.filter(t => t.id !== 'pos' || this.config.abilities.pos) : []; },
    workspaceTitle() { if (this.view === 'history') return 'Order history'; return this.view === 'kitchen' ? 'Kitchen Display' : 'Cashier Dashboard'; },
    pageTitle() { return {history: 'Order history & serving times', pos: 'New POS order', overview: 'Cashier Dashboard', tables: 'Table management', orders: 'Live orders', payments: 'Payments & bills', kitchen: 'Kitchen Display'}[this.view]; },
    openTickets() { return this.data.orders.filter(o => ['new', 'preparing', 'ready'].includes(o.status)); },
    readyTickets() { return this.openTickets.filter(o => o.status === 'ready'); },
    kitchenTickets() { return this.data.orders.filter(o => ['new', 'preparing', 'ready', 'served'].includes(o.status) && !(o.paid && o.status === 'served') && (this.kitchenTable === 'all' || String(o.table_id) === this.kitchenTable)); },
    kitchenTables() { return [...new Map(this.data.orders.filter(o => o.table_id && ['new', 'preparing', 'ready', 'served'].includes(o.status) && !(o.paid && o.status === 'served')).map(o => [o.table_id, {id: o.table_id, name: o.table_name}])).values()]; },
    preparationItems() { return this.kitchenTickets.filter(o => ['new', 'preparing'].includes(o.status)).reduce((n, o) => n + this.itemCount(o), 0); },
    filteredTables() { return this.data.tables.filter(t => (this.tableFilter === 'all' || (this.tableFilter === 'occupied' ? this.tableOrders(t.id).length : !this.tableOrders(t.id).length)) && t.name.toLowerCase().includes(this.query.toLowerCase())); },
    filteredOrders() { return this.data.orders.filter(o => this.orderFilter === 'all' || this.orderFilter === o.status).slice().reverse(); },
    selectedStatus() { return ['new', 'preparing', 'ready', 'served'].find(status => this.selectedOrders.some(o => o.status === status)) || 'available'; },
    selectedTable() { return this.externalBillId ? {name: this.selectedBill?.name || 'Order bill', capacity: '—'} : this.data.tables.find(t => t.id === this.selected); },
    selectedBill() { return this.data.bills.find(b => this.externalBillId ? b.id === this.externalBillId : b.table_id === this.selected); },
    selectedOrders() { return (this.externalBillId ? this.data.orders.filter(o => this.selectedBill?.order_ids.includes(o.id)) : this.selectedBill ? this.data.orders.filter(o => this.selectedBill.order_ids.includes(o.id)) : this.tableOrders(this.selected)).slice().sort((a, b) => a.round - b.round); },
    allServed() { return this.selectedOrders.length > 0 && this.selectedOrders.every(o => o.status === 'served' || (o.type === 'delivery' && o.status === 'ready')); },
    stats() {
      const occupied = this.data.tables.filter(t => this.tableOrders(t.id).length).length;
      return [{title: 'Active tables', value: occupied, suffix: ' / ' + this.data.tables.length, icon: 'table', detail: (this.data.tables.length - occupied) + ' tables without active orders'}, {title: 'In the kitchen', value: this.openTickets.filter(o => o.status !== 'ready').length, icon: 'chef', detail: 'New & preparing orders'}, {title: 'Ready to serve', value: this.readyTickets.length, icon: 'bell', detail: 'Waiting for your team'}, {title: 'Collected today', value: this.money(this.data.payments.reduce((n, p) => n + p.amount, 0)), icon: 'wallet', detail: this.data.payments.length + (this.data.payments.length === 1 ? ' payment recorded' : ' payments recorded')}];
    },
  },
  mounted() {
    this.selectDefault();
    document.title = this.workspaceTitle + ' | ' + this.config.merchant;
    this.timer = setInterval(() => { this.now = Date.now(); if (!document.hidden && !this.busy) this.load(); }, 5000);
    document.addEventListener('fullscreenchange', this.fullscreenChanged);
    document.addEventListener('keydown', this.exitDisplayMode);
  },
  beforeDestroy() { clearInterval(this.timer); clearTimeout(this.toastTimer); document.removeEventListener('fullscreenchange', this.fullscreenChanged); document.removeEventListener('keydown', this.exitDisplayMode); },
  methods: {
    typeLabel(order) { return {dine_in: 'Dine-in', delivery: 'Delivery', pickup: 'Pickup'}[order.type] || 'Delivery'; },
    selectOrderBill(order) { this.externalBillId = order.table_id ? null : order.bill_id; this.selected = order.table_id; },
    async collectHistorical(order) { this.selectOrderBill(order); await this.openPayment(); },
    async posCreated() { await this.load(); },
    money(value) { return '₱' + (Number(value || 0) / 100).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
    capitalize(value) { return value[0].toUpperCase() + value.slice(1); },
    label(status) { return {new: 'New order', preparing: 'Preparing', ready: 'Ready to serve', served: 'Served', available: 'Available', cancelled: 'Cancelled', on_the_way: 'On the way', arrived: 'At customer'}[status] || status; },
    time(value) { return new Date(value).toLocaleTimeString('en-PH', {hour: '2-digit', minute: '2-digit'}); },
    age(order) { return Math.max(0, Math.floor((this.now - new Date(order.created).getTime()) / 60000)); },
    timerClass(order) { const minutes = this.age(order); return minutes >= 15 ? 'timer-late' : minutes >= 10 ? 'timer-warning' : 'timer-fresh'; },
    variantOptions(item) { return Array.isArray(item.variant_options) && item.variant_options.length ? item.variant_options : String(item.variants || '').split(',').map(value => value.trim()).filter(Boolean); },
    itemCount(order) { return order.items.reduce((n, item) => n + item.qty, 0); },
    tableItemCount(id) { return this.tableOrders(id).reduce((n, o) => n + this.itemCount(o), 0); },
    tableAge(id) { return Math.max(0, ...this.tableOrders(id).map(o => this.age(o))); },
    tableOrders(id) { const bill = this.data.bills.find(b => b.table_id === id); return this.data.orders.filter(o => (bill && bill.order_ids.includes(o.id)) || (o.table_id === id && ['new', 'preparing', 'ready'].includes(o.status))); },
    tableTotal(id) { const bill = this.data.bills.find(b => b.table_id === id); return bill ? bill.totals.total : 0; },
    tableStatus(id) { const orders = this.tableOrders(id); return ['new', 'ready', 'preparing', 'served'].find(s => orders.some(o => o.status === s)) || 'available'; },
    tableName(id) { if (!id) return 'POS order'; const table = this.data.tables.find(t => t.id === id); return table ? table.name : 'Table #' + id; },
    billFees(bill) { return bill.totals.delivery_fee + bill.totals.convenience_fee - bill.totals.vat_amount; },
    ticketsFor(status) { return this.kitchenTickets.filter(o => o.status === status); },
    canAdvance(order) { return order.status === 'ready' ? order.type !== 'delivery' && this.config.abilities.serve : ['new', 'preparing'].includes(order.status) && this.config.abilities.prepare; },
    actionLabel(order) { if (order.status === 'ready' && order.type === 'pickup') return 'Complete pickup'; return {new: 'Start preparing', preparing: 'Mark ready', ready: 'Mark served'}[order.status]; },
    selectDefault() { if (this.externalBillId && !this.data.bills.some(b => b.id === this.externalBillId)) this.externalBillId = null; if (!this.data.tables.some(t => t.id === this.selected)) this.selected = (this.data.tables.find(t => this.tableOrders(t.id).length) || this.data.tables[0] || {}).id || null; },
    changeView(view) { if (this.busy || this.posBusy || this.paymentBill) return; this.view = view; document.title = this.workspaceTitle + ' | ' + this.config.merchant; this.actionError = ''; const url = new URL(window.location.href); url.searchParams.set('view', view); window.history.replaceState({}, '', url); },
    changeBranch(id) { const params = new URLSearchParams({branch_id: id, view: this.view}); if (this.view === 'history' && this.$refs.history) { const history = this.$refs.history; params.set('from', history.from); params.set('to', history.to); params.set('sort', history.sort); } window.location.href = this.config.base + '?' + params.toString(); },
    endpoint(suffix = '') { return this.config.base + suffix + '?branch_id=' + encodeURIComponent(this.config.branch.id); },
    message(error) { if (error.response && [401, 419].includes(error.response.status)) return 'Your session has expired. Log in again to continue.'; const data = error.response && error.response.data; return data && (data.errors ? Object.values(data.errors).flat()[0] : data.message) || 'Unable to connect. Please try again.'; },
    async load() {
      if (this.loading) return;
      this.loading = true; const sequence = ++this.sequence;
      try {
        const response = await axios.get(this.config.endpoint, {params: {branch_id: this.config.branch.id}, headers: {Accept: 'application/json'}});
        if (sequence !== this.sequence) return;
        if (response.data.abilities && JSON.stringify(response.data.abilities) !== JSON.stringify(this.config.abilities)) { window.location.href = this.config.base; return; }
        const previous = new Set(this.data.orders.map(o => o.id));
        if (response.data.orders.some(o => o.status === 'new' && !previous.has(o.id))) this.beep();
        this.data = response.data; this.selectDefault(); this.loadError = ''; this.now = Date.now();
      } catch (error) { if (sequence !== this.sequence) return; if (error.response && [401, 403, 419].includes(error.response.status)) { window.location.href = this.config.logout; return; } this.loadError = 'Orders could not refresh. The last loaded orders are still shown.'; }
      finally { if (sequence === this.sequence) this.loading = false; }
    },
    async request(key, method, url, input) {
      if (this.busy) return false;
      this.busy = key; this.actionError = ''; ++this.sequence;
      try {
        await axios({method, url, data: input, headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json'}});
        return true;
      } catch (error) {
        this.actionError = this.message(error);
        return false;
      } finally { this.busy = ''; this.loading = false; await this.load(); }
    },
    async advance(order) {
      const action = {new: 'preparing', preparing: 'ready', ready: 'served'}[order.status];
      if (await this.request('order-' + order.id, 'post', this.endpoint('/orders/' + order.id + '/transition'), {action, expected_status: order.status_id})) { this.toast('Order #' + order.number + ' updated.'); if (action === 'ready') this.beep(); }
    },
    async releaseBill() { if (await this.request('release', 'post', this.endpoint('/bills/' + this.selectedBill.id + '/release'), {})) this.toast('Cancelled bill closed. Table released.'); },
    async toggleTable() { if (await this.request('table', 'patch', this.endpoint('/tables/' + this.selected), {is_available: !this.selectedTable.is_available})) this.toast('Table ordering availability updated.'); },
    async openPayment() {
      if (this.busy || this.paymentBill) return;
      this.paymentReturnFocus = document.activeElement;
      await this.load();
      if (!this.selectedBill || !this.selectedOrders.length) return;
      this.paymentBill = JSON.parse(JSON.stringify({...this.selectedBill, table_name: this.selectedTable.name, orders: this.selectedOrders}));
    },
    cancelPayment() { if (this.busy) return; this.paymentBill = null; this.$nextTick(() => this.paymentReturnFocus?.focus()); },
    async paymentSaved() { this.toast('Payment recorded successfully. Kitchen preparation continues.'); await this.load(); if (this.view === 'history') this.$refs.history?.load(); },
    async printOpenBill() {
      if (!this.selectedBill) return;
      this.printData = {...JSON.parse(JSON.stringify(this.selectedBill)), table_name: this.selectedTable.name, orders: JSON.parse(JSON.stringify(this.selectedOrders)), paid: false};
      await this.$nextTick(); this.$refs.receiptDialog.showModal();
    },
    async receipt(id) {
      if (this.busy) return; this.busy = 'receipt';
      try { const response = await axios.get(this.endpoint('/bills/' + id + '/receipt'), {headers: {Accept: 'application/json'}}); this.printData = response.data; await this.$nextTick(); this.$refs.receiptDialog.showModal(); }
      catch (error) { this.actionError = this.message(error); }
      finally { this.busy = ''; }
    },
    async printReceipt() { this.$refs.receiptDialog.close(); await this.$nextTick(); window.print(); },
    toast(message) { this.toastMessage = message; clearTimeout(this.toastTimer); this.toastTimer = setTimeout(() => { this.toastMessage = ''; }, 4000); },
    toggleSound() { this.sound = !this.sound; if (this.sound) this.beep(); this.toast(this.sound ? 'Sound alerts enabled' : 'Sound alerts muted'); },
    beep() { if (!this.sound) return; try { const ac = new (window.AudioContext || window.webkitAudioContext)(); const osc = ac.createOscillator(); const gain = ac.createGain(); osc.connect(gain); gain.connect(ac.destination); osc.frequency.value = 820; gain.gain.value = 0.08; osc.start(); gain.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + 0.5); osc.stop(ac.currentTime + 0.5); osc.onended = () => ac.close(); } catch (error) { /* Sound is optional. */ } },
    fullscreenChanged() { this.fullscreen = !!document.fullscreenElement || this.immersive; },
    exitDisplayMode(event) { if (event.key === 'Escape' && this.immersive) { this.immersive = false; this.fullscreen = false; } },
    async toggleFullscreen() {
      if (this.immersive) { this.immersive = false; this.fullscreen = false; return; }
      try { if (document.fullscreenElement) await document.exitFullscreen(); else await this.$el.requestFullscreen(); }
      catch (error) { this.immersive = true; this.fullscreen = true; this.toast('Display mode enabled. Press Escape to exit.'); }
    },
  },
};
</script>
