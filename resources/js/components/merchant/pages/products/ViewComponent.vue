<template>
  <div class="product-manager">
    <div v-if="actionStatus === 'view'">
      <div class="product-hero">
        <div>
          <span class="eyebrow">Catalog management</span>
          <h2>Manage your products</h2>
          <p>Keep your menu organized, searchable, and ready for customers.</p>
        </div>
        <button type="button" class="btn add-product-button" @click="actionProduct('add')">
          <i class="fas fa-plus" aria-hidden="true"></i>
          Add product
        </button>
      </div>

      <div class="catalog-stats" aria-label="Product summary">
        <div class="stat-card">
          <span class="stat-icon stat-icon-all"><i class="fas fa-box-open"></i></span>
          <div>
            <span class="stat-label">Products on this page</span>
            <strong>{{ productsTemp.length }}</strong>
          </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon stat-icon-active"><i class="fas fa-check"></i></span>
          <div>
            <span class="stat-label">Available</span>
            <strong>{{ activeProductCount }}</strong>
          </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon stat-icon-hidden"><i class="fas fa-eye-slash"></i></span>
          <div>
            <span class="stat-label">Unavailable</span>
            <strong>{{ inactiveProductCount }}</strong>
          </div>
        </div>
      </div>

      <div class="catalog-layout">
        <aside class="category-panel">
          <div class="panel-heading">
            <span>Categories</span>
            <i class="fas fa-layer-group" aria-hidden="true"></i>
          </div>
          <div class="category-list">
            <button
              type="button"
              class="category-button"
              :class="{ active: selectedCategoryId === null }"
              @click="showAllProducts"
            >
              <span class="category-dot"></span>
              <span>All products</span>
              <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
            <button
              v-for="category in categories"
              :key="category.id"
              type="button"
              class="category-button"
              :class="{ active: selectedCategoryId === category.id }"
              @click="filterCategory(category)"
            >
              <span class="category-dot"></span>
              <span>{{ category.name }}</span>
              <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        </aside>

        <section class="product-panel">
          <div class="product-toolbar">
            <div>
              <h3>{{ selectedCategoryName }}</h3>
              <p>{{ resultLabel }}</p>
            </div>
            <div class="product-search">
              <i class="fas fa-search" aria-hidden="true"></i>
              <label class="sr-only" for="product-search">Search products</label>
              <input
                id="product-search"
                v-model.trim="productFilterVal"
                type="search"
                class="form-control"
                placeholder="Search by product name"
              >
              <button
                v-if="productFilterVal"
                type="button"
                class="clear-search"
                aria-label="Clear search"
                @click="productFilterVal = ''"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <div v-if="isLoading" class="catalog-state" aria-live="polite">
            <span class="loading-spinner"></span>
            <h4>Loading products</h4>
            <p>Your catalog will be ready in a moment.</p>
          </div>

          <div v-else-if="!filteredList.length" class="catalog-state">
            <span class="empty-icon"><i class="fas fa-search"></i></span>
            <h4>{{ productFilterVal ? 'No matching products' : 'No products here yet' }}</h4>
            <p>{{ productFilterVal ? 'Try a different product name or clear your search.' : 'Add a product to start building this category.' }}</p>
            <button v-if="!productFilterVal" type="button" class="btn add-product-button" @click="actionProduct('add')">
              <i class="fas fa-plus" aria-hidden="true"></i>
              Add your first product
            </button>
          </div>

          <div v-else class="product-list">
            <article v-for="product in filteredList" :key="product.id" class="product-row">
              <a :href="productEditUrl(product)" class="product-image-link" :aria-label="'Edit ' + product.title">
                <img
                  v-if="product.imgname"
                  :src="product.imgname + '&s=thumb'"
                  :alt="product.title"
                  class="product-image"
                  @error="hideBrokenImage"
                >
                <span class="product-image-placeholder"><i class="fas fa-utensils"></i></span>
              </a>

              <div class="product-copy">
                <div class="product-title-line">
                  <a :href="productEditUrl(product)" class="product-title">{{ product.title }}</a>
                  <span class="status-pill" :class="isActive(product) ? 'is-active' : 'is-inactive'">
                    {{ isActive(product) ? 'Available' : 'Unavailable' }}
                  </span>
                </div>
                <p>{{ product.description || 'No description added yet.' }}</p>
              </div>

              <div class="product-price">
                <span>Price</span>
                <strong>{{ formatPrice(product.price) }}</strong>
              </div>

              <div class="product-actions">
                <label class="availability-switch" :for="'product-status-' + product.id">
                  <input
                    :id="'product-status-' + product.id"
                    v-model="product.active"
                    type="checkbox"
                    :true-value="1"
                    :false-value="0"
                    @change="updateStatus(product)"
                  >
                  <span class="switch-track"><span class="switch-thumb"></span></span>
                  <span class="switch-label">{{ isActive(product) ? 'On' : 'Off' }}</span>
                </label>
                <a :href="productEditUrl(product)" class="edit-button" :aria-label="'Edit ' + product.title">
                  <i class="fas fa-pen"></i>
                </a>
              </div>
            </article>
          </div>

          <div v-if="products.last_page > 1 && !productFilterVal" class="catalog-pagination">
            <pagination-display :data="products" @pagination-change-page="fetchData"></pagination-display>
          </div>
        </section>
      </div>
    </div>

    <div v-if="actionStatus === 'add'">
      <product-add @actionProduct="actionProduct" @fetchData="fetchData"></product-add>
    </div>
  </div>
</template>

<script>
import AddingProducts from '../../../merchant/pages/products/AddComponent.vue';

export default {
  components: {
    'product-add': AddingProducts,
  },
  props: {
    categories: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      products: {},
      productsTemp: [],
      actionStatus: 'view',
      productFilterVal: '',
      selectedCategoryId: null,
      selectedCategoryName: 'All products',
      isLoading: true,
    };
  },
  mounted() {
    this.fetchData(1);
  },
  computed: {
    filteredList() {
      const search = this.productFilterVal.toLowerCase();

      return this.productsTemp.filter((product) => {
        return (product.title || '').toLowerCase().includes(search);
      });
    },
    activeProductCount() {
      return this.productsTemp.filter((product) => this.isActive(product)).length;
    },
    inactiveProductCount() {
      return this.productsTemp.length - this.activeProductCount;
    },
    resultLabel() {
      const count = this.filteredList.length;
      return `${count} ${count === 1 ? 'product' : 'products'} shown`;
    },
  },
  methods: {
    fetchData(page = 1) {
      this.isLoading = true;

      axios.get(`/api/merchant/product/list?api_token=${api_token}&page=${page}`)
        .then((response) => {
          this.products = response.data.products;
          this.productsTemp = this.products.data || [];
        })
        .catch((error) => {
          toastr.error(error.message);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    actionProduct(action) {
      this.actionStatus = action;
    },
    showAllProducts() {
      this.selectedCategoryId = null;
      this.selectedCategoryName = 'All products';
      this.productFilterVal = '';
      this.fetchData(1);
    },
    filterCategory(category) {
      this.selectedCategoryId = category.id;
      this.selectedCategoryName = category.name;
      this.productFilterVal = '';
      this.isLoading = true;

      axios.post(`/api/merchant/product/category/list?api_token=${api_token}`, {
        category_id: category.id,
      })
        .then((response) => {
          this.products = response.data.products;
          this.productsTemp = this.products.data || [];
        })
        .catch((error) => {
          toastr.error(error.message);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    updateStatus(product) {
      axios.put(`/api/merchant/product/${product.id}/status/submit?api_token=${api_token}`, {
        // This legacy endpoint expects the previous value and toggles it server-side.
        active: this.isActive(product) ? 0 : 1,
      })
        .then((response) => {
          if (response.data.status) {
            toastr.success(response.data.message);
          } else {
            toastr.info(response.data.message);
          }
        })
        .catch((error) => {
          product.active = this.isActive(product) ? 0 : 1;
          toastr.error(error.message);
        });
    },
    isActive(product) {
      return product.active === true || Number(product.active) === 1;
    },
    productEditUrl(product) {
      return `/merchant/product/${product.id}/edit`;
    },
    formatPrice(price) {
      const value = Number(price);

      if (Number.isNaN(value)) {
        return `PHP ${price}`;
      }

      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
      }).format(value);
    },
    hideBrokenImage(event) {
      event.target.style.display = 'none';
    },
  },
};
</script>

<style scoped>
.product-manager {
  color: #253041;
  padding-bottom: 2rem;
}

.product-hero {
  align-items: center;
  background: linear-gradient(120deg, #8f1d3f 0%, #c43f62 100%);
  border-radius: 18px;
  box-shadow: 0 12px 30px rgba(143, 29, 63, 0.18);
  color: #fff;
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
  overflow: hidden;
  padding: 1.6rem 1.8rem;
  position: relative;
}

.product-hero::after {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 50%;
  content: '';
  height: 210px;
  position: absolute;
  right: 12%;
  top: -125px;
  width: 210px;
}

.eyebrow {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  margin-bottom: 0.35rem;
  opacity: 0.78;
  text-transform: uppercase;
}

.product-hero h2 {
  font-size: 1.65rem;
  font-weight: 700;
  margin: 0 0 0.3rem;
}

.product-hero p {
  margin: 0;
  opacity: 0.8;
}

.add-product-button {
  align-items: center;
  background: #fff;
  border: 0;
  border-radius: 10px;
  color: #8f1d3f;
  display: inline-flex;
  font-weight: 700;
  gap: 0.55rem;
  padding: 0.7rem 1rem;
  position: relative;
  white-space: nowrap;
  z-index: 1;
}

.add-product-button:hover,
.add-product-button:focus {
  box-shadow: 0 5px 16px rgba(48, 18, 28, 0.18);
  color: #68142d;
  transform: translateY(-1px);
}

.catalog-stats {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-bottom: 1rem;
}

.stat-card {
  align-items: center;
  background: #fff;
  border: 1px solid #e7eaf0;
  border-radius: 14px;
  display: flex;
  gap: 0.8rem;
  padding: 1rem;
}

.stat-icon {
  align-items: center;
  border-radius: 11px;
  display: inline-flex;
  height: 42px;
  justify-content: center;
  width: 42px;
}

.stat-icon-all { background: #f4e9ee; color: #9c2949; }
.stat-icon-active { background: #e7f7ef; color: #218657; }
.stat-icon-hidden { background: #f1f2f5; color: #667085; }

.stat-label {
  color: #7b8493;
  display: block;
  font-size: 0.75rem;
  margin-bottom: 0.05rem;
}

.stat-card strong {
  color: #202a38;
  font-size: 1.25rem;
}

.catalog-layout {
  align-items: start;
  display: grid;
  gap: 1rem;
  grid-template-columns: 225px minmax(0, 1fr);
}

.category-panel,
.product-panel {
  background: #fff;
  border: 1px solid #e7eaf0;
  border-radius: 14px;
  box-shadow: 0 4px 16px rgba(26, 35, 50, 0.04);
  overflow: hidden;
}

.panel-heading {
  align-items: center;
  border-bottom: 1px solid #edf0f4;
  display: flex;
  font-weight: 700;
  justify-content: space-between;
  padding: 1rem;
}

.panel-heading i { color: #a8aeba; }

.category-list { padding: 0.55rem; }

.category-button {
  align-items: center;
  background: transparent;
  border: 0;
  border-radius: 9px;
  color: #5d6674;
  display: grid;
  font-size: 0.86rem;
  gap: 0.6rem;
  grid-template-columns: 8px 1fr auto;
  padding: 0.7rem 0.75rem;
  text-align: left;
  transition: background 0.15s ease, color 0.15s ease;
  width: 100%;
}

.category-button:hover { background: #f8f3f5; color: #8f1d3f; }
.category-button.active { background: #f7eaf0; color: #8f1d3f; font-weight: 700; }
.category-button i { font-size: 0.65rem; opacity: 0; }
.category-button.active i { opacity: 1; }

.category-dot {
  background: #d4d8df;
  border-radius: 50%;
  height: 7px;
  width: 7px;
}

.category-button.active .category-dot { background: #af3153; }

.product-toolbar {
  align-items: center;
  border-bottom: 1px solid #edf0f4;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 1rem 1.15rem;
}

.product-toolbar h3 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 0.15rem;
}

.product-toolbar p {
  color: #89919e;
  font-size: 0.76rem;
  margin: 0;
}

.product-search {
  max-width: 310px;
  position: relative;
  width: 100%;
}

.product-search > i {
  color: #99a1ad;
  left: 0.85rem;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
}

.product-search .form-control {
  background: #f8f9fb;
  border: 1px solid #e2e6eb;
  border-radius: 9px;
  font-size: 0.84rem;
  height: 40px;
  padding-left: 2.35rem;
  padding-right: 2.2rem;
}

.product-search .form-control:focus {
  background: #fff;
  border-color: #bd5673;
  box-shadow: 0 0 0 3px rgba(189, 86, 115, 0.12);
}

.clear-search {
  background: transparent;
  border: 0;
  color: #8c94a0;
  padding: 0.4rem;
  position: absolute;
  right: 0.35rem;
  top: 50%;
  transform: translateY(-50%);
}

.product-list { padding: 0 1.15rem; }

.product-row {
  align-items: center;
  border-bottom: 1px solid #eef0f3;
  display: grid;
  gap: 1rem;
  grid-template-columns: 64px minmax(150px, 1fr) 115px 132px;
  padding: 1rem 0;
}

.product-row:last-child { border-bottom: 0; }
.product-row:hover .product-title { color: #9a2446; }

.product-image-link {
  align-items: center;
  background: #f4f5f7;
  border-radius: 11px;
  display: flex;
  height: 64px;
  justify-content: center;
  overflow: hidden;
  position: relative;
  width: 64px;
}

.product-image {
  height: 100%;
  object-fit: cover;
  position: relative;
  width: 100%;
  z-index: 1;
}

.product-image-placeholder { color: #bdc2ca; position: absolute; }

.product-title-line {
  align-items: center;
  display: flex;
  gap: 0.55rem;
  margin-bottom: 0.3rem;
}

.product-title {
  color: #253041;
  font-size: 0.92rem;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.product-copy { min-width: 0; }

.product-copy p {
  color: #7d8694;
  display: -webkit-box;
  font-size: 0.78rem;
  line-height: 1.45;
  margin: 0;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.status-pill {
  border-radius: 999px;
  flex: 0 0 auto;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.45rem;
}

.status-pill.is-active { background: #e7f7ef; color: #218657; }
.status-pill.is-inactive { background: #f0f1f3; color: #737b87; }

.product-price span {
  color: #9299a4;
  display: block;
  font-size: 0.68rem;
  margin-bottom: 0.15rem;
}

.product-price strong { color: #263142; font-size: 0.87rem; }

.product-actions {
  align-items: center;
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.availability-switch {
  align-items: center;
  cursor: pointer;
  display: inline-flex;
  gap: 0.4rem;
  margin: 0;
}

.availability-switch input {
  height: 1px;
  opacity: 0;
  position: absolute;
  width: 1px;
}

.switch-track {
  background: #c9ced6;
  border-radius: 999px;
  height: 22px;
  padding: 3px;
  transition: background 0.15s ease;
  width: 39px;
}

.switch-thumb {
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(18, 27, 39, 0.28);
  display: block;
  height: 16px;
  transition: transform 0.15s ease;
  width: 16px;
}

.availability-switch input:checked + .switch-track { background: #2fa46b; }
.availability-switch input:checked + .switch-track .switch-thumb { transform: translateX(17px); }
.availability-switch input:focus + .switch-track { box-shadow: 0 0 0 3px rgba(47, 164, 107, 0.18); }

.switch-label { color: #727b88; font-size: 0.7rem; font-weight: 700; width: 19px; }

.edit-button {
  align-items: center;
  background: #f7eef1;
  border-radius: 8px;
  color: #9a2446;
  display: inline-flex;
  height: 34px;
  justify-content: center;
  width: 34px;
}

.edit-button:hover { background: #9a2446; color: #fff; }

.catalog-state {
  align-items: center;
  color: #7e8794;
  display: flex;
  flex-direction: column;
  min-height: 310px;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.catalog-state h4 { color: #2b3544; font-size: 1rem; font-weight: 700; margin: 0.8rem 0 0.25rem; }
.catalog-state p { font-size: 0.82rem; margin: 0 0 1rem; }
.catalog-state .add-product-button { background: #9a2446; color: #fff; }

.empty-icon {
  align-items: center;
  background: #f7eef1;
  border-radius: 50%;
  color: #a33151;
  display: inline-flex;
  height: 50px;
  justify-content: center;
  width: 50px;
}

.loading-spinner {
  animation: spin 0.75s linear infinite;
  border: 3px solid #ead9df;
  border-radius: 50%;
  border-top-color: #a33151;
  height: 34px;
  width: 34px;
}

.catalog-pagination { border-top: 1px solid #edf0f4; padding: 0.85rem 1.15rem 0.2rem; }

@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 991.98px) {
  .catalog-layout { grid-template-columns: 1fr; }
  .category-list { display: flex; gap: 0.4rem; overflow-x: auto; padding: 0.7rem; }
  .category-button { display: inline-flex; flex: 0 0 auto; width: auto; }
  .category-button i { display: none; }
  .product-row { grid-template-columns: 64px minmax(140px, 1fr) 105px; }
  .product-actions { grid-column: 3; grid-row: 1; }
  .product-price { grid-column: 3; grid-row: 2; text-align: right; }
  .product-copy { grid-row: span 2; }
  .product-image-link { grid-row: span 2; }
}

@media (max-width: 767.98px) {
  .product-hero { align-items: flex-start; padding: 1.25rem; }
  .product-hero h2 { font-size: 1.35rem; }
  .product-hero p { font-size: 0.8rem; max-width: 210px; }
  .catalog-stats { gap: 0.55rem; }
  .stat-card { padding: 0.75rem; }
  .stat-icon { display: none; }
  .stat-label { min-height: 30px; }
  .product-toolbar { align-items: stretch; flex-direction: column; }
  .product-search { max-width: none; }
}

@media (max-width: 575.98px) {
  .product-hero { flex-direction: column; gap: 1rem; }
  .product-hero .add-product-button { width: 100%; justify-content: center; }
  .catalog-stats { grid-template-columns: repeat(3, 1fr); }
  .stat-card strong { font-size: 1.05rem; }
  .product-row { align-items: start; grid-template-columns: 58px minmax(0, 1fr) auto; gap: 0.75rem; }
  .product-image-link { height: 58px; width: 58px; }
  .product-copy { grid-column: 2 / 4; grid-row: 1; padding-right: 0.25rem; }
  .product-image-link { grid-column: 1; grid-row: 1 / 3; }
  .product-price { grid-column: 2; grid-row: 2; text-align: left; }
  .product-actions { grid-column: 3; grid-row: 2; }
  .status-pill { display: none; }
}
</style>
