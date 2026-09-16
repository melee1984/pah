<template>
  <div id="modalAddVariantDetails" class="modal fade option-modal" tabindex="-1" role="dialog" aria-labelledby="variant-options-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header"><span class="modal-icon"><i class="fas fa-list-ul" aria-hidden="true"></i></span><div><span class="eyebrow">Product variations</span><h3 id="variant-options-title">Manage choices</h3><p>Add the options customers can select for this variation.</p></div><button type="button" class="close-button" aria-label="Close" @click="closeModalView"><i class="fas fa-times" aria-hidden="true"></i></button></div>
        <div class="modal-body">
          <div class="choices-panel">
            <div class="panel-heading"><h4>Choices <span class="count-badge">{{ details.length }}</span></h4><p>Select a choice to edit its name or price.</p></div>
            <div class="choices-table-wrap">
              <table class="choices-table">
                <thead><tr><th>Choice</th><th>Extra price</th><th>Pahatud commission (15%)</th><th>Default</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                  <tr v-if="isLoading"><td colspan="5" class="empty-row">Loading choices…</td></tr>
                  <tr v-else-if="!details.length"><td colspan="5" class="empty-row"><i class="fas fa-list-ul" aria-hidden="true"></i><strong>No choices yet</strong><span>Add the first option using the form.</span></td></tr>
                  <tr v-for="detail in details" :key="detail.id">
                    <td class="choice-name">{{ detail.title }}</td>
                    <td>{{ formatPrice(detail.price) }}</td>
                    <td class="commission-cell">{{ formatPrice(detail.price_comm == null ? Number(detail.price) * commissionRate : detail.price_comm) }}</td>
                    <td><span v-if="isSelectedByDefault(detail)" class="default-pill">Selected</span><span v-else class="muted-dash">—</span></td>
                    <td class="row-actions"><button type="button" :aria-label="'Edit ' + detail.title" @click="editVariantDetails(detail)"><i class="fas fa-pen" aria-hidden="true"></i></button><button type="button" class="delete-icon" :aria-label="'Delete ' + detail.title" @click="deleteVariantDetails(detail)"><i class="fas fa-trash" aria-hidden="true"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <form class="choice-form" @submit.prevent="submitRecord" novalidate>
            <div class="form-heading"><span class="heading-icon"><i :class="isEditing ? 'fas fa-pen' : 'fas fa-plus'" aria-hidden="true"></i></span><div><h4>{{ isEditing ? 'Edit choice' : 'Add a choice' }}</h4><p>{{ isEditing ? 'Update this option below.' : 'Create an option for customers.' }}</p></div></div>
            <div class="form-fields">
              <div class="form-group"><label for="choice-title">Choice name <span class="required">*</span></label><input id="choice-title" v-model.trim="field.title" type="text" maxlength="75" class="form-control" :class="{ 'is-invalid': errors.title }" :aria-invalid="!!errors.title" placeholder="e.g. Large, Extra cheese"><small v-if="errors.title" class="field-error">{{ errors.title }}</small></div>
              <div class="form-group"><label for="choice-price">Additional price <span class="required">*</span></label><div class="money-input"><span>₱</span><input id="choice-price" v-model="field.price" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.price }" :aria-invalid="!!errors.price" placeholder="0.00"></div><small v-if="errors.price" class="field-error">{{ errors.price }}</small><small v-else class="field-help">Enter 0 for no extra charge.</small></div>
              <div class="commission-note" aria-live="polite"><span><i class="fas fa-info-circle" aria-hidden="true"></i> Pahatud commission <small>15% of the additional price</small></span><strong>{{ formatPrice(commissionAmount) }}</strong></div>
              <label class="default-toggle" for="choice-default"><span><strong>Selected by default</strong><small>Preselect this choice for customers.</small></span><span class="switch"><input id="choice-default" v-model="field.active" type="checkbox"><span></span></span></label>
              <div class="form-actions"><button v-if="isEditing" type="button" class="cancel-button" @click="clearForm">Cancel edit</button><button type="submit" class="save-button" :disabled="isSubmit"><i :class="isSubmit ? 'fas fa-spinner fa-spin' : (isEditing ? 'fas fa-check' : 'fas fa-plus')" aria-hidden="true"></i> {{ isSubmit ? 'Saving…' : (isEditing ? 'Save choice' : 'Add choice') }}</button></div>
            </div>
          </form>
        </div>
        <div class="modal-footer"><button type="button" class="done-button" @click="closeModalView">Done</button></div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';

export default {
  props: { product_header_id: { type: [Number, String], default: '' } },
  data() {
    return { field: { title: '', price: '', active: false }, details: [], errors: {}, isSubmit: false, isLoading: false, modalInstance: null, activeHeaderId: '', editingId: null };
  },
  computed: {
    isEditing() { return this.editingId !== null; },
    commissionRate() { return 0.15; },
    commissionAmount() { return (Number(this.field.price) || 0) * this.commissionRate; },
  },
  mounted() {
    document.body.appendChild(this.$el);
    this.modalInstance = new Modal(this.$el);
  },
  beforeDestroy() {
    if (this.modalInstance) this.modalInstance.dispose();
    this.$el.remove();
  },
  methods: {
    formatPrice(value) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0); },
    isSelectedByDefault(detail) { return detail.active === true || Number(detail.active) === 1; },
    openModalView() { this.clearForm(); this.modalInstance.show(); },
    closeModalView() { this.modalInstance.hide(); },
    reloadVariantDetails(headerId) {
      this.activeHeaderId = headerId;
      this.isLoading = true;
      axios.get(`/api/merchant/product/variant/${headerId}/details`).then((response) => {
        if (String(this.activeHeaderId) === String(headerId)) this.details = response.data.product_details || [];
      }).catch(() => { toastr.error('Could not load choices. Please try again.'); }).finally(() => { this.isLoading = false; });
    },
    validateForm() {
      const errors = {};
      if (!this.field.title) errors.title = 'Enter a choice name.';
      if (this.field.price === '' || !Number.isFinite(Number(this.field.price)) || Number(this.field.price) < 0) errors.price = 'Enter a valid additional price.';
      this.errors = errors;
      if (Object.keys(errors).length) this.$nextTick(() => this.$el.querySelector('.is-invalid')?.focus());
      return !Object.keys(errors).length;
    },
    submitRecord() {
      if (this.isSubmit || !this.validateForm()) return;
      this.isSubmit = true;
      const url = `/api/merchant/product/variant/${this.activeHeaderId || this.product_header_id}/detail/submit`;
      const request = this.isEditing
        ? axios.put(url, { id: this.editingId, ...this.field })
        : axios.post(url, this.field);
      request.then((response) => {
        if (response.data.status) {
          toastr.success(response.data.message);
          this.reloadVariantDetails(this.activeHeaderId || this.product_header_id);
          this.clearForm();
        } else toastr.info(response.data.message);
      }).catch((error) => {
        if (error.response?.status === 422) {
          const validation = error.response.data.errors || {};
          this.errors = Object.keys(validation).reduce((result, key) => { result[key] = validation[key][0]; return result; }, {});
          toastr.error(error.response.data.message || 'Please check the highlighted fields.');
        } else toastr.error('Could not save this choice. Please try again.');
      }).finally(() => { this.isSubmit = false; });
    },
    editVariantDetails(detail) {
      this.editingId = detail.id;
      this.field = { title: detail.title, price: detail.price, active: this.isSelectedByDefault(detail) };
      this.errors = {};
      this.$nextTick(() => this.$el.querySelector('#choice-title')?.focus());
    },
    deleteVariantDetails(detail) {
      if (!window.confirm(`Delete “${detail.title}”? This cannot be undone.`)) return;
      axios.delete(`/api/merchant/product/variant/detail/${detail.id}/delete`).then((response) => {
        if (response.data.status) {
          toastr.success(response.data.message);
          if (this.editingId === detail.id) this.clearForm();
          this.reloadVariantDetails(this.activeHeaderId || this.product_header_id);
        } else toastr.info(response.data.message);
      }).catch(() => { toastr.error('Could not delete this choice. Please try again.'); });
    },
    clearForm() { this.field = { title: '', price: '', active: false }; this.editingId = null; this.errors = {}; },
  },
};
</script>

<style scoped>
.option-modal .modal-dialog { max-width: 1000px; }.option-modal .modal-content { border: 0; border-radius: 16px; box-shadow: 0 22px 70px rgba(23,27,29,.18); overflow: hidden; }.modal-header { align-items: start; border-bottom: 1px solid #f0ece8; display: flex; gap: .85rem; padding: 1.4rem; }.modal-icon { align-items: center; background: #fff0ee; border-radius: 11px; color: var(--admin-red); display: flex; flex: 0 0 44px; height: 44px; justify-content: center; }.modal-header > div { flex: 1; }.eyebrow { color: var(--admin-red); display: block; font-size: .67rem; font-weight: 800; letter-spacing: .12em; margin-bottom: .25rem; text-transform: uppercase; }.modal-header h3 { color: var(--admin-ink); font-size: 1.13rem; font-weight: 800; margin: 0 0 .2rem; }.modal-header p { color: var(--admin-muted); font-size: .77rem; margin: 0; }.close-button { align-items: center; background: #f6f5f3; border: 0; border-radius: 8px; color: #697176; display: flex; flex: 0 0 32px; height: 32px; justify-content: center; }.close-button:hover { background: #fff0ee; color: var(--admin-red); }
.modal-body { display: grid; gap: 1.2rem; grid-template-columns: minmax(0,1.25fr) minmax(270px,.75fr); padding: 1.4rem; }.choices-panel,.choice-form { border: 1px solid #ece8e4; border-radius: 11px; min-width: 0; overflow: hidden; }.panel-heading { border-bottom: 1px solid #f0ece8; padding: 1rem; }.panel-heading h4,.form-heading h4 { color: var(--admin-ink); font-size: .9rem; font-weight: 800; margin: 0 0 .25rem; }.panel-heading p,.form-heading p { color: var(--admin-muted); font-size: .72rem; margin: 0; }.count-badge { background: #fff0ee; border-radius: 100px; color: var(--admin-red); font-size: .68rem; padding: .25rem .45rem; }.choices-table-wrap { overflow-x: auto; }.choices-table { border-collapse: collapse; font-size: .75rem; min-width: 450px; width: 100%; }.choices-table th { background: #faf9f8; color: #7c8589; font-size: .65rem; font-weight: 800; letter-spacing: .05em; padding: .7rem .9rem; text-align: left; text-transform: uppercase; }.choices-table td { border-top: 1px solid #f0ece8; color: #5b6569; padding: .8rem .9rem; }.choices-table .choice-name { color: var(--admin-ink); font-weight: 700; }.default-pill { background: #e8f6ee; border-radius: 100px; color: #2a8155; font-size: .66rem; font-weight: 700; padding: .3rem .5rem; }.muted-dash { color: #b2b9bc; }.row-actions { white-space: nowrap; }.row-actions button { background: #f7f8f8; border: 0; border-radius: 7px; color: #6e777b; height: 29px; margin-left: .3rem; width: 29px; }.row-actions button:hover { background: #fff0ee; color: var(--admin-red); }.row-actions button.delete-icon:hover { color: #bd2c27; }.empty-row { color: #899196; padding: 2.5rem 1rem !important; text-align: center; }.empty-row i,.empty-row strong,.empty-row span { display: block; }.empty-row i { color: #c7cdd0; font-size: 1.4rem; margin-bottom: .5rem; }.empty-row strong { color: #505a5f; margin-bottom: .25rem; }
.form-heading { align-items: center; background: #fcfbfa; border-bottom: 1px solid #f0ece8; display: flex; gap: .65rem; padding: 1rem; }.heading-icon { align-items: center; background: #fff0ee; border-radius: 8px; color: var(--admin-red); display: flex; flex: 0 0 34px; height: 34px; justify-content: center; }.form-fields { padding: 1rem; }.form-group { margin-bottom: 1rem; }.form-group label { color: #30383c; display: block; font-size: .77rem; font-weight: 700; margin-bottom: .4rem; }.required { color: var(--admin-red); }.form-control { border: 1px solid #dedfdf; border-radius: 9px; font-size: .82rem; min-height: 42px; padding: .55rem .75rem; }.form-control:focus { border-color: var(--admin-red); box-shadow: 0 0 0 3px rgba(239,59,53,.1); }.field-help,.field-error { display: block; font-size: .69rem; margin-top: .35rem; }.field-help { color: #879095; }.field-error { color: #bd2924; }.money-input { position: relative; }.money-input > span { color: #7a8388; font-weight: 700; left: .75rem; position: absolute; top: 50%; transform: translateY(-50%); }.money-input input { padding-left: 1.7rem; }.default-toggle { align-items: center; border: 1px solid #ece8e4; border-radius: 9px; cursor: pointer; display: flex; justify-content: space-between; padding: .7rem; }.default-toggle strong { color: var(--admin-ink); display: block; font-size: .75rem; }.default-toggle small { color: #879095; display: block; font-size: .67rem; margin-top: .1rem; }.switch { display: inline-flex; margin-left: .4rem; position: relative; }.switch input { height: 1px; opacity: 0; position: absolute; width: 1px; }.switch > span { background: #cdd3d5; border-radius: 100px; display: block; height: 22px; position: relative; transition: background .2s; width: 40px; }.switch > span::after { background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.17); content: ''; height: 16px; left: 3px; position: absolute; top: 3px; transition: transform .2s; width: 16px; }.switch input:checked + span { background: var(--admin-red); }.switch input:checked + span::after { transform: translateX(18px); }.switch input:focus + span { box-shadow: 0 0 0 3px rgba(239,59,53,.18); }.commission-note { background: #f8f9fa; border-radius: 8px; color: #758085; font-size: .69rem; margin-top: 1rem; padding: .65rem; }.commission-note i { color: var(--admin-red); margin-right: .25rem; }.form-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1rem; }.cancel-button,.save-button,.done-button { border-radius: 8px; font-size: .76rem; font-weight: 700; min-height: 39px; padding: .55rem .85rem; }.cancel-button,.done-button { background: #fff; border: 1px solid #e5e0dc; color: #555f63; }.save-button { background: var(--admin-red); border: 1px solid var(--admin-red); color: #fff; }.save-button:hover { background: var(--admin-red-dark); }.save-button:disabled { opacity: .65; }.save-button i { margin-right: .25rem; }.modal-footer { background: #fcfbfa; border-top: 1px solid #f0ece8; padding: .85rem 1.4rem; }
.choices-table { min-width: 610px; }
.commission-cell { color: var(--admin-ink) !important; font-weight: 700; white-space: nowrap; }
.commission-note { align-items: center; display: flex; gap: .7rem; justify-content: space-between; margin: 0 0 1rem; padding: .8rem; }
.commission-note > span { color: #505a5f; font-weight: 700; }
.commission-note small { color: #879095; display: block; font-size: .66rem; font-weight: 500; margin: .2rem 0 0 1.25rem; }
.commission-note strong { color: var(--admin-red); font-size: .92rem; white-space: nowrap; }
@media (max-width:767px) { .option-modal .modal-dialog { margin: .6rem; }.modal-body { grid-template-columns: 1fr; padding: 1rem; }.modal-header { padding: 1rem; }.modal-footer { padding: .85rem 1rem; } }
</style>
