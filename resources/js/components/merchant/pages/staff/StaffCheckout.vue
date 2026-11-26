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
        <div class="checkout-totals"><div><span>Subtotal</span><strong>{{ money(order.totals.sub_total) }}</strong></div><div v-if="fees"><span>Fees</span><strong>{{ money(fees) }}</strong></div><div v-if="order.totals.discount"><span>Discount</span><strong>−{{ money(order.totals.discount) }}</strong></div><div v-if="appliedRoundingDiscount"><span>QuickPay rounding discount</span><strong>−{{ money(appliedRoundingDiscount) }}</strong></div><div class="checkout-total"><span>{{ saved ? 'Total paid' : 'Total amount due' }}</span><strong>{{ money(showQuickPayCode ? quickPay.amount_cents : order.totals.total) }}</strong></div></div>
        <p class="checkout-note">Payment and kitchen preparation are tracked separately. The kitchen can continue preparing this order after payment.</p>
      </section>
      <section class="panel checkout-payment">
        <div v-if="saved" class="checkout-success" role="status"><h2>Payment recorded</h2><p>{{ money(order.totals.total) }} paid by {{ paidMethod }} against bill #{{ order.id }}.</p><div v-if="paidChange !== null" class="checkout-change"><span>Change Due</span><strong>{{ money(paidChange) }}</strong></div><button type="button" class="btn primary" @click="$emit('receipt', order.id)">Print receipt</button><button type="button" class="btn checkout-back-menu" @click="$emit('done')">New Order</button></div>
        <div v-else-if="showQuickPayCode" class="checkout-quickpay">
          <h2>QuickPay payment</h2>
          <p role="status">Waiting for payment of {{ money(quickPay.amount_cents) }}.</p>
          <p v-if="quickPay.livemode === false" class="checkout-note">Test checkout. Use QuickPay’s test tools to simulate payment.</p>
          <template v-if="quickPayImage || quickPayQrValue">
            <p v-if="quickPay.livemode !== false">{{ quickPayScanInstructions }}</p>
            <img v-if="quickPayImage" class="checkout-quickpay-qr" :src="quickPayImage" :alt="quickPayQrLabel" @error="failedQuickPayImage = quickPayImage">
            <qrcode-vue v-else :value="quickPayQrValue" :size="200" level="M" render-as="svg" class="checkout-quickpay-qr" role="img" :aria-label="quickPayQrLabel" />
          </template>
          <p v-if="quickPayHosted"><a :href="quickPay.checkout_url" target="_blank" rel="noopener noreferrer">Open QuickPay checkout</a></p>
          <p v-if="quickPay.monitoring_stopped" class="checkout-note">Automatic checks are off. Check payment status after the customer pays.</p>
          <p v-if="quickPayStatusNote" class="checkout-note" role="status">{{ quickPayStatusNote }}</p>
          <p v-if="error" class="pos-error" role="alert">{{ error }}</p>
          <button type="button" class="btn primary" :disabled="busy || checking" @click="checkQuickPay(false, true, true)">{{ checking ? 'Checking…' : 'Check payment status' }}</button>
          <button type="button" class="btn" :disabled="busy || checking" @click="cancelQuickPay">{{ busy ? 'Cancelling…' : 'Cancel' }}</button>
        </div>
        <form v-else @submit.prevent="pay">
          <payment-method-cards ref="methods" v-model="method" :methods="methods" :disabled="busy || !!quickPay" />
          <template v-if="quickPay">
            <p class="checkout-note" role="status">Automatic QuickPay checks are off. The issued code can still be paid. Check its status before taking another payment.</p>
            <p v-if="quickPayStatusNote" class="checkout-note" role="status">{{ quickPayStatusNote }}</p>
            <p v-if="error" class="pos-error" role="alert">{{ error }}</p>
            <button ref="checkCancelledQuickPay" type="button" class="btn" :disabled="checking" @click="checkQuickPay(false, true)">{{ checking ? 'Checking…' : 'Check payment status' }}</button>
            <button type="button" class="btn" :disabled="checking" @click="checkQuickPay(false, true, true)">Show QR again</button>
          </template>
          <template v-else>
            <template v-if="isCash"><label class="checkout-field" for="checkout-cash">Amount Received (₱)<input id="checkout-cash" ref="cash" v-model.trim="cash" type="number" inputmode="decimal" min="0" step="0.01" autocomplete="off" placeholder="0.00" :disabled="busy" required></label><div class="checkout-change" aria-live="polite"><span>Change Due</span><strong>{{ money(change) }}</strong></div><p v-if="shortfall" class="checkout-note">Still due: {{ money(shortfall) }}</p></template>
            <p v-if="usesQuickPay && quickPayCharge <= 0" class="checkout-note" role="status">QuickPay requires a positive whole-peso charge. Choose another payment method.</p>
            <label v-else-if="usesQuickPay && roundingDiscount" class="checkout-note checkout-rounding-confirm">
              <input v-model="roundingConfirmed" type="checkbox" :disabled="busy">
              <span>Apply a <strong>{{ money(roundingDiscount) }} rounding discount</strong> and charge <strong>{{ money(quickPayCharge) }}</strong> with QuickPay.</span>
            </label>
            <p v-else class="checkout-note">{{ usesQuickPay ? 'Generate a payment code for the customer to scan.' : isCash ? 'Count the cash received before confirming payment.' : 'Confirm receipt using the selected payment method before recording it here. This does not charge the customer.' }}</p>
            <p v-if="error" class="pos-error" role="alert">{{ error }}</p>
            <button class="btn primary checkout-confirm" :disabled="busy || checking || !ready">{{ usesQuickPay ? (busy ? 'Generating QR…' : 'Generate QuickPay QR') : (busy ? 'Recording payment…' : 'Confirm Payment') }}</button>
          </template>
        </form>
      </section>
    </div>
  </section>
</template>
<script>
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';
import PaymentMethodCards from './PaymentMethodCards.vue';
import {isCashMethod, requestId} from './payment';
export default {
  components: {PaymentMethodCards, QrcodeVue},
  props: {bill: {type: Object, required: true}, methods: {type: Array, required: true}, endpoint: {type: String, required: true}, preferred: {default: ''}},
  data() { return {order: JSON.parse(JSON.stringify(this.bill)), method: this.preferred, cash: '', busy: false, saved: this.bill.paid, error: '', roundingConfirmed: false, quickPay: this.bill.quickpay?.status === 'pending' ? this.bill.quickpay : null, failedQuickPayImage: null, quickPayStatusNote: '', checking: false, pollTimer: null, attempt: null, paidMethod: this.bill.payment?.method || '', paidChange: this.bill.payment?.change ?? null}; },
  computed: {
    selectedMethod() { return this.methods.find(m => String(m.id) === String(this.method)); },
    showQuickPayCode() {
      const payment = this.quickPay;
      return !!payment && (!payment.monitoring_stopped || !!(payment.qr_payload || payment.qr_image || payment.checkout_url));
    },
    quickPayImage() {
      const image = this.quickPay?.qr_image;
      return image && image !== this.failedQuickPayImage ? image : null;
    },
    quickPayHosted() { return !!this.quickPay?.checkout_url && !this.quickPay.qr_payload && !this.quickPayImage; },
    quickPayWallet() { return {gcash: 'GCash', maya: 'Maya'}[this.quickPay?.channel]; },
    quickPayScanInstructions() {
      if (this.quickPayHosted) return 'Ask the customer to scan with their phone camera and pay on QuickPay.';
      return this.quickPayWallet
        ? `Ask the customer to scan this payment code in the ${this.quickPayWallet} app.`
        : 'Ask the customer to scan with their bank or e-wallet app.';
    },
    quickPayQrLabel() {
      if (this.quickPayHosted) return 'QuickPay hosted checkout code';
      return this.quickPayWallet ? `${this.quickPayWallet} payment code` : 'QuickPay QR Ph payment code';
    },
    quickPayQrValue() { return this.quickPay?.qr_payload || (this.quickPayHosted ? this.quickPay.checkout_url : null); },
    usesQuickPay() { return this.selectedMethod?.provider === 'quickpay'; },
    roundingDiscount() { return this.order.totals.total % 100; },
    quickPayCharge() { return this.order.totals.total - this.roundingDiscount; },
    appliedRoundingDiscount() { return this.showQuickPayCode ? this.quickPay.rounding_discount_cents : this.order.totals.rounding_discount_cents; },
    isCash() { return isCashMethod(this.selectedMethod); },
    cashCents() { const value = Number(this.cash); return this.cash !== '' && Number.isFinite(value) && value >= 0 ? Math.round(value * 100) : null; },
    change() { return this.isCash ? Math.max(0, (this.cashCents || 0) - this.order.totals.total) : 0; },
    shortfall() { return Math.max(0, this.order.totals.total - (this.cashCents || 0)); },
    ready() {
      if (this.saved || this.quickPay || !this.selectedMethod || !this.order.orders.length) return false;
      if (this.usesQuickPay) return this.quickPayCharge > 0 && (!this.roundingDiscount || this.roundingConfirmed);
      return !this.isCash || (this.cashCents !== null && this.cashCents >= this.order.totals.total);
    },
    fees() { return this.order.totals.delivery_fee + this.order.totals.convenience_fee - this.order.totals.vat_amount; },
  },
  watch: {
    method() { this.cash = ''; this.error = ''; this.roundingConfirmed = false; this.focusMethod(); },
    'order.totals.total'() { this.roundingConfirmed = false; },
    busy(value) { this.$emit('busy', value); },
  },
  mounted() {
    this.$refs.heading.focus();
    if (this.saved) return;
    if (this.isCash) this.focusMethod();
    if (this.quickPay?.monitoring_stopped) return;
    if (this.quickPay || this.methods.some(method => method.provider === 'quickpay')) this.checkQuickPay(true);
  },
  beforeDestroy() { clearTimeout(this.pollTimer); },
  methods: {
    money(value) { return '₱' + (Number(value || 0) / 100).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
    statusLabel(value) { return {new: 'New order', preparing: 'Preparing', ready: 'Ready to serve', served: 'Served'}[value] || value; },
    focusMethod() { this.$nextTick(() => this.$refs.cash?.focus()); },
    headers() { return {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json'}; },
    paymentError(error) {
      const response = error.response;
      return response && [401, 419].includes(response.status) ? 'Your session has expired. Log in again to continue.' : Object.values(response?.data?.errors || {}).flat()[0] || response?.data?.message || 'Unable to connect. Please try again.';
    },
    async finishPayment(method, change = null) {
      this.saved = true;
      this.paidMethod = method;
      this.paidChange = change;
      this.$emit('paid');
      await this.$nextTick();
      this.$refs.heading?.focus();
    },
    async showQuickPay(payment) {
      this.quickPay = payment.status === 'pending' ? payment : null;
      this.error = '';
      this.roundingConfirmed = false;
      if (!this.quickPay) this.quickPayStatusNote = '';

      if (payment.status === 'paid') {
        this.order.totals = {...this.order.totals, total: payment.amount_cents, rounding_discount_cents: payment.rounding_discount_cents};
        await this.finishPayment('QuickPay');
      } else if (payment.status === 'expired') {
        this.error = 'This QuickPay checkout expired. Generate a new code or select another payment method.';
      } else if (payment.status === 'rejected') {
        this.error = payment.rejection_message || 'QuickPay could not accept this checkout. Check the amount and the configured QuickPay payment channel, or select another payment method.';
      }
      this.scheduleQuickPayCheck();
    },
    quickPayEndpoint(action) {
      const url = new URL(this.endpoint, window.location.origin);
      url.pathname += '/quickpay/' + action;
      return url.toString();
    },
    async cancelQuickPay() {
      if (this.busy || this.checking) return;
      if (this.quickPay.monitoring_stopped) {
        await this.showQuickPay({...this.quickPay, checkout_url: null, qr_payload: null, qr_image: null});
        this.$refs.checkCancelledQuickPay?.focus();
        return;
      }
      this.busy = true;
      this.quickPayStatusNote = '';
      clearTimeout(this.pollTimer);
      try {
        const response = await axios.post(this.quickPayEndpoint('cancel'), {checkout_id: this.quickPay.id ?? null}, {headers: this.headers()});
        this.method = '';
        await this.showQuickPay(response.data.payment);
        await this.$nextTick();
        this.$refs.checkCancelledQuickPay?.focus();
      } catch (error) {
        if (error.response?.status === 404 && !this.quickPay.id) {
          this.quickPay = null;
          this.method = '';
          this.error = '';
        } else {
          this.error = this.paymentError(error);
          this.scheduleQuickPayCheck();
        }
      } finally { this.busy = false; }
    },
    scheduleQuickPayCheck() {
      clearTimeout(this.pollTimer);
      if (this.quickPay && !this.quickPay.monitoring_stopped && this.$refs.heading) this.pollTimer = setTimeout(() => this.checkQuickPay(), 6000);
    },
    async checkQuickPay(initial = false, manual = false, showCode = false) {
      if (this.checking || (this.busy && !initial) || (!manual && this.quickPay?.monitoring_stopped)) return;
      this.checking = true;
      if (manual) this.quickPayStatusNote = '';
      try {
        const response = await axios.post(this.quickPayEndpoint('status'), {manual, show_code: showCode}, {headers: this.headers()});
        await this.showQuickPay(response.data.payment);
        if (manual && this.quickPay) {
          this.quickPayStatusNote = this.quickPay.livemode === false
            ? 'QuickPay reports this test payment is still pending.'
            : 'QuickPay reports this payment is still pending.';
        }
      } catch (error) {
        if (error.response?.status === 404 && !this.quickPay?.id) {
          this.quickPay = null;
        } else {
          this.error = this.paymentError(error);
          if (manual || ![401, 403, 419].includes(error.response?.status)) this.scheduleQuickPayCheck();
        }
      } finally { this.checking = false; }
    },
    async pay() {
      if (this.busy || this.checking || !this.ready) return;
      this.busy = true; this.error = '';
      const input = {payment_method_id: this.method, expected_total: this.order.totals.total, cash_tendered_cents: this.isCash ? this.cashCents : null, reference: null};
      if (this.usesQuickPay && this.roundingConfirmed) input.confirm_rounding_discount = true;
      const signature = JSON.stringify(input);
      if (this.attempt?.signature !== signature) this.attempt = {signature, key: requestId()};
      try {
        const response = await axios.post(this.endpoint, {...input, idempotency_key: this.attempt.key}, {headers: this.headers()});
        if (response.data.payment?.provider === 'quickpay') {
          await this.showQuickPay(response.data.payment);
        } else {
          await this.finishPayment(this.selectedMethod.title, this.isCash ? this.change : null);
        }
      } catch (error) {
        const response = error.response;
        this.error = this.paymentError(error);
        if (this.usesQuickPay && (!response || response.status >= 500)) {
          this.quickPay = {amount_cents: this.quickPayCharge, rounding_discount_cents: this.roundingDiscount};
          this.scheduleQuickPayCheck();
        }
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

<style scoped>
.checkout-rounding-confirm {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  white-space: normal;
  cursor: pointer;
}
.checkout-rounding-confirm > input {
  appearance: auto;
  flex: none;
  width: 18px;
  height: 18px;
  margin: 2px 0 0;
  padding: 0;
  accent-color: var(--red);
}
.checkout-rounding-confirm > input:focus-visible {
  outline: 2px solid #252b30;
  outline-offset: 2px;
}
.checkout-quickpay-qr { display: block; width: fit-content; max-width: 100%; margin: 12px 0; padding: 12px; background: white; }
img.checkout-quickpay-qr { width: min(224px, 100%); height: auto; }
</style>
