<template>
  <div class="staff-shell" :class="{ kitchen: view === 'kitchen', 'display-mode': fullscreen }">
    <aside v-if="!fullscreen" class="sidebar">
      <a class="restaurant-brand" :href="config.dashboard || config.base"><img class="restaurant-brand-logo" :src="config.logo" alt="Pahatud" width="38" height="38"><span class="restaurant-brand-copy"><strong :title="config.merchant">{{ config.merchant }}</strong><small>Merchant partner</small></span></a>
      <div class="workspace-label">RESTAURANT WORKSPACE</div>
      <nav aria-label="Dining workspace">
        <button v-for="tab in cashierTabs" :key="tab.id" class="nav" :class="{ active: view === tab.id }" :title="tab.title" :aria-label="tab.title" @click="changeView(tab.id)"><staff-icon :name="tab.icon" /><span>{{ tab.title }}</span><span v-if="tab.id === 'orders'" class="count">{{ openTickets.length }}</span></button>
        <div v-if="config.abilities.cashier" class="divider"></div>
        <button class="nav" :class="{ active: view === 'kitchen' }" :aria-current="view === 'kitchen' ? 'page' : null" title="Kitchen display" @click="changeView('kitchen')"><staff-icon name="chef" /><span>Kitchen display</span></button>
        <a v-if="config.dashboard" class="nav" :href="config.dashboard" title="Merchant dashboard"><staff-icon name="back" /><span>Merchant dashboard</span></a>
      </nav>
      <div class="switch-card"><staff-icon name="chef" /><p>Made for a busy kitchen.</p><small>Big tickets. Clear instructions.<br>Every order in one place.</small><button @click="changeView('kitchen')">Open kitchen display ↗</button></div>
      <div class="sidebar-foot"><div class="avatar">{{ initials }}</div><div><b>{{ config.user.name }}</b><small>{{ config.user.role }}</small></div></div>
    </aside>

    <main class="main">
      <header class="topbar staff-header" :aria-label="workspaceTitle + ' header'">
        <div class="staff-header-identity">
          <div class="staff-header-copy"><strong class="staff-header-title">{{ workspaceTitle }}</strong><small class="staff-header-context">{{ config.branch.name }}{{ config.branch.city ? ' · ' + config.branch.city : '' }}</small></div>
        </div>
        <div class="top-right">
          <select v-if="config.branches.length > 1" aria-label="Branch" class="branch-select" :value="config.branch.id" :disabled="!!busy" @change="changeBranch($event.target.value)"><option v-for="branch in config.branches" :key="branch.id" :value="branch.id">{{ branch.address_1 }}{{ branch.city ? ' · ' + branch.city : '' }}</option></select>
          <span class="live" :class="{ 'connection-error': !!loadError }"><span class="dot"></span>{{ loadError ? 'Reconnecting' : loading ? 'Refreshing' : 'Live orders' }}</span>
          <span class="clock muted timestamp">{{ clock }}</span>
          <a class="logout" :href="config.logout">Log out</a>
        </div>
      </header>
      <div class="content">
        <div class="page-title">
          <div><div class="eyebrow" style="color:#9a9da5;margin-bottom:7px">{{ view === 'kitchen' ? 'KITCHEN OPERATIONS' : 'CASHIER OPERATIONS' }}</div><h1>{{ pageTitle }}</h1><p>{{ view === 'kitchen' ? 'Prepare incoming orders and move each ticket from the kitchen to the table.' : 'Manage tables, review open bills, and record received payments for this branch.' }}</p></div>
          <div class="actions">
            <template v-if="view === 'kitchen'">
              <select v-model="kitchenTable" aria-label="Filter kitchen by table" class="station-select"><option value="all">All tables</option><option v-for="table in kitchenTables" :key="table.id" :value="String(table.id)">{{ table.name }}</option></select>
              <button class="iconbtn" :aria-label="sound ? 'Mute sound alerts' : 'Enable sound alerts'" :title="sound ? 'Mute sound alerts' : 'Enable sound alerts'" :aria-pressed="sound" @click="toggleSound"><staff-icon name="volume" /></button>
              <button class="btn kitchen-fullscreen" :aria-label="fullscreen ? 'Exit full screen' : 'Full screen'" @click="toggleFullscreen"><staff-icon name="expand" />{{ fullscreen ? 'Exit full screen' : 'Full screen' }}</button>
            </template>
            <button class="btn" :disabled="loading || !!busy" @click="load"><staff-icon name="clock" />Refresh</button>
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
                <div class="k-ticket-top"><div><h2>{{ order.table_name }}</h2><small>#{{ order.number }} · Round {{ order.round }} · Dine-in</small></div><span class="timer" :class="timerClass(order)" :aria-label="`${age(order)} minutes since order, submitted at ${time(order.created)}`"><staff-icon name="clock" /><span><strong>{{ age(order) }} min</strong><small>{{ time(order.created) }}</small></span></span></div>
                <div class="k-items"><div v-for="(item, index) in order.items" :key="index" class="k-item"><span class="qty">{{ item.qty }}×</span><div class="k-item-body"><strong>{{ item.name }}</strong><div v-if="variantOptions(item).length" class="k-variants" aria-label="Selected variants"><span v-for="(variant, variantIndex) in variantOptions(item)" :key="`${variant}-${variantIndex}`" class="k-variant">+ {{ variant }}</span></div><p v-if="item.note"><b>Note:</b> {{ item.note }}</p></div></div></div>
                <div class="k-ticket-foot"><button v-if="canAdvance(order)" class="btn" :class="{primary: order.status === 'new', readybtn: order.status === 'preparing', servebtn: order.status === 'ready'}" :disabled="!!busy" @click="advance(order)"><staff-icon :name="order.status === 'new' ? 'chef' : 'check'" />{{ busy === 'order-' + order.id ? 'Updating…' : actionLabel(order) }}</button><p v-else class="bill-note" :class="{'served-note': order.status === 'served'}">{{ order.status === 'served' ? 'Served · awaiting payment or table close' : 'Waiting for the kitchen team' }}</p></div>
              </article>
              <div v-if="!ticketsFor(column.id).length" class="blank-column"><staff-icon name="check" /><p style="margin-top:12px">{{ column.empty }}</p></div>
            </section>
          </div>
        </template>

        <template v-else>
          <div class="stats">
            <article v-for="stat in stats" :key="stat.title" class="stat"><div class="stat-head">{{ stat.title }}<div class="stat-icon"><staff-icon :name="stat.icon" /></div></div><div class="stat-number">{{ stat.value }}<span v-if="stat.suffix" style="font-size:13px;font-weight:400;color:#afb1b7">{{ stat.suffix }}</span></div><div class="stat-foot">{{ stat.detail }}</div></article>
          </div>
          <div class="workgrid">
            <section v-if="view === 'overview' || view === 'tables'" class="panel">
              <div class="panel-title"><div><h2>Your floor, at a glance</h2><p>Select a table to view its orders and bill.</p></div><span class="badge served">Main dining</span></div>
              <div class="toolbar"><div class="tabs"><button v-for="f in ['all', 'occupied', 'available']" :key="f" class="tab" :class="{active: tableFilter === f}" @click="tableFilter = f">{{ capitalize(f) }}</button></div><label class="search"><staff-icon name="search" /><input v-model="query" aria-label="Search tables" placeholder="Find a table…"></label></div>
              <div class="tables"><button v-for="table in filteredTables" :key="table.id" class="table-card" :class="{empty: !tableOrders(table.id).length, selected: selected === table.id}" :aria-label="table.name + ', ' + label(tableStatus(table.id))" :aria-pressed="selected === table.id" @click="selected = table.id"><div class="table-top"><span class="table-name">{{ table.name }}</span><span class="badge" :class="tableStatus(table.id)">{{ label(tableStatus(table.id)) }}</span></div><div class="seat"><staff-icon name="table" /></div><template v-if="tableOrders(table.id).length"><div class="metadata"><span>{{ tableItemCount(table.id) }} {{ tableItemCount(table.id) === 1 ? 'item' : 'items' }} · {{ tableOrders(table.id).length }} {{ tableOrders(table.id).length === 1 ? 'round' : 'rounds' }}</span><span>{{ tableAge(table.id) }} min</span></div><div class="amount">{{ money(tableTotal(table.id)) }}<small>Open bill</small></div></template><template v-else><div class="empty-caption">{{ table.active && table.is_available ? 'Ready for your next guests' : 'Ordering paused' }}</div><div class="amount"><small>{{ table.capacity }} seats</small><small>No open bill</small></div></template></button></div>
              <div v-if="!filteredTables.length" class="empty-state">{{ data.tables.length ? 'No matching tables.' : 'No dining tables configured for this branch.' }}</div>
              <div class="legend"><span v-for="status in ['new', 'preparing', 'ready', 'served']" :key="status"><i class="dot" :style="{color: colors[status]}"></i>{{ label(status) }}</span></div>
            </section>
            <section v-else-if="view === 'orders'" class="panel"><div class="panel-title"><div><h2>Order activity</h2><p>Every round, from arrival to the table.</p></div><span class="badge served">{{ data.orders.length }} orders</span></div><div class="toolbar"><div class="tabs"><button v-for="f in ['all', 'new', 'preparing', 'ready', 'served', 'cancelled']" :key="f" class="tab" :class="{active: orderFilter === f}" @click="orderFilter = f">{{ f === 'all' ? 'All orders' : capitalize(f) }}</button></div></div><div class="order-list"><table><thead><tr><th>Order / Table</th><th>Items</th><th>Status</th><th>Total</th><th>Bill</th></tr></thead><tbody><tr v-for="order in filteredOrders" :key="order.id"><td>#{{ order.number }}<small style="display:block;margin-top:4px">{{ order.table_name }} · Round {{ order.round }}</small></td><td>{{ itemCount(order) }} items<small style="display:block;margin-top:4px">{{ age(order) }} min ago</small></td><td><span class="badge" :class="order.status">{{ order.paid ? 'Paid · ' : '' }}{{ label(order.status) }}</span></td><td>{{ money(order.total) }}</td><td><button v-if="!order.paid && order.bill_id" class="textbtn" @click="selected = order.table_id">View bill →</button><button v-else-if="order.paid" class="textbtn" @click="receipt(order.bill_id)">Receipt →</button><span v-else class="muted">—</span></td></tr></tbody></table><p v-if="!filteredOrders.length" class="empty-state">No orders in this view.</p></div></section>
            <section v-else class="panel"><div class="panel-title"><div><h2>Payments</h2><p>Today's recorded payments for this branch.</p></div></div><div v-if="data.payments.length" class="order-list"><table><thead><tr><th>Table</th><th>Method</th><th>Amount</th><th>Recorded</th><th>Receipt</th></tr></thead><tbody><tr v-for="payment in data.payments" :key="payment.id"><td>{{ tableName(payment.table_id) }}</td><td>{{ payment.method }}<small v-if="payment.cash_tendered !== null" class="payment-cash-detail">Tendered {{ money(payment.cash_tendered) }} · Change {{ money(payment.change) }}</small></td><td>{{ money(payment.amount) }}</td><td>{{ time(payment.created) }}</td><td><button class="textbtn" :disabled="!!busy" @click="receipt(payment.bill_id)">Print receipt</button></td></tr></tbody></table></div><div v-else class="empty-state payment-empty"><staff-icon name="wallet" /><p>No payments recorded today.</p><p style="font-size:11px;margin-top:8px">Select an occupied table to review its bill.</p><button class="btn" style="margin-top:20px" @click="changeView('overview')">View tables</button></div></section>

            <aside>
              <section class="panel">
                <template v-if="selectedTable">
                  <div class="ticket-head"><div class="eyebrow">{{ selectedBill && selectedOrders.length ? 'Current table bill' : 'Table details' }}</div><h2>{{ selectedTable.name }}<span class="badge" :class="tableStatus(selected)" style="float:right;margin-top:3px">{{ label(tableStatus(selected)) }}</span></h2><div class="sub"><span>{{ selectedTable.capacity }} seats</span><span>{{ selectedOrders.length }} order {{ selectedOrders.length === 1 ? 'round' : 'rounds' }}</span></div></div>
                  <template v-if="selectedBill && selectedOrders.length">
                    <div class="bill-list"><div v-for="order in selectedOrders" :key="order.id"><div class="round"><span>Round {{ order.round }} · #{{ order.number }}</span><span>{{ order.status === 'served' ? 'Served' : label(order.status) }} · {{ order.payment_method || 'No payment selected' }}</span></div><div v-for="(item, index) in order.items" :key="index" class="bill-item"><span class="qty">{{ item.qty }}×</span><div class="name">{{ item.name }}<small v-if="item.variants" class="variants">{{ item.variants }}</small><small v-if="item.note">{{ item.note }}</small></div><span class="price">{{ money(item.qty * item.unit_price) }}</span></div></div></div>
                    <div class="bill-totals"><div class="row"><span>Subtotal</span><span>{{ money(selectedBill.totals.sub_total) }}</span></div><div v-if="billFees(selectedBill)" class="row"><span>Fees</span><span>{{ money(billFees(selectedBill)) }}</span></div><div v-if="selectedBill.totals.discount" class="row"><span>Discount</span><span>−{{ money(selectedBill.totals.discount) }}</span></div><div class="row total"><span>Total due</span><span>{{ money(selectedBill.totals.total) }}</span></div><div class="payment-preference"><span><small>Customer selected</small><strong>{{ selectedBill.payment_method || 'No payment preference' }}</strong></span><span class="payment-state unpaid">Not yet paid</span></div></div>
                    <div class="bill-actions"><button v-for="order in selectedOrders.filter(o => o.status === 'ready')" :key="order.id" class="btn" :disabled="!!busy" @click="advance(order)"><staff-icon name="check" />Mark #{{ order.number }} served</button><button v-if="config.abilities.pay" class="btn primary" :disabled="!!busy || !data.payment_methods.length" @click="openPayment"><staff-icon name="wallet" />Collect payment</button><button class="btn" @click="printOpenBill"><staff-icon name="print" />Print bill</button><div class="bill-note">Bill #{{ selectedBill.id }} · {{ allServed ? 'Ready for payment' : 'Serve all orders before closing this table' }}</div></div>
                  </template>
                  <div v-else class="empty-state"><staff-icon name="table" /><p style="margin-top:12px">{{ selectedTable.active && selectedTable.is_available ? 'Ready for your next guests.' : 'Ordering is paused for this table.' }}</p><small>Customers can order using this table's QR code.</small></div>
                  <div v-if="selectedBill && !selectedOrders.length && config.abilities.tables" class="bill-actions"><button class="btn" :disabled="!!busy" @click="releaseBill">Release cancelled bill</button></div>
                  <div v-if="view === 'tables' && config.abilities.tables" class="table-controls"><button class="btn" :disabled="!!busy || !selectedTable.active" @click="toggleTable">{{ selectedTable.is_available ? 'Pause ordering for this table' : 'Enable ordering for this table' }}</button></div>
                </template>
                <div v-else class="empty-state">Select a table to view its bill.</div>
              </section>
              <section class="panel requests"><h3>Ready at the pass <span class="badge ready">{{ readyTickets.length }}</span></h3><div v-for="order in readyTickets" :key="order.id" class="request"><div class="request-icon"><staff-icon name="bell" /></div><div><b>{{ order.table_name }}</b><small>#{{ order.number }} · {{ itemCount(order) }} items</small></div><button :disabled="!!busy" :aria-label="'View ' + order.table_name + ' bill'" @click="selected = order.table_id">View bill →</button></div><small v-if="!readyTickets.length">Ready dishes will appear here.</small></section>
            </aside>
          </div>
        </template>
        <div class="footer"><span>pahatud. <span style="margin-left:7px">Good food. Smooth service.</span></span><span>Updated {{ time(data.updated_at) }} · {{ config.branch.name }}</span></div>
      </div>
    </main>

    <dialog ref="paymentDialog" aria-labelledby="payment-heading" @cancel="cancelPayment">
      <template v-if="paymentBill">
        <div class="modal-top"><h2 id="payment-heading">Collect payment</h2><button aria-label="Close payment" :disabled="!!busy" @click="cancelPayment"><staff-icon name="close" /></button></div><p>{{ paymentBill.table_name }} · Bill #{{ paymentBill.id }}</p><p class="amount-big">{{ money(paymentBill.total) }}</p><div class="payment-preference modal-preference"><span><small>Customer selected</small><strong>{{ paymentBill.payment_method || 'No payment preference' }}</strong></span><span class="payment-state unpaid">Not yet paid</span></div><div v-if="!paymentBill.served" class="banner">Some orders are awaiting service. Serve all orders before closing this table.</div>
        <div v-if="paymentError" class="banner error" role="alert">{{ paymentError }}</div>
        <form @submit.prevent="pay"><div class="field"><label for="payment-method">Payment method</label><select id="payment-method" v-model="paymentMethod" :disabled="!!busy" required @change="paymentMethodChanged"><option disabled value="">Select a method</option><option v-for="method in data.payment_methods" :key="method.id" :value="method.id">{{ method.title }}</option></select></div><template v-if="isCashPayment"><div class="field"><label for="cash-tendered">Cash tendered</label><div class="money-input"><span>₱</span><input id="cash-tendered" v-model.trim="cashTendered" type="number" min="0" step="0.01" inputmode="decimal" :disabled="!!busy" required autocomplete="off" placeholder="0.00"></div></div><div class="cash-summary" :class="{short: cashShortfall > 0}"><span>{{ cashShortfall > 0 ? 'Still due' : 'Change due' }}</span><strong>{{ money(cashShortfall > 0 ? cashShortfall : changeDue) }}</strong></div></template><div v-else class="field"><label for="payment-reference">Payment reference (optional)</label><input id="payment-reference" v-model.trim="paymentReference" maxlength="150" :disabled="!!busy" autocomplete="off" placeholder="Transaction or receipt number"></div><p>Record payment only after confirming it was received. This does not charge the customer.</p><div class="modal-bottom"><button type="button" class="btn" :disabled="!!busy" @click="cancelPayment">Cancel</button><button class="btn primary" :disabled="!!busy || !paymentBill.served || !paymentMethod || (isCashPayment && !cashPaymentReady)">{{ busy === 'payment' ? 'Recording…' : 'Confirm payment' }}</button></div></form>
      </template>
    </dialog>
    <dialog ref="receiptDialog" aria-labelledby="receipt-heading">
      <div class="modal-top"><h2 id="receipt-heading">{{ printData && printData.paid ? 'Payment receipt' : 'Table bill' }}</h2><button aria-label="Close receipt" @click="$refs.receiptDialog.close()"><staff-icon name="close" /></button></div>
      <template v-if="printData"><p><strong>{{ config.merchant }}</strong><br>{{ config.branch.name }} · {{ printData.table_name }} · Bill #{{ printData.id }}</p><div class="receipt-preview"><template v-for="order in printData.orders"><div :key="'receipt-' + order.id" class="round">Round {{ order.round }} · #{{ order.number }}</div><div v-for="(item, index) in order.items" :key="order.id + '-preview-' + index" class="bill-item"><div class="name">{{ item.qty }}× {{ item.name }}<small v-if="item.variants">{{ item.variants }}</small></div><span class="price">{{ money(item.qty * item.unit_price) }}</span></div></template></div><div class="bill-totals"><div class="row"><span>Subtotal</span><span>{{ money(printData.totals.sub_total) }}</span></div><div class="row"><span>Fees</span><span>{{ money(billFees(printData)) }}</span></div><div v-if="printData.totals.discount" class="row"><span>Discount</span><span>−{{ money(printData.totals.discount) }}</span></div><div class="row total"><span>{{ printData.paid ? 'Total paid' : 'Total due' }}</span><span>{{ money(printData.totals.total) }}</span></div><template v-if="printData.payment && printData.payment.cash_tendered !== null"><div class="row"><span>Cash tendered</span><span>{{ money(printData.payment.cash_tendered) }}</span></div><div class="row"><strong>Change</strong><strong>{{ money(printData.payment.change) }}</strong></div></template></div><p v-if="printData.payment">{{ printData.payment.method }} · {{ time(printData.payment.created) }}<span v-if="printData.payment.reference"> · {{ printData.payment.reference }}</span></p><p v-else>Open bill · Not paid</p></template>
      <div class="modal-bottom"><button class="btn" @click="$refs.receiptDialog.close()">Close</button><button class="btn primary" @click="printReceipt"><staff-icon name="print" />Print</button></div>
    </dialog>
    <div id="toast" role="status" :class="{visible: !!toastMessage}">{{ toastMessage }}</div>
    <section v-if="printData" class="print-receipt"><h2>{{ config.merchant }}</h2><p>{{ config.branch.name }}</p><p>{{ printData.table_name }} · Bill #{{ printData.id }}</p><p><strong>{{ printData.paid ? 'Payment receipt' : 'Open bill · Not paid' }}</strong></p><template v-for="order in printData.orders"><p :key="'round-' + order.id">Round {{ order.round }} · #{{ order.number }}</p><div v-for="(item, index) in order.items" :key="order.id + '-' + index" class="bill-item"><div>{{ item.qty }}× {{ item.name }}<small v-if="item.variants">{{ item.variants }}</small></div><span class="price">{{ money(item.qty * item.unit_price) }}</span></div></template><div class="row"><span>Subtotal</span><span>{{ money(printData.totals.sub_total) }}</span></div><div class="row"><span>Fees</span><span>{{ money(billFees(printData)) }}</span></div><div v-if="printData.totals.discount" class="row"><span>Discount</span><span>−{{ money(printData.totals.discount) }}</span></div><div class="row total"><span>{{ printData.paid ? 'Total paid' : 'Total due' }}</span><span>{{ money(printData.totals.total) }}</span></div><template v-if="printData.payment && printData.payment.cash_tendered !== null"><div class="row"><span>Cash tendered</span><span>{{ money(printData.payment.cash_tendered) }}</span></div><div class="row"><strong>Change</strong><strong>{{ money(printData.payment.change) }}</strong></div></template><p v-if="printData.payment">{{ printData.payment.method }} · {{ time(printData.payment.created) }}<span v-if="printData.payment.reference"> · {{ printData.payment.reference }}</span></p></section>
  </div>
</template>

<script>
import axios from 'axios';
import paths from './icons';

const StaffIcon = { functional: true, props: ['name'], render(h, context) { return h('svg', { attrs: {viewBox: '0 0 24 24', 'aria-hidden': 'true'} }, [h('path', {attrs: {d: paths[context.props.name] || paths.orders}})]); } };

function paymentRequestId() {
  if (typeof window.crypto?.randomUUID === 'function') return window.crypto.randomUUID();

  const bytes = window.crypto.getRandomValues(new Uint8Array(16));
  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;
  const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0'));
  return `${hex.slice(0, 4).join('')}-${hex.slice(4, 6).join('')}-${hex.slice(6, 8).join('')}-${hex.slice(8, 10).join('')}-${hex.slice(10).join('')}`;
}

export default {
  components: { StaffIcon },
  props: { config: { type: Object, required: true } },
  data() { return {
    data: this.config.initial, view: this.config.view, selected: null, query: '', tableFilter: 'all', orderFilter: 'all', kitchenTable: 'all',
    sound: false, fullscreen: false, immersive: false, now: Date.now(), loading: false, busy: '', loadError: '', actionError: '', toastMessage: '',
    paymentBill: null, paymentMethod: '', paymentReference: '', cashTendered: '', paymentKey: '', paymentError: '', printData: null,
    sequence: 0, timer: null, toastTimer: null,
    tabs: [{id: 'overview', title: 'Overview', icon: 'grid'}, {id: 'orders', title: 'Orders', icon: 'orders'}, {id: 'tables', title: 'Tables', icon: 'table'}, {id: 'payments', title: 'Payments', icon: 'wallet'}],
    colors: {new: '#dba044', preparing: '#6c8bd1', ready: '#3ba582', served: '#a7adb4'},
    columns: [{id: 'new', title: 'New orders', subtitle: 'Next up', color: '#d49b3e', empty: 'No new orders. All caught up.'}, {id: 'preparing', title: 'In preparation', subtitle: 'On the fire', color: '#6388cc', empty: 'Your prep queue is clear.'}, {id: 'ready', title: 'Ready to serve', subtitle: 'At the pass', color: '#2c9d7a', empty: 'Ready dishes will appear here.'}, {id: 'served', title: 'Served', subtitle: 'Completed', color: '#7c838c', empty: 'Served orders will appear here.'}],
  }; },
  computed: {
    initials() { return this.config.user.name.split(' ').filter(Boolean).slice(0, 2).map(s => s[0]).join(''); },
    clock() { return new Date(this.now).toLocaleTimeString('en-PH', {hour: '2-digit', minute: '2-digit'}); },
    cashierTabs() { return this.config.abilities.cashier ? this.tabs : []; },
    workspaceTitle() { return this.view === 'kitchen' ? 'Kitchen Display' : 'Cashier Dashboard'; },
    pageTitle() { return {overview: 'Cashier Dashboard', tables: 'Table management', orders: 'Live orders', payments: 'Payments & bills', kitchen: 'Kitchen Display'}[this.view]; },
    openTickets() { return this.data.orders.filter(o => !o.paid && ['new', 'preparing', 'ready'].includes(o.status)); },
    readyTickets() { return this.openTickets.filter(o => o.status === 'ready'); },
    kitchenTickets() { return this.data.orders.filter(o => !o.paid && ['new', 'preparing', 'ready', 'served'].includes(o.status) && (this.kitchenTable === 'all' || String(o.table_id) === this.kitchenTable)); },
    kitchenTables() { return [...new Map(this.data.orders.filter(o => !o.paid && ['new', 'preparing', 'ready', 'served'].includes(o.status)).map(o => [o.table_id, {id: o.table_id, name: o.table_name}])).values()]; },
    preparationItems() { return this.kitchenTickets.filter(o => ['new', 'preparing'].includes(o.status)).reduce((n, o) => n + this.itemCount(o), 0); },
    filteredTables() { return this.data.tables.filter(t => (this.tableFilter === 'all' || (this.tableFilter === 'occupied' ? this.tableOrders(t.id).length : !this.tableOrders(t.id).length)) && t.name.toLowerCase().includes(this.query.toLowerCase())); },
    filteredOrders() { return this.data.orders.filter(o => this.orderFilter === 'all' || this.orderFilter === o.status).slice().reverse(); },
    selectedTable() { return this.data.tables.find(t => t.id === this.selected); },
    selectedBill() { return this.data.bills.find(b => b.table_id === this.selected); },
    selectedOrders() { return this.tableOrders(this.selected).slice().sort((a, b) => a.round - b.round); },
    allServed() { return this.selectedOrders.length > 0 && this.selectedOrders.every(o => o.status === 'served'); },
    selectedPaymentMethod() { return this.data.payment_methods.find(method => String(method.id) === String(this.paymentMethod)); },
    isCashPayment() { return !!this.selectedPaymentMethod?.is_cash; },
    cashTenderedCents() {
      const value = Number(this.cashTendered);
      return Number.isFinite(value) && value >= 0 ? Math.round(value * 100) : 0;
    },
    cashShortfall() { return this.paymentBill ? Math.max(0, this.paymentBill.total - this.cashTenderedCents) : 0; },
    changeDue() { return this.paymentBill ? Math.max(0, this.cashTenderedCents - this.paymentBill.total) : 0; },
    cashPaymentReady() { return !!this.paymentBill && this.cashTendered !== '' && this.cashTenderedCents >= this.paymentBill.total; },
    stats() {
      const occupied = this.data.tables.filter(t => this.tableOrders(t.id).length).length;
      return [{title: 'Active tables', value: occupied, suffix: ' / ' + this.data.tables.length, icon: 'table', detail: (this.data.tables.length - occupied) + ' tables without an open bill'}, {title: 'In the kitchen', value: this.openTickets.filter(o => o.status !== 'ready').length, icon: 'chef', detail: 'New & preparing orders'}, {title: 'Ready to serve', value: this.readyTickets.length, icon: 'bell', detail: 'Waiting for your team'}, {title: 'Collected today', value: this.money(this.data.payments.reduce((n, p) => n + p.amount, 0)), icon: 'wallet', detail: this.data.payments.length + (this.data.payments.length === 1 ? ' payment recorded' : ' payments recorded')}];
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
    money(value) { return '₱' + (Number(value || 0) / 100).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
    capitalize(value) { return value[0].toUpperCase() + value.slice(1); },
    label(status) { return {new: 'New order', preparing: 'Preparing', ready: 'Ready to serve', served: 'Dining', available: 'Available', cancelled: 'Cancelled'}[status] || status; },
    time(value) { return new Date(value).toLocaleTimeString('en-PH', {hour: '2-digit', minute: '2-digit'}); },
    age(order) { return Math.max(0, Math.floor((this.now - new Date(order.created).getTime()) / 60000)); },
    timerClass(order) { const minutes = this.age(order); return minutes >= 15 ? 'timer-late' : minutes >= 10 ? 'timer-warning' : 'timer-fresh'; },
    variantOptions(item) { return Array.isArray(item.variant_options) && item.variant_options.length ? item.variant_options : String(item.variants || '').split(',').map(value => value.trim()).filter(Boolean); },
    itemCount(order) { return order.items.reduce((n, item) => n + item.qty, 0); },
    tableItemCount(id) { return this.tableOrders(id).reduce((n, o) => n + this.itemCount(o), 0); },
    tableAge(id) { return Math.max(0, ...this.tableOrders(id).map(o => this.age(o))); },
    tableOrders(id) { const bill = this.data.bills.find(b => b.table_id === id); return bill ? this.data.orders.filter(o => bill.order_ids.includes(o.id)) : []; },
    tableTotal(id) { const bill = this.data.bills.find(b => b.table_id === id); return bill ? bill.totals.total : 0; },
    tableStatus(id) { const orders = this.tableOrders(id); return ['new', 'ready', 'preparing', 'served'].find(s => orders.some(o => o.status === s)) || 'available'; },
    tableName(id) { const table = this.data.tables.find(t => t.id === id); return table ? table.name : 'Table #' + id; },
    billFees(bill) { return bill.totals.delivery_fee + bill.totals.convenience_fee - bill.totals.vat_amount; },
    ticketsFor(status) { return this.kitchenTickets.filter(o => o.status === status); },
    canAdvance(order) { return order.status === 'ready' ? this.config.abilities.serve : ['new', 'preparing'].includes(order.status) && this.config.abilities.prepare; },
    actionLabel(order) { return {new: 'Start preparing', preparing: 'Mark ready', ready: 'Mark served'}[order.status]; },
    selectDefault() { if (!this.data.tables.some(t => t.id === this.selected)) this.selected = (this.data.tables.find(t => this.tableOrders(t.id).length) || this.data.tables[0] || {}).id || null; },
    changeView(view) { if (this.busy) return; this.view = view; document.title = this.workspaceTitle + ' | ' + this.config.merchant; this.actionError = ''; const url = new URL(window.location.href); url.searchParams.set('view', view); window.history.replaceState({}, '', url); },
    changeBranch(id) { window.location.href = this.config.base + '?branch_id=' + encodeURIComponent(id) + '&view=' + this.view; },
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
        if (key === 'payment') this.paymentError = this.message(error); else this.actionError = this.message(error);
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
      await this.load();
      if (!this.selectedBill || !this.selectedOrders.length) return;
      this.paymentBill = {id: this.selectedBill.id, table_name: this.selectedTable.name, total: this.selectedBill.totals.total, served: this.allServed, payment_method: this.selectedBill.payment_method};
      const preferred = this.data.payment_methods.find(method => String(method.id) === String(this.selectedBill.payment_method_id));
      this.paymentMethod = preferred ? preferred.id : ''; this.paymentReference = ''; this.cashTendered = ''; this.paymentError = ''; this.paymentKey = paymentRequestId();
      await this.$nextTick(); this.$refs.paymentDialog.showModal();
    },
    paymentMethodChanged() { this.paymentReference = ''; this.cashTendered = ''; },
    cancelPayment(event) { if (this.busy) { if (event) event.preventDefault(); return; } this.$refs.paymentDialog.close(); this.paymentBill = null; },
    async pay() {
      if (!this.paymentBill || !this.paymentBill.served || !this.paymentMethod) return;
      const saved = await this.request('payment', 'post', this.endpoint('/bills/' + this.paymentBill.id + '/payment'), {payment_method_id: this.paymentMethod, idempotency_key: this.paymentKey, expected_total: this.paymentBill.total, reference: this.isCashPayment ? null : this.paymentReference || null, cash_tendered_cents: this.isCashPayment ? this.cashTenderedCents : null});
      if (saved) { this.cancelPayment(); this.toast('Payment recorded. Table is available.'); }
    },
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
