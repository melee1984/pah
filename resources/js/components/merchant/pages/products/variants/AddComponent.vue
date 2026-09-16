<template>
  <div id="modalAddVariant" class="modal fade variant-modal" tabindex="-1" role="dialog" aria-labelledby="add-variant-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header"><span class="modal-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></span><div><span class="eyebrow">Product variations</span><h3 id="add-variant-title">Add a variation</h3><p>Create a group of choices for this product.</p></div><button type="button" class="close-button" aria-label="Close" @click="closeModalView"><i class="fas fa-times" aria-hidden="true"></i></button></div>
        <form @submit.prevent="submitRecord" novalidate>
          <div class="modal-body">
            <div class="form-group"><label for="variant-title">Variation name <span class="required">*</span></label><input id="variant-title" v-model.trim="field.title" type="text" maxlength="75" class="form-control" :class="{ 'is-invalid': errors.title }" :aria-invalid="!!errors.title" placeholder="e.g. Size, Extras, or Spice level"><small v-if="errors.title" class="field-error">{{ errors.title }}</small><small v-else class="field-help">Customers will see this name above the choices.</small></div>
            <div class="form-group"><label for="variant-sorting">Display order <span class="optional">Optional</span></label><input id="variant-sorting" v-model="field.sorting" type="number" min="0" step="1" class="form-control" :class="{ 'is-invalid': errors.sorting }" :aria-invalid="!!errors.sorting" placeholder="e.g. 1"><small v-if="errors.sorting" class="field-error">{{ errors.sorting }}</small><small v-else class="field-help">Lower numbers appear first.</small></div>
            <div class="settings-label">Customer choices</div>
            <div class="choice-settings">
              <label class="setting-row" for="variant-active"><span class="setting-icon"><i class="fas fa-eye" aria-hidden="true"></i></span><span class="setting-copy"><strong>Available</strong><small>Show this variation to customers.</small></span><span class="switch"><input id="variant-active" v-model="field.active" type="checkbox"><span></span></span></label>
              <label class="setting-row" for="variant-required"><span class="setting-icon"><i class="fas fa-check-circle" aria-hidden="true"></i></span><span class="setting-copy"><strong>Required selection</strong><small>Customers must choose an option.</small></span><span class="switch"><input id="variant-required" v-model="field.required" type="checkbox"><span></span></span></label>
              <label class="setting-row" for="variant-multiple"><span class="setting-icon"><i class="fas fa-clone" aria-hidden="true"></i></span><span class="setting-copy"><strong>Multiple selections</strong><small>Allow more than one option.</small></span><span class="switch"><input id="variant-multiple" v-model="field.is_multiple" type="checkbox"><span></span></span></label>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="cancel-button" :disabled="isSubmit" @click="closeModalView">Cancel</button><button type="submit" class="save-button" :disabled="isSubmit"><i :class="isSubmit ? 'fas fa-spinner fa-spin' : 'fas fa-plus'" aria-hidden="true"></i> {{ isSubmit ? 'Adding…' : 'Add variation' }}</button></div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';

export default {
  props: { productId: { type: [Number, String], required: true } },
  data() {
    return { field: { title: '', sorting: '', active: true, required: false, is_multiple: false }, errors: {}, isSubmit: false, modalInstance: null };
  },
  mounted() {
    // The editor card clips its contents, so keep the modal at document level.
    document.body.appendChild(this.$el);
    this.modalInstance = new Modal(this.$el);
  },
  beforeDestroy() {
    if (this.modalInstance) this.modalInstance.dispose();
    this.$el.remove();
  },
  methods: {
    openModalView() { this.errors = {}; this.modalInstance.show(); },
    closeModalView() { this.modalInstance.hide(); },
    validateForm() {
      const errors = {};
      if (!this.field.title) errors.title = 'Enter a variation name.';
      if (this.field.sorting !== '' && (!Number.isInteger(Number(this.field.sorting)) || Number(this.field.sorting) < 0)) errors.sorting = 'Enter a whole number of zero or higher.';
      this.errors = errors;
      if (Object.keys(errors).length) this.$nextTick(() => this.$el.querySelector('.is-invalid')?.focus());
      return !Object.keys(errors).length;
    },
    submitRecord() {
      if (this.isSubmit || !this.validateForm()) return;
      this.isSubmit = true;
      axios.post('/api/merchant/product/variant/submit', { product_id: this.productId, ...this.field, sorting: this.field.sorting === '' ? 0 : Number(this.field.sorting) })
        .then((response) => {
          if (response.data.status) {
            toastr.success(response.data.message);
            this.$emit('populatedVariant');
            this.field = { title: '', sorting: '', active: true, required: false, is_multiple: false };
            this.closeModalView();
          } else toastr.info(response.data.message);
        }).catch((error) => {
          if (error.response?.status === 422) {
            const validation = error.response.data.errors || {};
            this.errors = Object.keys(validation).reduce((result, key) => { result[key] = validation[key][0]; return result; }, {});
            toastr.error(error.response.data.message || 'Please check the highlighted fields.');
          } else toastr.error('Could not add this variation. Please try again.');
        }).finally(() => { this.isSubmit = false; });
    },
  },
};
</script>

<style scoped>
.variant-modal .modal-dialog { max-width: 540px; }.variant-modal .modal-content { border: 0; border-radius: 16px; box-shadow: 0 22px 70px rgba(23,27,29,.18); overflow: hidden; }.modal-header { align-items: start; border-bottom: 1px solid #f0ece8; display: flex; gap: .85rem; padding: 1.4rem; }.modal-icon { align-items: center; background: #fff0ee; border-radius: 11px; color: var(--admin-red); display: flex; flex: 0 0 44px; height: 44px; justify-content: center; }.modal-header > div { flex: 1; }.eyebrow { color: var(--admin-red); display: block; font-size: .67rem; font-weight: 800; letter-spacing: .12em; margin-bottom: .25rem; text-transform: uppercase; }.modal-header h3 { color: var(--admin-ink); font-size: 1.13rem; font-weight: 800; margin: 0 0 .2rem; }.modal-header p { color: var(--admin-muted); font-size: .77rem; margin: 0; }.close-button { align-items: center; background: #f6f5f3; border: 0; border-radius: 8px; color: #697176; display: flex; flex: 0 0 32px; height: 32px; justify-content: center; }.close-button:hover { background: #fff0ee; color: var(--admin-red); }
.modal-body { padding: 1.4rem; }.form-group { margin-bottom: 1.2rem; }.form-group label { color: #30383c; display: block; font-size: .79rem; font-weight: 700; margin-bottom: .45rem; }.required { color: var(--admin-red); }.optional { color: #9ca4a8; font-size: .7rem; font-weight: 500; }.form-control { border: 1px solid #dedfdf; border-radius: 9px; font-size: .85rem; min-height: 45px; padding: .65rem .85rem; }.form-control:focus { border-color: var(--admin-red); box-shadow: 0 0 0 3px rgba(239,59,53,.1); }.field-help,.field-error { display: block; font-size: .71rem; margin-top: .38rem; }.field-help { color: #879095; }.field-error { color: #bd2924; }.settings-label { color: #30383c; font-size: .79rem; font-weight: 800; margin-bottom: .55rem; }.choice-settings { border: 1px solid #ece8e4; border-radius: 10px; overflow: hidden; }.setting-row { align-items: center; cursor: pointer; display: flex; gap: .75rem; margin: 0; padding: .85rem; }.setting-row + .setting-row { border-top: 1px solid #f0ece8; }.setting-icon { align-items: center; background: #f7f4f1; border-radius: 8px; color: #8c7770; display: flex; flex: 0 0 32px; height: 32px; justify-content: center; }.setting-copy { flex: 1; }.setting-copy strong { color: var(--admin-ink); display: block; font-size: .77rem; }.setting-copy small { color: #879095; display: block; font-size: .68rem; margin-top: .1rem; }.switch { display: inline-flex; position: relative; }.switch input { height: 1px; opacity: 0; position: absolute; width: 1px; }.switch > span { background: #cdd3d5; border-radius: 100px; display: block; height: 22px; position: relative; transition: background .2s; width: 40px; }.switch > span::after { background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.17); content: ''; height: 16px; left: 3px; position: absolute; top: 3px; transition: transform .2s; width: 16px; }.switch input:checked + span { background: var(--admin-red); }.switch input:checked + span::after { transform: translateX(18px); }.switch input:focus + span { box-shadow: 0 0 0 3px rgba(239,59,53,.18); }
.modal-footer { background: #fcfbfa; border-top: 1px solid #f0ece8; display: flex; gap: .55rem; justify-content: flex-end; padding: 1rem 1.4rem; }.cancel-button,.save-button { border-radius: 9px; font-size: .8rem; font-weight: 700; min-height: 42px; padding: .6rem 1rem; }.cancel-button { background: #fff; border: 1px solid #e5e0dc; color: #555f63; }.save-button { background: var(--admin-red); border: 1px solid var(--admin-red); color: #fff; }.save-button:hover { background: var(--admin-red-dark); }.save-button:disabled { opacity: .65; }.save-button i { margin-right: .3rem; }
@media (max-width:575px) { .variant-modal .modal-dialog { margin: .6rem; }.modal-header,.modal-body { padding: 1rem; }.modal-footer { padding: .9rem 1rem; } }
</style>
