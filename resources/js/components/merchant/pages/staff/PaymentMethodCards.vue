<template>
  <fieldset class="pos-payment-picker" :disabled="disabled">
    <legend>{{ label }}</legend>
    <div class="pos-payment-grid">
      <label v-for="method in methods" :key="method.id" class="pos-payment-card" :class="[kind(method), {selected: String(value) === String(method.id)}]">
        <input type="radio" :name="groupName" :value="method.id" :checked="String(value) === String(method.id)" :disabled="disabled" required @change="select(method.id)">
        <svg class="pos-method-icon" viewBox="0 0 24 24" aria-hidden="true"><path :d="icon(method)" /></svg>
        <strong>{{ method.title }}</strong>
        <svg v-if="String(value) === String(method.id)" class="pos-method-selected" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4L19 6" /></svg>
      </label>
    </div>
    <p v-if="!methods.length" class="pos-error" role="alert">No payment methods are available.</p>
  </fieldset>
</template>
<script>
import {isCashMethod} from './payment';
const icons = {
  cash: 'M2 5h20v14H2z M2 9a4 4 0 0 0 4-4 M18 5a4 4 0 0 0 4 4 M2 15a4 4 0 0 1 4 4 M18 19a4 4 0 0 1 4-4 M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
  gcash: 'M8 2h8a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2 M10 5h4 M12 19h.01 M15 9a4 4 0 1 0 0 6v-3h-3',
  card: 'M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2 M2 9h20 M6 15h4 M15 15h3',
  wallet: 'M4 5h15v4H4a2 2 0 0 1 0-4z M3 7v13h18V9H5 M16 13h5v4h-5z',
};
export default {
  props: {value: {default: ''}, methods: {type: Array, default: () => []}, disabled: Boolean, label: {type: String, default: 'Payment method'}},
  data() { return {groupName: 'pos-payment-method-' + this._uid}; },
  methods: {
    kind(method) { if (isCashMethod(method)) return 'cash'; if (String(method.id) === '2' || /gcash/i.test(method.title)) return 'gcash'; if (String(method.id) === '1' || /card/i.test(method.title)) return 'card'; return 'wallet'; },
    icon(method) { return icons[this.kind(method)]; },
    select(id) { if (this.disabled) return; this.$emit('input', id); this.$emit('change', id); },
    focus() { const input = this.$el.querySelector('input:checked:not(:disabled)') || this.$el.querySelector('input:not(:disabled)'); input?.focus(); },
  },
};
</script>
