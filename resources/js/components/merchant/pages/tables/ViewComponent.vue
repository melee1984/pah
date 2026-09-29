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
            <thead><tr><th>Table</th><th>Capacity</th><th>Checkout visibility</th><th>Current availability</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
              <tr v-for="table in tables" :key="table.id">
                <td><input v-model.trim="table.name" type="text" maxlength="100" class="form-control" aria-label="Table name"></td>
                <td><input v-model.number="table.capacity" type="number" min="1" max="100" class="form-control table-capacity" aria-label="Guest capacity"></td>
                <td><label class="merchant-table-checkbox"><input v-model="table.active" type="checkbox"><span>{{ table.active ? 'Enabled' : 'Hidden' }}</span></label></td>
                <td><label class="merchant-table-checkbox"><input v-model="table.is_available" type="checkbox"><span>{{ table.is_available ? 'Available' : 'Occupied' }}</span></label></td>
                <td class="text-right"><button type="button" class="btn btn-sm admin-btn-secondary mr-2" :disabled="requestPending" @click="saveTable(table)">Save</button><button type="button" class="btn btn-sm merchant-danger-button" :disabled="requestPending" @click="deleteTable(table)">Delete</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      locations: [],
      selectedLocationId: '',
      tables: [],
      newTable: { name: '', capacity: 2 },
      loading: true,
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
      return [location.address_1, location.city].filter(Boolean).join(', ');
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
}
</style>
