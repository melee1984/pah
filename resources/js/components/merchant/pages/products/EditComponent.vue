<template>
  <div class="product-editor">
    <header class="editor-header">
      <a href="/merchant/products" class="back-link"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to products</a>
      <span class="eyebrow">Catalog management</span>
      <h2>Edit product</h2>
      <p>Update the details customers see in your catalog.</p>
    </header>

    <div class="editor-layout">
      <form class="editor-form" @submit.prevent="onSubmit" novalidate>
        <section class="editor-card">
          <div class="section-heading"><span class="heading-icon"><i class="fas fa-align-left" aria-hidden="true"></i></span><div><h3>Product details</h3><p>Keep the name and description clear and useful.</p></div></div>
          <div class="card-fields">
            <div class="form-group"><label for="edit-title">Product name <span class="required">*</span></label><input id="edit-title" v-model.trim="field.title" type="text" maxlength="75" class="form-control" :class="{ 'is-invalid': errors.title }" :aria-invalid="!!errors.title" placeholder="e.g. Classic Chicken Burger"><small v-if="errors.title" class="field-error">{{ errors.title }}</small><small v-else class="field-help">Up to 75 characters.</small></div>
            <div class="form-group last"><label for="edit-description">Description <span class="required">*</span></label><textarea id="edit-description" v-model.trim="field.description" rows="5" class="form-control" :class="{ 'is-invalid': errors.description }" :aria-invalid="!!errors.description" placeholder="Describe ingredients, serving size, and other useful details."></textarea><small v-if="errors.description" class="field-error">{{ errors.description }}</small></div>
          </div>
        </section>

        <section class="editor-card">
          <div class="section-heading"><span class="heading-icon"><i class="fas fa-tags" aria-hidden="true"></i></span><div><h3>Category &amp; pricing</h3><p>Set where the product appears and how much it costs.</p></div></div>
          <div class="card-fields">
            <div class="form-group"><label for="edit-category">Category <span class="required">*</span></label><select id="edit-category" v-model="field.category_id" class="form-control" :class="{ 'is-invalid': errors.category_id }" :aria-invalid="!!errors.category_id"><option value="">Select a category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><small v-if="errors.category_id" class="field-error">{{ errors.category_id }}</small></div>
            <div class="price-grid">
              <div class="form-group last"><label for="edit-price">Regular price <span class="required">*</span></label><div class="money-input"><span>₱</span><input id="edit-price" v-model="field.price" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.price }" :aria-invalid="!!errors.price" placeholder="0.00"></div><small v-if="errors.price" class="field-error">{{ errors.price }}</small></div>
              <div class="form-group last"><label for="edit-markdown">Sale price <em>Optional</em></label><div class="money-input"><span>₱</span><input id="edit-markdown" v-model="field.markdown_price" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.markdown_price }" :aria-invalid="!!errors.markdown_price" placeholder="0.00"></div><small v-if="errors.markdown_price" class="field-error">{{ errors.markdown_price }}</small><small v-else class="field-help">Use a lower price when this product is on sale.</small></div>
            </div>
            <div class="commission-note"><i class="fas fa-info-circle" aria-hidden="true"></i> Commission is calculated automatically at {{ commissionPercent }}% when you save.</div>
          </div>
        </section>

        <section class="editor-card availability"><div class="section-heading"><span class="heading-icon"><i class="fas fa-eye" aria-hidden="true"></i></span><div><h3>Available to customers</h3><p>{{ field.active ? 'This product appears in your catalog.' : 'This product is hidden from customers.' }}</p></div></div><label class="switch" for="edit-active"><input id="edit-active" v-model="field.active" type="checkbox"><span></span><span class="sr-only">Available to customers</span></label></section>
        <div class="form-actions"><a href="/merchant/products" class="cancel-button">Cancel</a><button type="submit" class="save-button" :disabled="isSubmit"><i :class="isSubmit ? 'fas fa-spinner fa-spin' : 'fas fa-check'" aria-hidden="true"></i> {{ isSubmit ? 'Saving changes…' : 'Save changes' }}</button></div>
      </form>

      <aside class="preview-column"><div class="preview-card"><div class="preview-heading">Listing preview <i class="fas fa-eye" aria-hidden="true"></i></div><div class="preview-art"><img v-if="field.imgname && !imageError" :src="field.imgname + '&s=banner'" :alt="field.title || 'Product photo'" @error="imageError = true"><div v-else class="image-placeholder"><i class="fas fa-utensils" aria-hidden="true"></i><span>No product photo yet</span></div></div><div class="preview-body"><span class="preview-category">{{ selectedCategoryName }}</span><h3>{{ field.title || 'Your product name' }}</h3><p>{{ field.description || 'Your product description will appear here.' }}</p><div class="preview-price"><strong>{{ formatPrice(field.markdown_price || field.price) }}</strong><span v-if="field.markdown_price && field.price" class="old-price">{{ formatPrice(field.price) }}</span></div></div><div class="preview-footer"><span class="status-dot" :class="{ inactive: !field.active }"></span>{{ field.active ? 'Available' : 'Unavailable' }}<span>Preview only</span></div></div></aside>
    </div>

    <section class="editor-card properties-card">
      <div class="properties-heading"><div><span class="eyebrow">More options</span><h3>Product properties</h3><p>Manage variations, photos, and product settings.</p></div></div>
      <div class="properties-tabs" role="tablist" aria-label="Product properties"><button v-for="tab in tabs" :key="tab.id" type="button" role="tab" :id="'tab-' + tab.id" :aria-controls="'panel-' + tab.id" :aria-selected="activeTab === tab.id" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id"><i :class="tab.icon" aria-hidden="true"></i> {{ tab.label }}</button></div>
      <div v-show="activeTab === 'variations'" id="panel-variations" role="tabpanel" aria-labelledby="tab-variations" class="properties-content variations-panel"><product-variants-view :product="product"></product-variants-view></div>
      <div v-show="activeTab === 'photo'" id="panel-photo" role="tabpanel" aria-labelledby="tab-photo" class="properties-content photo-panel">
        <div class="photo-copy"><h4>Product image — 1200 × 1200 px</h4><p>Use a square JPG or PNG image. File must be under 1 MB.</p><div class="upload-actions"><label for="edit-photo" class="choose-button"><i class="fas fa-image" aria-hidden="true"></i> Choose photo</label><input id="edit-photo" ref="photoInput" type="file" accept="image/jpeg,image/png" class="sr-only" @change="onFileSelected"><button type="button" class="save-button" :disabled="!fileImage || isUploading" @click="onUploadImage"><i :class="isUploading ? 'fas fa-spinner fa-spin' : 'fas fa-upload'" aria-hidden="true"></i> {{ isUploading ? 'Uploading…' : 'Upload photo' }}</button></div><small v-if="fileImage" class="selected-file">Selected: {{ fileImage.name }}</small><small v-if="imageUploadError" class="field-error">{{ imageUploadError }}</small></div>
        <div class="photo-sample"><img v-if="field.imgname && !imageError" :src="field.imgname + '&s=orig'" :alt="field.title || 'Current product photo'" @error="imageError = true"><div v-else><i class="fas fa-image" aria-hidden="true"></i><span>No photo uploaded</span></div></div>
      </div>
      <div v-show="activeTab === 'settings'" id="panel-settings" role="tabpanel" aria-labelledby="tab-settings" class="properties-content"><div class="danger-zone"><span class="danger-icon"><i class="fas fa-trash-alt" aria-hidden="true"></i></span><div><h4>Delete this product</h4><p>This removes the product from your catalog. This action cannot be undone.</p></div><button type="button" class="delete-button" :disabled="isDeleting" @click="onDelete">{{ isDeleting ? 'Deleting…' : 'Delete product' }}</button></div></div>
    </section>
  </div>
</template>

<script>
import VariantsComponent from './VariantsComponent.vue';

export default {
  components: { 'product-variants-view': VariantsComponent },
  props: { product: { type: Object, required: true } },
  data() {
    return {
      field: {
        ...this.product,
        category_id: this.product.category_id || '',
        markdown_price: this.product.markdown_price ?? '',
        active: this.product.active === true || Number(this.product.active) === 1,
      },
      categories: [], errors: {}, isSubmit: false, isUploading: false, isDeleting: false,
      fileImage: null, imageUploadError: '', imageError: false, activeTab: 'variations',
      tabs: [
        { id: 'variations', label: 'Variations', icon: 'fas fa-layer-group' },
        { id: 'photo', label: 'Photo', icon: 'fas fa-image' },
        { id: 'settings', label: 'Settings', icon: 'fas fa-cog' },
      ],
    };
  },
  computed: {
    commissionPercent() { return Number(this.product.user?.merchant?.percentage) === 10 ? 10 : 15; },
    selectedCategoryName() {
      const category = this.categories.find((item) => String(item.id) === String(this.field.category_id));
      return category ? category.name : (this.product.category?.name || 'Select a category');
    },
  },
  mounted() { this.fetchRecord(); },
  methods: {
    formatPrice(value) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0); },
    fetchRecord() {
      axios.get('/api/merchant/product/requirement').then((response) => { this.categories = response.data.categories || []; }).catch(() => { toastr.error('Could not load categories. Please refresh the page.'); });
    },
    validateForm() {
      const errors = {};
      if (!this.field.title?.trim()) errors.title = 'Enter a product name.';
      if (!this.field.description?.trim()) errors.description = 'Enter a description.';
      if (!this.field.category_id) errors.category_id = 'Choose a category.';
      if (this.field.price === '' || this.field.price === null || !Number.isFinite(Number(this.field.price)) || Number(this.field.price) < 0) errors.price = 'Enter a valid price.';
      if (this.field.markdown_price !== '' && this.field.markdown_price !== null && (!Number.isFinite(Number(this.field.markdown_price)) || Number(this.field.markdown_price) < 0 || Number(this.field.markdown_price) >= Number(this.field.price))) errors.markdown_price = 'Enter a sale price below the regular price.';
      this.errors = errors;
      if (Object.keys(errors).length) this.$nextTick(() => this.$el.querySelector('.is-invalid')?.focus());
      return !Object.keys(errors).length;
    },
    onSubmit() {
      if (this.isSubmit || !this.validateForm()) return;
      this.isSubmit = true;
      axios.put(`/api/merchant/product/${this.product.id}/submit`, {
        title: this.field.title, description: this.field.description, category_id: this.field.category_id,
        price: this.field.price, markdown_price: this.field.markdown_price === '' ? null : this.field.markdown_price,
        active: this.field.active,
      }).then((response) => {
        if (response.data.status) { toastr.success(response.data.message); window.location.href = '/merchant/products'; }
        else toastr.info(response.data.message);
      }).catch((error) => {
        if (error.response?.status === 422) {
          const validation = error.response.data.errors || {};
          this.errors = Object.keys(validation).reduce((result, key) => { result[key] = validation[key][0]; return result; }, {});
          toastr.error(error.response.data.message || 'Please check the highlighted fields.');
        } else toastr.error('Could not save your changes. Please try again.');
      }).finally(() => { this.isSubmit = false; });
    },
    onFileSelected(event) {
      this.imageUploadError = '';
      const file = event.target.files[0];
      if (!file) { this.fileImage = null; return; }
      if (!['image/jpeg', 'image/png'].includes(file.type) || file.size >= 1024 * 1024) {
        this.fileImage = null;
        this.imageUploadError = 'Choose a JPG or PNG image under 1 MB.';
        event.target.value = '';
        return;
      }
      this.fileImage = file;
    },
    onUploadImage() {
      if (!this.fileImage || this.isUploading) return;
      this.isUploading = true;
      this.imageUploadError = '';
      const formData = new FormData();
      formData.append('file', this.fileImage);
      axios.post(`/api/merchant/product/${this.product.id}/upload/submit`, formData).then((response) => {
        if (response.data.status) {
          toastr.success(response.data.message);
          this.field.imgname = response.data.img;
          this.imageError = false;
          this.fileImage = null;
          if (this.$refs.photoInput) this.$refs.photoInput.value = '';
        } else this.imageUploadError = response.data.message || 'Could not upload the photo.';
      }).catch((error) => {
        this.imageUploadError = error.response?.data?.errors?.file?.[0] || 'Could not upload the photo. Please try again.';
      }).finally(() => { this.isUploading = false; });
    },
    onDelete() {
      if (this.isDeleting || !window.confirm('Delete this product? This action cannot be undone.')) return;
      this.isDeleting = true;
      axios.delete(`/api/merchant/product/${this.product.id}/delete`).then((response) => {
        if (response.data.status) { toastr.success(response.data.message); window.location.href = '/merchant/products'; }
        else toastr.info(response.data.message);
      }).catch(() => { toastr.error('Could not delete this product. Please try again.'); }).finally(() => { this.isDeleting = false; });
    },
  },
};
</script>

<style scoped>
.product-editor { color: var(--admin-ink); padding-bottom: 2rem; }.editor-header { margin-bottom: 1.4rem; }.back-link { color: var(--admin-muted); display: block; font-size: .8rem; font-weight: 700; margin-bottom: 1.25rem; }.back-link:hover { color: var(--admin-red); }.back-link i { margin-right: .35rem; }.eyebrow { color: var(--admin-red); display: block; font-size: .7rem; font-weight: 800; letter-spacing: .12em; margin-bottom: .3rem; text-transform: uppercase; }.editor-header h2 { font-size: 1.75rem; font-weight: 800; margin: 0 0 .3rem; }.editor-header p { color: var(--admin-muted); margin: 0; }
.editor-layout { align-items: start; display: grid; gap: 1.25rem; grid-template-columns: minmax(0,1fr) 310px; }.editor-form { min-width: 0; }.editor-card,.preview-card { background: #fff; border: 1px solid var(--admin-line); border-radius: 15px; box-shadow: 0 5px 22px rgba(23,27,29,.04); margin-bottom: 1rem; overflow: hidden; }.section-heading { align-items: center; border-bottom: 1px solid #f0ece8; display: flex; gap: .85rem; padding: 1.15rem 1.35rem; }.heading-icon { align-items: center; background: #fff0ee; border-radius: 10px; color: var(--admin-red); display: flex; flex: 0 0 40px; height: 40px; justify-content: center; }.section-heading h3 { font-size: 1rem; font-weight: 800; margin: 0 0 .2rem; }.section-heading p { color: var(--admin-muted); font-size: .78rem; margin: 0; }.card-fields { padding: 1.35rem; }.form-group { margin-bottom: 1.25rem; }.form-group.last { margin-bottom: 0; }
label { color: #30383c; display: block; font-size: .79rem; font-weight: 700; margin-bottom: .45rem; }.required { color: var(--admin-red); }label em { color: #9ca4a8; font-size: .7rem; font-style: normal; font-weight: 500; margin-left: .25rem; }.form-control { background: #fff; border: 1px solid #dedfdf; border-radius: 9px; color: var(--admin-ink); font-size: .86rem; min-height: 45px; padding: .65rem .85rem; }.form-control::placeholder { color: #a4aaad; }.form-control:focus { border-color: var(--admin-red); box-shadow: 0 0 0 3px rgba(239,59,53,.1); }textarea.form-control { min-height: 125px; resize: vertical; }.field-help,.field-error { display: block; font-size: .72rem; margin-top: .4rem; }.field-help { color: #879095; }.field-error { color: #bd2924; }.price-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2,minmax(0,1fr)); }.money-input { position: relative; }.money-input > span { color: #7a8388; font-weight: 700; left: .85rem; position: absolute; top: 50%; transform: translateY(-50%); }.money-input input { padding-left: 1.85rem; }.commission-note { align-items: center; background: #f8f9fa; border-radius: 9px; color: #6e777b; display: flex; font-size: .74rem; gap: .55rem; margin-top: 1.1rem; padding: .75rem .85rem; }.commission-note i { color: var(--admin-red); }
.availability { align-items: center; display: flex; justify-content: space-between; padding-right: 1.35rem; }.availability .section-heading { border: 0; }.switch { cursor: pointer; margin: 0 0 0 1rem; position: relative; }.switch input { height: 1px; opacity: 0; position: absolute; width: 1px; }.switch > span:first-of-type { background: #cdd3d5; border-radius: 100px; display: block; height: 24px; position: relative; transition: background .2s; width: 44px; }.switch > span:first-of-type::after { background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.17); content: ''; height: 18px; left: 3px; position: absolute; top: 3px; transition: transform .2s; width: 18px; }.switch input:checked + span { background: var(--admin-red); }.switch input:checked + span::after { transform: translateX(20px); }.switch input:focus + span { box-shadow: 0 0 0 3px rgba(239,59,53,.18); }.form-actions { display: flex; gap: .65rem; justify-content: flex-end; padding: .35rem 0 1.2rem; }.cancel-button,.save-button { align-items: center; border-radius: 9px; display: inline-flex; font-size: .83rem; font-weight: 700; justify-content: center; min-height: 44px; padding: .65rem 1.15rem; }.cancel-button { background: #fff; border: 1px solid var(--admin-line); color: #555f63; }.save-button { background: var(--admin-red); border: 1px solid var(--admin-red); box-shadow: 0 9px 20px rgba(239,59,53,.17); color: #fff; }.save-button:hover { background: var(--admin-red-dark); color: #fff; }.save-button:disabled { cursor: wait; opacity: .6; }.save-button i { margin-right: .35rem; }
.preview-column { position: sticky; top: 85px; }.preview-heading { align-items: center; display: flex; font-size: .78rem; font-weight: 800; justify-content: space-between; padding: 1rem 1.1rem; }.preview-heading i { color: #a8afb2; }.preview-art { background: linear-gradient(135deg,#f7efeb,#fff6f1); height: 150px; overflow: hidden; }.preview-art img { height: 100%; object-fit: cover; width: 100%; }.image-placeholder { align-items: center; color: #bd8c80; display: flex; flex-direction: column; gap: .65rem; height: 100%; justify-content: center; text-align: center; }.image-placeholder i { font-size: 2rem; opacity: .55; }.image-placeholder span { font-size: .69rem; }.preview-body { padding: 1.1rem; }.preview-category { color: var(--admin-red); font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }.preview-body h3 { font-size: 1rem; font-weight: 800; margin: .4rem 0 .5rem; overflow-wrap: anywhere; }.preview-body p { color: #879095; display: -webkit-box; font-size: .76rem; line-height: 1.5; margin: 0 0 1rem; overflow: hidden; overflow-wrap: anywhere; -webkit-box-orient: vertical; -webkit-line-clamp: 3; }.preview-price { align-items: baseline; display: flex; gap: .5rem; }.preview-price strong { font-size: 1.08rem; }.old-price { color: #9ca4a8; font-size: .75rem; text-decoration: line-through; }.preview-footer { align-items: center; border-top: 1px solid #f0ece8; color: #557262; display: flex; font-size: .71rem; font-weight: 700; gap: .35rem; padding: .85rem 1.1rem; }.preview-footer > span:last-child { color: #a0a8ac; font-weight: 500; margin-left: auto; }.status-dot { background: #31ae70; border-radius: 50%; height: 7px; width: 7px; }.status-dot.inactive { background: #9da5aa; }
.properties-card { margin-top: .5rem; }.properties-heading { padding: 1.3rem 1.4rem .9rem; }.properties-heading h3 { font-size: 1.1rem; font-weight: 800; margin: 0 0 .2rem; }.properties-heading p { color: var(--admin-muted); font-size: .78rem; margin: 0; }.properties-tabs { border-bottom: 1px solid var(--admin-line); display: flex; gap: .25rem; padding: 0 1.25rem; }.properties-tabs button { background:none; border:0; border-bottom:2px solid transparent; color:#778185; font-size:.8rem; font-weight:700; margin-bottom:-1px; padding:.85rem 1rem; }.properties-tabs button:hover { color: var(--admin-red); }.properties-tabs button.active { border-bottom-color: var(--admin-red); color: var(--admin-red); }.properties-tabs i { margin-right: .4rem; }.properties-content { padding: 1.4rem; }.variations-panel { overflow-x: auto; }.photo-panel { align-items: center; display: grid; gap: 2rem; grid-template-columns: minmax(0,1fr) 220px; }.photo-copy h4,.danger-zone h4 { font-size: .95rem; font-weight: 800; margin: 0 0 .4rem; }.photo-copy p,.danger-zone p { color: var(--admin-muted); font-size: .78rem; line-height: 1.5; margin: 0 0 1rem; }.upload-actions { align-items: center; display: flex; flex-wrap: wrap; gap: .6rem; }.choose-button { background: #fff; border: 1px solid var(--admin-line); border-radius: 9px; color: #555f63; cursor:pointer; font-size:.82rem; margin:0; padding:.67rem 1rem; }.choose-button i { margin-right: .35rem; }.selected-file { color: var(--admin-muted); display: block; margin-top: .65rem; }.photo-sample { align-items:center; aspect-ratio:1 / 1; background:#f8f4f1; border:1px dashed #e4d8d3; border-radius:12px; display:flex; justify-content:center; overflow:hidden; width:100%; }.photo-sample img { height:100%; object-fit:contain; width:100%; }.photo-sample > div { align-items: center; color: #b8a6a0; display: flex; flex-direction: column; gap: .4rem; }.photo-sample i { font-size: 1.5rem; }.danger-zone { align-items: center; background: #fffafa; border: 1px solid #f3d8d5; border-radius: 11px; display: flex; gap: 1rem; padding: 1.15rem; }.danger-zone p { margin: 0; }.danger-icon { align-items: center; background: #ffeded; border-radius: 10px; color: #c5322b; display: flex; flex: 0 0 40px; height: 40px; justify-content: center; }.delete-button { background: #fff; border: 1px solid #e9b8b4; border-radius: 8px; color: #bd2c27; font-size: .78rem; font-weight: 700; margin-left: auto; padding: .65rem .9rem; white-space: nowrap; }.delete-button:hover { background: #bd2c27; color: #fff; }
@media (max-width:991px) { .editor-layout { grid-template-columns: minmax(0,1fr); }.preview-column { position: static; }.preview-card { max-width: 500px; } }
@media (max-width:575px) { .editor-header h2 { font-size: 1.4rem; }.card-fields,.section-heading,.properties-content { padding: 1rem; }.price-grid,.photo-panel { grid-template-columns: 1fr; }.price-grid .form-group:first-child { margin-bottom: 1rem; }.availability .heading-icon { display: none; }.availability { padding-right: 1rem; }.form-actions { display: grid; grid-template-columns: 1fr 1fr; }.properties-tabs { overflow-x: auto; padding: 0 .5rem; }.properties-tabs button { white-space: nowrap; }.danger-zone { align-items: start; flex-wrap: wrap; }.danger-zone > div { flex: 1; }.delete-button { margin-left: 0; width: 100%; } }
</style>
