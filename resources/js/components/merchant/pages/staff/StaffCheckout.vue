<template>
  <section class="staff-checkout" aria-labelledby="checkout-title" :aria-busy="busy">
    <header class="checkout-heading"><button type="button" class="btn" :disabled="busy" @click="$emit('back')">← Back to Order</button><div><h1 id="checkout-title" ref="heading" tabindex="-1">{{ saved ? 'Payment successful' : 'Checkout' }}</h1><p>{{ order.table_name }} · Bill #{{ order.id }}</p></div></header>
    <div class="checkout-grid">
      <section class="panel checkout-summary" aria-labelledby="checkout-summary-title">
        <h2 id="checkout-summary-title">Order summary</h2>
        <article v-for="round in order.orders" :key="round.id" class="checkout-round">
          <h3>Order #{{ round.number }} <span>{{ statusLabel(round.status) }}</span></h3>
          <div v-for="(item, index) in round.items" :key="index" class="checkout-item"><div><strong>{{ item.qty }}× {{ item.name }}</strong><small v-if="item.variants">{{ item.variants }}</small><small v-if="item.note">{{ item.note }}</small></div><strong>{{ money(item.qty * item.unit_price) }}</strong></div>
          <p v-if="round.notes" class="checkout-note">{{ round.notes }}</p>
        </article>
        <div class="checkout-totals"><div><span>Subtotal</span><strong>{{ money(order.totals.sub_total) }}</strong></div><div v-if="fees"><span>Fees</span><strong>{{ money(fees) }}</strong></div><div v-if="order.totals.discount"><span>Discount</span><strong>−{{ money(order.totals.discount) }}</strong></div><div class="checkout-total"><span>{{ saved ? 'Total paid' : 'Total amount due' }}</span><strong>{{ money(order.totals.total) }}</strong></div></div>
        <p class="checkout-note">Payment and kitchen preparation are tracked separately. The kitchen can continue preparing this order after payment.</p>
      </section>
      <section class="panel checkout-payment">
        <div v-if="saved" class="checkout-success" role="status"><h2>Payment recorded</h2><p>{{ money(order.totals.total) }} paid by {{ paidMethod }} against bill #{{ order.id }}.</p><div v-if="paidChange !== null" class="checkout-change"><span>Change Due</span><strong>{{ money(paidChange) }}</strong></div><button type="button" class="btn primary" @click="$emit('receipt', order.id)">Print receipt</button></div>
        <form v-else @submit.prevent="pay">
          <payment-method-cards ref="methods" v-model="method" :methods="methods" :disabled="busy" />
          <template v-if="isCash"><label class="checkout-field" for="checkout-cash">Amount Received (₱)<input id="checkout-cash" ref="cash" v-model.trim="cash" type="number" inputmode="decimal" min="0" step="0.01" autocomplete="off" placeholder="0.00" :disabled="busy" required></label><div class="checkout-change" aria-live="polite"><span>Change Due</span><strong>{{ money(change) }}</strong></div><p v-if="shortfall" class="checkout-note">Still due: {{ money(shortfall) }}</p></template>
          <label v-else-if="selectedMethod" class="checkout-field">Payment reference (optional)<input ref="reference" v-model.trim="reference" maxlength="150" :disabled="busy" autocomplete="off" placeholder="Transaction or receipt number"></label>
          <p class="checkout-note">{{ isCash ? 'Count the cash received before confirming payment.' : 'Confirm receipt using the selected payment method before recording it here. This does not charge the customer.' }}</p>
          <p v-if="error" class="pos-error" role="alert">{{ error }}</p>
          <button class="btn primary checkout-confirm" :disabled="busy || !ready">{{ busy ? 'Recording payment…' : 'Confirm Payment' }}</button>
        </form>
      </section>
    </div>
  </section>
</template>
<script>
import axios from 'axios';
import PaymentMethodCards from './PaymentMethodCards.vue';
import {isCashMethod, requestId} from './payment';
export default {
  components: {PaymentMethodCards},
  props: {bill: {type: Object, required: true}, methods: {type: Array, required: true}, endpoint: {type: String, required: true}, preferred: {default: ''}},
  data() { return {order: JSON.parse(JSON.stringify(this.bill)), method: this.preferred, cash: '', reference: '', busy: false, saved: this.bill.paid, error: '', attempt: null, paidMethod: this.bill.payment?.method || '', paidChange: this.bill.payment?.change ?? null}; },
  computed: {
    selectedMethod() { return this.methods.find(m => String(m.id) === String(this.method)); },
    isCash() { return isCashMethod(this.selectedMethod); },
    cashCents() { const value = Number(this.cash); return this.cash !== '' && Number.isFinite(value) && value >= 0 ? Math.round(value * 100) : null; },
    change() { return this.isCash ? Math.max(0, (this.cashCents || 0) - this.order.totals.total) : 0; },
    shortfall() { return Math.max(0, this.order.totals.total - (this.cashCents || 0)); },
    ready() { return !this.saved && !!this.selectedMethod && this.order.orders.length > 0 && (!this.isCash || (this.cashCents !== null && this.cashCents >= this.order.totals.total)); },
    fees() { return this.order.totals.delivery_fee + this.order.totals.convenience_fee - this.order.totals.vat_amount; },
  },
  watch: {
    method() { this.cash = ''; this.reference = ''; this.error = ''; this.focusMethod(); },
    busy(value) { this.$emit('busy', value); },
  },
  mounted() { this.$refs.heading.focus(); if (this.isCash && !this.saved) this.focusMethod(); },
  methods: {
    money(value) { return '₱' + (Number(value || 0) / 100).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
    statusLabel(value) { return {new: 'New order', preparing: 'Preparing', ready: 'Ready to serve', served: 'Served'}[value] || value; },
    focusMethod() { this.$nextTick(() => (this.isCash ? this.$refs.cash : this.$refs.reference)?.focus()); },
    async pay() {
      if (this.busy || !this.ready) return;
      this.busy = true; this.error = '';
      const input = {payment_method_id: this.method, expected_total: this.order.totals.total, cash_tendered_cents: this.isCash ? this.cashCents : null, reference: this.isCash ? null : this.reference || null};
      const signature = JSON.stringify(input);
      if (this.attempt?.signature !== signature) this.attempt = {signature, key: requestId()};
      try {
        await axios.post(this.endpoint, {...input, idempotency_key: this.attempt.key}, {headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json'}});
        this.saved = true; this.paidMethod = this.selectedMethod.title; this.paidChange = this.isCash ? this.change : null;
        this.$emit('paid');
        await this.$nextTick(); this.$refs.heading.focus();
      } catch (error) {
        const response = error.response;
        this.error = response && [401, 419].includes(response.status) ? 'Your session has expired. Log in again to continue.' : Object.values(response?.data?.errors || {}).flat()[0] || response?.data?.message || 'Unable to connect. Please retry Confirm Payment.';
        // Reconcile a changed bill or a response lost after the server saved payment.
        try {
          const url = new URL(this.endpoint, window.location.origin); url.pathname = url.pathname.replace(/\/payment$/, '/receipt');
          const result = await axios.get(url.toString(), {headers: {Accept: 'application/json'}});
          if (result.data.paid) {
            this.order = result.data; this.saved = true; this.error = ''; this.paidMethod = result.data.payment?.method; this.paidChange = result.data.payment?.change ?? null; this.$emit('paid');
          } else if (result.data.totals.total !== this.order.totals.total) {
            this.order = result.data; this.cash = ''; this.error = 'The bill changed. Review the updated order and total before confirming again.';
          }
        } catch (_) { /* Keep the original error and idempotency key for retry. */ }
      } finally { this.busy = false; }
    },
  },
};
</script>
