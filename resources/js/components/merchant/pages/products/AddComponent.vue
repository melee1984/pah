<template>
  <div class="add-product">
    <header class="add-header">
      <button type="button" class="back-link" @click="$emit('actionProduct', 'view')"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to products</button>
      <span class="eyebrow">Catalog management</span>
      <h2>Add a product</h2>
      <p>Give customers the details they need to choose your product.</p>
    </header>

    <form class="add-layout" @submit.prevent="onSubmit" novalidate>
      <main>
        <section class="form-card">
          <div class="section-heading"><span class="heading-icon"><i class="fas fa-align-left" aria-hidden="true"></i></span><div><h3>Product details</h3><p>Start with a name and a useful description.</p></div></div>
          <div class="card-fields">
            <div class="form-group">
              <label for="product-title">Product name <span class="required">*</span></label>
              <input id="product-title" v-model.trim="field.title" type="text" maxlength="75" class="form-control" :class="{ 'is-invalid': errors.title }" :aria-invalid="!!errors.title" placeholder="e.g. Classic Chicken Burger">
              <small v-if="errors.title" class="field-error">{{ errors.title }}</small><small v-else class="field-help">Choose a name customers will recognize. Up to 75 characters.</small>
            </div>
            <div class="form-group last">
              <label for="product-description">Description <span class="required">*</span></label>
              <textarea id="product-description" v-model.trim="field.description" rows="5" class="form-control" :class="{ 'is-invalid': errors.description }" :aria-invalid="!!errors.description" placeholder="What makes this product special? Include ingredients, serving size, or other useful details."></textarea>
              <small v-if="errors.description" class="field-error">{{ errors.description }}</small>
            </div>
          </div>
        </section>

        <section class="form-card">
          <div class="section-heading"><span class="heading-icon"><i class="fas fa-tags" aria-hidden="true"></i></span><div><h3>Category &amp; pricing</h3><p>Place your product in the right section and set its price.</p></div></div>
          <div class="card-fields">
            <div class="form-group">
              <label for="product-category">Category <span class="required">*</span></label>
              <select id="product-category" v-model="field.category_id" class="form-control" :class="{ 'is-invalid': errors.category_id }" :aria-invalid="!!errors.category_id"><option value="">Select a category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select>
              <small v-if="errors.category_id" class="field-error">{{ errors.category_id }}</small>
            </div>
            <div class="price-grid">
              <div class="form-group last"><label for="product-price">Regular price <span class="required">*</span></label><div class="money-input"><span>₱</span><input id="product-price" v-model="field.price" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.price }" :aria-invalid="!!errors.price" placeholder="0.00"></div><small v-if="errors.price" class="field-error">{{ errors.price }}</small></div>
              <div class="form-group last"><label for="product-markdown">Sale price <em>Optional</em></label><div class="money-input"><span>₱</span><input id="product-markdown" v-model="field.markdown_price" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.markdown_price }" :aria-invalid="!!errors.markdown_price" placeholder="0.00"></div><small v-if="errors.markdown_price" class="field-error">{{ errors.markdown_price }}</small><small v-else class="field-help">Use a lower price when this product is on sale.</small></div>
            </div>
            <div class="commission-note"><i class="fas fa-info-circle" aria-hidden="true"></i> Commission is calculated automatically at {{ commissionPercent }}% when you save.</div>
          </div>
        </section>

        <section class="form-card availability"><div class="section-heading"><span class="heading-icon"><i class="fas fa-eye" aria-hidden="true"></i></span><div><h3>Available to customers</h3><p>{{ field.active ? 'This product will appear in your catalog.' : 'This product will be hidden until you turn it on.' }}</p></div></div><label class="switch" for="product-active"><input id="product-active" v-model="field.active" type="checkbox"><span></span><span class="sr-only">Available to customers</span></label></section>
        <div class="form-actions"><button type="button" class="cancel-button" :disabled="isSubmit" @click="$emit('actionProduct', 'view')">Cancel</button><button type="submit" class="save-button" :disabled="isSubmit"><i :class="isSubmit ? 'fas fa-spinner fa-spin' : 'fas fa-check'" aria-hidden="true"></i> {{ isSubmit ? 'Saving product…' : 'Save product' }}</button></div>
      </main>

      <aside class="preview-column">
        <div class="preview-card"><div class="preview-heading">Listing preview <i class="fas fa-eye" aria-hidden="true"></i></div><div class="preview-art"><i class="fas fa-utensils" aria-hidden="true"></i><span>Product photo can be added after saving</span></div><div class="preview-body"><span class="preview-category">{{ selectedCategoryName }}</span><h3>{{ field.title || 'Your product name' }}</h3><p>{{ field.description || 'Your product description will appear here as you type.' }}</p><div class="preview-price"><strong>{{ formatPrice(field.markdown_price || field.price) }}</strong><span v-if="field.markdown_price && field.price" class="old-price">{{ formatPrice(field.price) }}</span></div></div><div class="preview-footer"><span class="status-dot" :class="{ inactive: !field.active }"></span>{{ field.active ? 'Available' : 'Unavailable' }}<span>Preview only</span></div></div>
        <p class="preview-tip"><i class="fas fa-lightbulb" aria-hidden="true"></i> After saving, open the product to add a photo and variations.</p>
      </aside>
    </form>
  </div>
</template>

<script>
export default {
  props: {
    categories: { type: Array, default: () => [] },
    merchant: { type: Object, default: () => ({}) },
  },
  data() {
    return { field: { title: '', description: '', category_id: '', price: '', markdown_price: '', active: true }, errors: {}, isSubmit: false };
  },
  computed: {
    commissionPercent() { return Number(this.merchant.percentage) === 10 ? 10 : 15; },
    selectedCategoryName() {
      const category = this.categories.find((item) => String(item.id) === String(this.field.category_id));
      return category ? category.name : 'Select a category';
    },
  },
  methods: {
    formatPrice(value) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0); },
    validateForm() {
      const errors = {};
      if (!this.field.title) errors.title = 'Enter a product name.';
      if (!this.field.description) errors.description = 'Enter a description.';
      if (!this.field.category_id) errors.category_id = 'Choose a category.';
      if (this.field.price === '' || !Number.isFinite(Number(this.field.price)) || Number(this.field.price) < 0) errors.price = 'Enter a valid price.';
      if (this.field.markdown_price !== '' && (!Number.isFinite(Number(this.field.markdown_price)) || Number(this.field.markdown_price) < 0 || Number(this.field.markdown_price) >= Number(this.field.price))) errors.markdown_price = 'Enter a sale price below the regular price.';
      this.errors = errors;
      if (Object.keys(errors).length) this.$nextTick(() => this.$el.querySelector('.is-invalid')?.focus());
      return !Object.keys(errors).length;
    },
    onSubmit() {
      if (this.isSubmit || !this.validateForm()) return;
      this.isSubmit = true;
      axios.post('/api/merchant/product/submit', {
        ...this.field,
        markdown_price: this.field.markdown_price === '' ? null : this.field.markdown_price,
      }).then((response) => {
        if (response.data.status) {
          toastr.success(response.data.message);
          this.$emit('fetchData', 1);
          this.$emit('actionProduct', 'view');
        } else toastr.info(response.data.message);
      }).catch((error) => {
        if (error.response && error.response.status === 422) {
          const validation = error.response.data.errors || {};
          this.errors = Object.keys(validation).reduce((result, key) => { result[key] = validation[key][0]; return result; }, {});
          toastr.error(error.response.data.message || 'Please check the highlighted fields.');
        } else toastr.error('Could not save this product. Please try again.');
      }).finally(() => { this.isSubmit = false; });
    },
  },
};
</script>

<style scoped>
.add-product { color: var(--admin-ink); padding-bottom: 2rem; }
.add-header { margin-bottom: 1.4rem; }
.back-link { background: none; border: 0; color: var(--admin-muted); display: block; font-size: .8rem; font-weight: 700; margin-bottom: 1.25rem; padding: 0; }
.back-link:hover { color: var(--admin-red); }.back-link i { margin-right: .35rem; }
.eyebrow { color: var(--admin-red); display: block; font-size: .7rem; font-weight: 800; letter-spacing: .12em; margin-bottom: .3rem; text-transform: uppercase; }
.add-header h2 { font-size: 1.75rem; font-weight: 800; margin: 0 0 .3rem; }.add-header p { color: var(--admin-muted); margin: 0; }
.add-layout { align-items: start; display: grid; gap: 1.25rem; grid-template-columns: minmax(0,1fr) 310px; }.add-layout main { min-width: 0; }
.form-card,.preview-card { background: #fff; border: 1px solid var(--admin-line); border-radius: 15px; box-shadow: 0 5px 22px rgba(23,27,29,.04); margin-bottom: 1rem; overflow: hidden; }
.section-heading { align-items: center; border-bottom: 1px solid #f0ece8; display: flex; gap: .85rem; padding: 1.15rem 1.35rem; }.heading-icon { align-items: center; background: #fff0ee; border-radius: 10px; color: var(--admin-red); display: flex; flex: 0 0 40px; height: 40px; justify-content: center; }
.section-heading h3 { font-size: 1rem; font-weight: 800; margin: 0 0 .2rem; }.section-heading p { color: var(--admin-muted); font-size: .78rem; margin: 0; }
.card-fields { padding: 1.35rem; }.form-group { margin-bottom: 1.25rem; }.form-group.last { margin-bottom: 0; }
label { color: #30383c; display: block; font-size: .79rem; font-weight: 700; margin-bottom: .45rem; }.required { color: var(--admin-red); }label em { color: #9ca4a8; font-size: .7rem; font-style: normal; font-weight: 500; margin-left: .25rem; }
.form-control { background: #fff; border: 1px solid #dedfdf; border-radius: 9px; color: var(--admin-ink); font-size: .86rem; min-height: 45px; padding: .65rem .85rem; }.form-control::placeholder { color: #a4aaad; }.form-control:focus { border-color: var(--admin-red); box-shadow: 0 0 0 3px rgba(239,59,53,.1); }textarea.form-control { min-height: 125px; resize: vertical; }
.field-help,.field-error { display: block; font-size: .72rem; margin-top: .4rem; }.field-help { color: #879095; }.field-error { color: #bd2924; }
.price-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2,minmax(0,1fr)); }.money-input { position: relative; }.money-input > span { color: #7a8388; font-weight: 700; left: .85rem; position: absolute; top: 50%; transform: translateY(-50%); }.money-input input { padding-left: 1.85rem; }
.commission-note { align-items: center; background: #f8f9fa; border-radius: 9px; color: #6e777b; display: flex; font-size: .74rem; gap: .55rem; margin-top: 1.1rem; padding: .75rem .85rem; }.commission-note i { color: var(--admin-red); }
.availability { align-items: center; display: flex; justify-content: space-between; padding-right: 1.35rem; }.availability .section-heading { border: 0; }.switch { cursor: pointer; margin: 0 0 0 1rem; position: relative; }.switch input { height: 1px; opacity: 0; position: absolute; width: 1px; }.switch > span:first-of-type { background: #cdd3d5; border-radius: 100px; display: block; height: 24px; position: relative; transition: background .2s; width: 44px; }.switch > span:first-of-type::after { background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.17); content: ''; height: 18px; left: 3px; position: absolute; top: 3px; transition: transform .2s; width: 18px; }.switch input:checked + span { background: var(--admin-red); }.switch input:checked + span::after { transform: translateX(20px); }.switch input:focus + span { box-shadow: 0 0 0 3px rgba(239,59,53,.18); }
.form-actions { display: flex; gap: .65rem; justify-content: flex-end; padding-top: .35rem; }.cancel-button,.save-button { border-radius: 9px; font-size: .83rem; font-weight: 700; min-height: 44px; padding: .65rem 1.15rem; }.cancel-button { background: #fff; border: 1px solid var(--admin-line); color: #555f63; }.save-button { background: var(--admin-red); border: 1px solid var(--admin-red); box-shadow: 0 9px 20px rgba(239,59,53,.17); color: #fff; }.save-button:hover { background: var(--admin-red-dark); }.save-button:disabled { cursor: wait; opacity: .7; }
.preview-column { position: sticky; top: 85px; }.preview-heading { align-items: center; display: flex; font-size: .78rem; font-weight: 800; justify-content: space-between; padding: 1rem 1.1rem; }.preview-heading i { color: #a8afb2; }.preview-art { align-items: center; background: linear-gradient(135deg,#f7efeb,#fff6f1); color: #bd8c80; display: flex; flex-direction: column; gap: .65rem; height: 150px; justify-content: center; text-align: center; }.preview-art i { font-size: 2rem; opacity: .55; }.preview-art span { font-size: .69rem; max-width: 160px; }.preview-body { padding: 1.1rem; }.preview-category { color: var(--admin-red); font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }.preview-body h3 { font-size: 1rem; font-weight: 800; margin: .4rem 0 .5rem; overflow-wrap: anywhere; }.preview-body p { color: #879095; display: -webkit-box; font-size: .76rem; line-height: 1.5; margin: 0 0 1rem; overflow: hidden; overflow-wrap: anywhere; -webkit-box-orient: vertical; -webkit-line-clamp: 3; }.preview-price { align-items: baseline; display: flex; gap: .5rem; }.preview-price strong { font-size: 1.08rem; }.old-price { color: #9ca4a8; font-size: .75rem; text-decoration: line-through; }.preview-footer { align-items: center; border-top: 1px solid #f0ece8; color: #557262; display: flex; font-size: .71rem; font-weight: 700; gap: .35rem; padding: .85rem 1.1rem; }.preview-footer > span:last-child { color: #a0a8ac; font-weight: 500; margin-left: auto; }.status-dot { background: #31ae70; border-radius: 50%; height: 7px; width: 7px; }.status-dot.inactive { background: #9da5aa; }.preview-tip { color: #879095; display: flex; font-size: .72rem; gap: .5rem; line-height: 1.5; margin: 0 .3rem; }.preview-tip i { color: #d6a048; margin-top: .15rem; }
@media (max-width:991px) { .add-layout { grid-template-columns: minmax(0,1fr); }.preview-column { position: static; }.preview-card { max-width: 500px; } }
@media (max-width:575px) { .add-header h2 { font-size: 1.4rem; }.card-fields,.section-heading { padding: 1rem; }.price-grid { grid-template-columns: 1fr; }.price-grid .form-group:first-child { margin-bottom: 1rem; }.availability .heading-icon { display: none; }.availability { padding-right: 1rem; }.form-actions { display: grid; grid-template-columns: 1fr 1fr; } }
</style>
