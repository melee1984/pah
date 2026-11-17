<template>
  <div class="merchant-tables-page">
    <div class="card admin-card dashboard-data-card">
      <div class="admin-card-header merchant-tables-header">
        <div><h2>Dining tables</h2><p>Select a branch location, then manage only the tables assigned to that branch.</p></div>
        <label v-if="locations.length" class="merchant-branch-select"><span>Select branch location</span><select v-model="selectedLocationId" class="form-control" @change="selectLocation"><option disabled value="">Choose a branch location</option><option v-for="location in locations" :key="location.id" :value="location.id">{{ branchName(location) }}</option></select></label>
      </div>

      <div v-if="loading" class="merchant-tables-empty"><i class="fas fa-spinner fa-spin"></i> Loading dining tables…</div>
      <div v-else-if="locations.length === 0" class="merchant-tables-empty">
        <i class="fas fa-chair"></i>
        <div><strong>No branches available</strong><p>Add a merchant branch before creating dining tables.</p></div>
      </div>
      <div v-else-if="!selectedLocationId" class="merchant-tables-empty"><i class="fas fa-map-marker-alt"></i><div><strong>Select a branch location</strong><p>Tables are stored separately per branch. Choose a location above to continue.</p></div></div>
      <template v-else>
        <div v-if="selectedLocation && !selectedLocationHasDineIn" class="merchant-dine-in-notice"><i class="fas fa-info-circle" aria-hidden="true"></i><span>You can add tables to this location now. They will appear in customer checkout after Dine-in is enabled for this branch.</span></div>
        <div class="merchant-table-add">
          <div class="form-group"><label for="table_name">Table name or number</label><input id="table_name" v-model.trim="newTable.name" type="text" maxlength="100" class="form-control" placeholder="e.g. Table 1"></div>
          <div class="form-group"><label for="table_capacity">Guest capacity</label><input id="table_capacity" v-model.number="newTable.capacity" type="number" min="1" max="100" class="form-control"></div>
          <button type="button" class="btn admin-btn-primary" :disabled="requestPending" @click="addTable"><i class="fas fa-plus mr-2"></i>Add table</button>
        </div>

        <div v-if="tables.length === 0" class="merchant-tables-empty"><i class="fas fa-chair"></i><div><strong>No tables yet</strong><p>Add the first dining table for this branch above.</p></div></div>
        <div v-else class="table-responsive">
          <table class="table dashboard-data-table merchant-table-list">
            <thead><tr><th>Table</th><th>Capacity</th><th>QR ordering</th><th>Checkout visibility</th><th>Current availability</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
              <tr v-for="table in tables" :key="table.id">
                <td><input v-model.trim="table.name" type="text" maxlength="100" class="form-control" aria-label="Table name"></td>
                <td><input v-model.number="table.capacity" type="number" min="1" max="100" class="form-control table-capacity" aria-label="Guest capacity"></td>
                <td>
                  <div v-if="table.qr_code" class="table-qr">
                    <qrcode-vue :value="table.qr_code.ordering_url" :size="72" level="M" render-as="svg" class-name="table-qr-code" />
                    <div><button type="button" class="table-qr-action" @click="openQrPreview(table)">Preview &amp; print</button><a :href="table.qr_code.ordering_url" target="_blank" rel="noopener">Open ordering link</a><button type="button" class="table-qr-action" @click="downloadQr(table, $event)">Download QR</button></div>
                  </div>
                  <small v-else class="text-muted">QR unavailable</small>
                </td>
                <td><label class="merchant-table-checkbox"><input v-model="table.active" type="checkbox"><span>{{ table.active ? 'Enabled' : 'Hidden' }}</span></label></td>
                <td><label class="merchant-table-checkbox"><input v-model="table.is_available" type="checkbox"><span>{{ table.is_available ? 'Available' : 'Occupied' }}</span></label></td>
                <td class="text-right"><button type="button" class="btn btn-sm admin-btn-secondary mr-2" :disabled="requestPending" @click="saveTable(table)">Save</button><button type="button" class="btn btn-sm merchant-danger-button" :disabled="requestPending" @click="deleteTable(table)">Delete</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <div v-if="previewTable" class="qr-preview-overlay" role="presentation" @click.self="closeQrPreview">
      <section class="qr-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="qr-preview-title">
        <header class="qr-preview-header">
          <div><span>Print preview</span><h3 id="qr-preview-title">{{ previewTable.name }} ordering QR</h3></div>
          <button type="button" class="qr-preview-close" aria-label="Close QR print preview" @click="closeQrPreview">&times;</button>
        </header>

        <div class="qr-preview-stage">
          <article class="qr-print-sheet">
            <img src="/images/logo-medium.png" alt="Pahatud" class="qr-print-logo">
            <p class="qr-print-kicker">Pahatud at your table</p>
            <h2>{{ previewTable.name }}</h2>
            <p class="qr-print-branch">{{ branchName(selectedLocation) }}</p>
            <div class="qr-print-code">
              <qrcode-vue :value="previewTable.qr_code.ordering_url" :size="300" level="M" render-as="svg" />
            </div>
            <h3>Scan to view the menu and order</h3>
            <p class="qr-print-instructions">Open your phone camera, point it at the QR code, and follow the link. Your order will be assigned to this table.</p>
            <p class="qr-print-url">{{ previewTable.qr_code.ordering_url }}</p>
            <footer>Order with Pahatud</footer>
          </article>
        </div>

        <footer class="qr-preview-actions">
          <button type="button" class="btn admin-btn-secondary" @click="closeQrPreview">Close</button>
          <button type="button" class="btn admin-btn-primary" @click="printQrPreview"><i class="fas fa-print mr-2"></i>Print / Save as PDF</button>
        </footer>
      </section>
    </div>
  </div>
</template>

<script>
import QrcodeVue from 'qrcode.vue';

export default {
  components: { QrcodeVue },
  data() {
    return {
      locations: [],
      selectedLocationId: '',
      tables: [],
      newTable: { name: '', capacity: 2 },
      loading: true,
      previewTable: null,
      requestPending: false,
    };
  },
  computed: {
    selectedLocation() {
      return this.locations.find(location => Number(location.id) === Number(this.selectedLocationId));
    },
    selectedLocationHasDineIn() {
      return Boolean(this.selectedLocation && (this.selectedLocation.checkout_options || [])
        .some(option => option.type === 'dine_in' && option.active));
    },
  },
  mounted() {
    this.fetchLocations();
    document.addEventListener('keydown', this.handleKeydown);
  },
  beforeDestroy() {
    document.removeEventListener('keydown', this.handleKeydown);
  },
  methods: {
    fetchLocations() {
      this.loading = true;
      axios.get('/api/merchant/location/list').then((response) => {
        this.locations = response.data.location.data || [];
        this.selectedLocationId = '';
        this.tables = [];
      }).catch((error) => this.showError(error, 'Unable to load merchant branches.'))
        .finally(() => { this.loading = false; });
    },
    selectLocation() {
      this.newTable = { name: '', capacity: 2 };
      this.fetchTables();
    },
    fetchTables() {
      if (!this.selectedLocationId) {
        this.tables = [];
        return Promise.resolve();
      }

      return axios.get('/api/merchant/location/' + this.selectedLocationId + '/tables')
        .then((response) => { this.tables = response.data.tables || []; })
        .catch((error) => this.showError(error, 'Unable to load dining tables.'));
    },
    addTable() {
      if (!this.validTable(this.newTable)) {
        toastr.error('Enter a table name and a capacity from 1 to 100.');
        return;
      }

      this.requestPending = true;
      axios.post('/api/merchant/location/' + this.selectedLocationId + '/tables', {
        name: this.newTable.name,
        capacity: this.newTable.capacity,
        active: true,
        is_available: true,
      }).then((response) => {
        this.tables = response.data.tables;
        this.newTable = { name: '', capacity: 2 };
        toastr.success(response.data.message);
      }).catch((error) => this.showError(error, 'Unable to add the dining table.'))
        .finally(() => { this.requestPending = false; });
    },
    saveTable(table) {
      if (!this.validTable(table)) {
        toastr.error('Enter a table name and a capacity from 1 to 100.');
        return;
      }

      this.requestPending = true;
      axios.put('/api/merchant/location/' + this.selectedLocationId + '/tables/' + table.id, {
        name: table.name,
        capacity: table.capacity,
        active: Boolean(table.active),
        is_available: Boolean(table.is_available),
      }).then((response) => {
        this.tables = response.data.tables;
        toastr.success(response.data.message);
      }).catch((error) => this.showError(error, 'Unable to update the dining table.'))
        .finally(() => { this.requestPending = false; });
    },
    deleteTable(table) {
      if (!confirm('Delete ' + table.name + '?')) {
        return;
      }

      this.requestPending = true;
      axios.delete('/api/merchant/location/' + this.selectedLocationId + '/tables/' + table.id)
        .then((response) => {
          this.tables = response.data.tables;
          toastr.success(response.data.message);
        }).catch((error) => this.showError(error, 'Unable to delete the dining table.'))
        .finally(() => { this.requestPending = false; });
    },
    validTable(table) {
      const capacity = Number(table.capacity);
      return Boolean(table.name) && Number.isInteger(capacity) && capacity >= 1 && capacity <= 100;
    },
    branchName(location) {
      if (!location) {
        return '';
      }

      return [location.address_1, location.city].filter(Boolean).join(', ');
    },
    openQrPreview(table) {
      this.previewTable = table;
    },
    closeQrPreview() {
      this.previewTable = null;
    },
    handleKeydown(event) {
      if (event.key === 'Escape' && this.previewTable) {
        this.closeQrPreview();
      }
    },
    printQrPreview() {
      this.$nextTick(() => window.print());
    },
    qrFilename(table) {
      const safeName = table.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      return 'pahatud-' + (safeName || 'table') + '-qr.svg';
    },
    downloadQr(table, event) {
      const svg = event.currentTarget.closest('.table-qr').querySelector('svg').cloneNode(true);
      svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
      const objectUrl = URL.createObjectURL(new Blob([svg.outerHTML], { type: 'image/svg+xml' }));
      const link = document.createElement('a');
      link.href = objectUrl;
      link.download = this.qrFilename(table);
      link.click();
      URL.revokeObjectURL(objectUrl);
    },
    showError(error, fallback) {
      toastr.error(error.response?.data?.message || fallback);
    },
  },
};
</script>

<style scoped>
.merchant-tables-header,
.merchant-table-add,
.merchant-table-checkbox,
.merchant-tables-empty {
  align-items: center;
  display: flex;
}

.merchant-tables-header {
  gap: 20px;
  justify-content: space-between;
}

.merchant-branch-select {
  margin: 0;
  min-width: 280px;
}

.merchant-branch-select span {
  display: block;
  font-size: 11px;
  font-weight: 700;
  margin-bottom: 4px;
}

.merchant-table-add {
  align-items: end;
  gap: 12px;
  padding: 18px;
}

.merchant-table-add .form-group:first-child {
  flex: 1;
}

.merchant-table-add .form-group {
  margin: 0;
}

.merchant-table-list {
  margin: 0;
}

.merchant-table-list td {
  vertical-align: middle;
}

.table-qr {
  align-items: center;
  display: flex;
  gap: 10px;
  min-width: 220px;
}

.table-qr-code {
  border: 1px solid #e2e5e7;
  border-radius: 4px;
  line-height: 0;
  padding: 3px;
}

.table-qr a,
.table-qr-action {
  display: block;
  font-size: 12px;
  line-height: 1.7;
}

.table-qr-action {
  background: transparent;
  border: 0;
  color: #d5322f;
  padding: 0;
}

.qr-preview-overlay {
  align-items: center;
  background: rgba(19, 25, 29, 0.72);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 24px;
  position: fixed;
  z-index: 2050;
}

.qr-preview-dialog {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
  max-height: calc(100vh - 48px);
  max-width: 720px;
  overflow: auto;
  width: 100%;
}

.qr-preview-header,
.qr-preview-actions {
  align-items: center;
  display: flex;
  justify-content: space-between;
  padding: 16px 20px;
}

.qr-preview-header {
  border-bottom: 1px solid #e7e9ea;
}

.qr-preview-header span {
  color: #d5322f;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.qr-preview-header h3 {
  font-size: 18px;
  margin: 2px 0 0;
}

.qr-preview-close {
  background: transparent;
  border: 0;
  color: #596168;
  font-size: 30px;
  line-height: 1;
}

.qr-preview-stage {
  background: #eef1f2;
  padding: 28px;
}

.qr-print-sheet {
  background: #fff;
  border: 1px solid #dfe3e5;
  border-radius: 8px;
  box-shadow: 0 5px 18px rgba(29, 37, 41, 0.08);
  margin: 0 auto;
  max-width: 480px;
  padding: 34px 42px 28px;
  text-align: center;
}

.qr-print-logo {
  height: auto;
  max-width: 150px;
}

.qr-print-kicker {
  color: #d5322f;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.12em;
  margin: 18px 0 6px;
  text-transform: uppercase;
}

.qr-print-sheet h2 {
  color: #20272b;
  font-size: 36px;
  font-weight: 800;
  margin: 0;
}

.qr-print-branch {
  color: #697277;
  margin: 4px 0 20px;
}

.qr-print-code {
  display: inline-block;
  line-height: 0;
  padding: 12px;
}

.qr-print-sheet h3 {
  color: #20272b;
  font-size: 19px;
  margin: 14px 0 8px;
}

.qr-print-instructions {
  color: #596168;
  font-size: 13px;
  line-height: 1.55;
  margin: 0 auto;
  max-width: 350px;
}

.qr-print-url {
  color: #8a9195;
  font-size: 9px;
  margin: 16px 0;
  overflow-wrap: anywhere;
}

.qr-print-sheet > footer {
  border-top: 1px solid #e8eaeb;
  color: #d5322f;
  font-size: 12px;
  font-weight: 700;
  padding-top: 16px;
}

.qr-preview-actions {
  border-top: 1px solid #e7e9ea;
  gap: 12px;
  justify-content: flex-end;
}

.table-capacity {
  max-width: 110px;
}

.merchant-table-checkbox {
  gap: 7px;
  margin: 0;
  white-space: nowrap;
}

.merchant-tables-empty {
  color: #697277;
  gap: 14px;
  justify-content: center;
  min-height: 190px;
  padding: 28px;
  text-align: center;
}

.merchant-tables-empty > i {
  color: #d5322f;
  font-size: 28px;
}

.merchant-tables-empty p {
  margin: 4px 0 14px;
}

.merchant-dine-in-notice {
  align-items: center;
  background: #fff8e8;
  border-bottom: 1px solid #f0d99d;
  color: #6d5521;
  display: flex;
  gap: 9px;
  padding: 12px 18px;
}

@media (max-width: 767px) {
  .merchant-tables-header,
  .merchant-table-add {
    align-items: stretch;
    flex-direction: column;
  }

  .merchant-branch-select {
    min-width: 0;
    width: 100%;
  }

  .qr-preview-overlay {
    padding: 10px;
  }

  .qr-preview-stage {
    padding: 14px;
  }

  .qr-print-sheet {
    padding: 26px 16px 22px;
  }

  .qr-print-code svg {
    height: auto;
    max-width: 100%;
  }
}
</style>

<style>
@media print {
  body * {
    visibility: hidden !important;
  }

  .qr-print-sheet,
  .qr-print-sheet * {
    visibility: visible !important;
  }

  .qr-print-sheet {
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    left: 50% !important;
    margin: 0 !important;
    max-width: 480px !important;
    position: fixed !important;
    top: 20px !important;
    transform: translateX(-50%) !important;
    width: 100% !important;
  }

  @page {
    margin: 12mm;
    size: auto;
  }
}
</style>
