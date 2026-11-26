<template>
  <article class="thermal-receipt" :style="{'--receipt-width': paper + 'mm'}">
    <header><h2>{{ merchant }}</h2><p>{{ branch }}</p><h3>{{ receipt.paid ? 'Payment receipt' : 'Open bill · Not paid' }}</h3><p>{{ receipt.table_name }} · Bill #{{ receipt.id }}</p></header>
    <section v-for="order in receipt.orders" :key="order.id" class="receipt-round">
      <h4>Order #{{ order.number }} · Round {{ order.round }}</h4>
      <p v-if="order.type" class="receipt-meta">{{ order.type.replace('_', ' ') }}</p><p v-if="order.placed_at" class="receipt-meta">Placed: {{ date(order.placed_at) }}</p>
      <div v-for="(item, index) in order.items" :key="index" class="receipt-item">
        <div class="receipt-line"><strong>{{ item.qty }}× {{ item.name }}</strong><span>{{ money(item.qty * item.unit_price) }}</span></div>
        <p>{{ money(item.unit_price) }} each</p><p v-if="item.variants">Options: {{ item.variants }}</p><p v-if="item.note">Item instructions: {{ item.note }}</p>
      </div>
      <p v-if="order.notes" class="receipt-notes"><strong>Whole-order notes:</strong> {{ order.notes }}</p>
    </section>
    <section class="receipt-totals">
      <div class="receipt-line"><span>Subtotal</span><span>{{ money(receipt.totals.sub_total) }}</span></div>
      <div v-if="fees" class="receipt-line"><span>Fees</span><span>{{ money(fees) }}</span></div>
      <div v-if="receipt.totals.discount" class="receipt-line"><span>Discount</span><span>−{{ money(receipt.totals.discount) }}</span></div>
      <div v-if="receipt.totals.rounding_discount_cents" class="receipt-line"><span>QuickPay rounding discount</span><span>−{{ money(receipt.totals.rounding_discount_cents) }}</span></div>
      <div class="receipt-line receipt-total"><strong>{{ receipt.paid ? 'Total paid' : 'Total due' }}</strong><strong>{{ money(receipt.totals.total) }}</strong></div>
      <template v-if="receipt.payment"><div class="receipt-line"><span>Payment</span><strong>{{ receipt.payment.method }}</strong></div><template v-if="receipt.payment.cash_tendered != null"><div class="receipt-line"><span>Cash tendered</span><span>{{ money(receipt.payment.cash_tendered) }}</span></div><div class="receipt-line"><strong>Change</strong><strong>{{ money(receipt.payment.change) }}</strong></div></template><p v-if="receipt.payment.reference">Reference: {{ receipt.payment.reference }}</p><p>Paid: {{ date(receipt.payment.created) }}</p></template>
    </section><footer>Thank you for ordering with {{ merchant }}!</footer>
  </article>
</template>
<script>
export default {
  props: {receipt: {type: Object, required: true}, merchant: String, branch: String, paper: {type: String, default: '80'}},
  computed: {fees() { const t = this.receipt.totals; return (t.delivery_fee || 0) + (t.convenience_fee || 0) - (t.vat_amount || 0); }},
  methods: {money(cents) { return '₱' + ((cents || 0) / 100).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }, date(value) { return value ? new Date(value).toLocaleString('en-PH', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'Not recorded'; }},
};
</script>
