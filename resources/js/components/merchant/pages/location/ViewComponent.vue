<template>
  <div class="merchant-settings-page">
    <div v-if="actionStatus === 'view'" class="card admin-card dashboard-data-card">
      <div class="admin-card-header merchant-settings-card-header">
        <div><h2>Store branches</h2><p>{{ searchFilter.length }} branches shown. Select a row to edit its details.</p></div>
        <div class="merchant-settings-toolbar">
          <label class="admin-search" for="location-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input id="location-search" v-model.trim="search" type="search" placeholder="Search addresses">
          </label>
          <button type="button" class="btn admin-btn-primary" @click="action('add')"><i class="fas fa-plus mr-2"></i>Add branch</button>
        </div>
      </div>
      <div class="card-body table-responsive p-0">
        <table class="table dashboard-data-table merchant-settings-table merchant-location-table">
          <thead><tr><th>Branch</th><th>Checkout options</th><th>Telephone</th><th>Mobile</th><th>Coordinates</th><th class="text-right">Availability</th></tr></thead>
          <tbody>
            <tr v-if="searchFilter.length === 0"><td colspan="6" class="dashboard-table-empty">{{ search ? 'No branches match your search.' : 'No store branches have been added yet.' }}</td></tr>
            <tr v-for="location in searchFilter" :key="location.id" class="merchant-settings-row" @click="editAction(location)">
              <td><strong>{{ location.address_1 }}</strong><small>{{ [location.address_2, location.city, location.zip_code].filter(Boolean).join(', ') || 'No additional address details' }}</small></td>
              <td><span v-for="option in enabledCheckoutOptions(location)" :key="option.type" class="merchant-option-badge">{{ option.label }}</span><small v-if="enabledCheckoutOptions(location).length === 0">None enabled</small><small v-else-if="enabledCheckoutOptions(location).some(option => option.type === 'dine_in')">{{ (location.dining_tables || []).length }} dining {{ (location.dining_tables || []).length === 1 ? 'table' : 'tables' }}</small></td>
              <td>{{ location.telephone || '—' }}</td>
              <td>{{ location.mobile || '—' }}</td>
              <td><strong>{{ location.latitude || '—' }}</strong><small>Lat · {{ location.longtitude || '—' }} Long</small></td>
              <td class="text-right" @click.stop>
                <label class="merchant-toggle" :for="'is_active' + location.id">
                  <input :id="'is_active' + location.id" v-model="location.active" type="checkbox" @change="updateStatus(location.id, location.active)">
                  <span><i></i></span><strong>{{ location.active ? 'Active' : 'Hidden' }}</strong>
                </label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="locations.last_page > 1" class="merchant-settings-pagination"><pagination-display :data="locations" @pagination-change-page="fetchData"></pagination-display></div>
    </div>

    <div v-else class="merchant-form-shell merchant-form-shell-wide">
      <div class="card admin-card merchant-form-card">
        <div class="admin-card-header">
          <div><span class="admin-eyebrow">Branch details</span><h2>{{ actionStatus === 'add' ? 'Add a branch' : 'Edit branch' }}</h2><p>Provide an address and reliable contact details for this branch.</p></div>
          <button v-if="actionStatus === 'edit'" type="button" class="btn merchant-danger-button" @click="onDelete"><i class="fas fa-trash-alt mr-2"></i>Delete</button>
        </div>
        <form class="merchant-settings-form" @submit.prevent="onSubmit">
          <div v-if="errors.length" class="alert alert-danger" role="alert"><div v-for="error in errors" :key="error">{{ error }}</div></div>
          <div class="merchant-toggle-panel">
            <div><strong>Branch availability</strong><small>Active branches can receive customer orders.</small></div>
            <label class="merchant-toggle" for="active"><input id="active" v-model="field.active" type="checkbox"><span><i></i></span><strong>{{ field.active ? 'Active' : 'Hidden' }}</strong></label>
          </div>
          <div class="merchant-checkout-options">
            <div><strong>Checkout options</strong><small>Select at least one option customers can use at this branch.</small></div>
            <div class="merchant-checkout-option-grid">
              <label v-for="option in checkoutOptionChoices" :key="option.type" class="merchant-checkout-option" :class="{ selected: field.checkout_options.includes(option.type) }">
                <input v-model="field.checkout_options" type="checkbox" :value="option.type">
                <span><strong>{{ option.label }}</strong><small>{{ option.description }}</small></span>
              </label>
            </div>
          </div>
          <div class="merchant-form-grid">
            <div class="form-group merchant-form-span"><label for="address_1">Address line 1</label><input id="address_1" v-model.trim="field.address_1" type="text" class="form-control" placeholder="Street, building, or unit"></div>
            <div class="form-group merchant-form-span"><label for="address_2">Address line 2 <small>(optional)</small></label><input id="address_2" v-model.trim="field.address_2" type="text" class="form-control" placeholder="Barangay or landmark"></div>
            <div class="form-group"><label for="city">City</label><input id="city" v-model.trim="field.city" type="text" class="form-control" placeholder="City"></div>
            <div class="form-group"><label for="zip">ZIP code</label><input id="zip" v-model.trim="field.zip_code" type="text" class="form-control" placeholder="ZIP code"></div>
            <div class="form-group"><label for="mobile">Mobile</label><input id="mobile" v-model.trim="field.mobile" type="text" class="form-control" placeholder="Mobile number"></div>
            <div class="form-group"><label for="telephone">Telephone</label><input id="telephone" v-model.trim="field.telephone" type="text" class="form-control" placeholder="Telephone number"></div>
            <div class="form-group merchant-form-span merchant-location-picker">
              <div class="merchant-location-picker-header">
                <div><label>Pin branch location</label><small>Click the map or drag the pin to set the exact coordinates.</small></div>
                <button type="button" class="btn admin-btn-secondary merchant-location-button" :disabled="isLocating" @click="useCurrentLocation">
                  <i :class="isLocating ? 'fas fa-spinner fa-spin' : 'fas fa-crosshairs'" aria-hidden="true"></i>
                  {{ isLocating ? 'Getting location…' : 'Use my location' }}
                </button>
              </div>
              <div ref="locationMap" class="merchant-location-map" aria-label="Interactive branch location map"></div>
              <small class="merchant-location-message" role="status" aria-live="polite">{{ locationMessage }}</small>
            </div>
            <div class="form-group"><label for="latitude">Latitude</label><input id="latitude" v-model.trim="field.latitude" type="number" step="any" min="-90" max="90" class="form-control" placeholder="e.g. 10.3157"><small class="merchant-field-help">A value from -90 to 90.</small></div>
            <div class="form-group"><label for="longtitude">Longitude</label><input id="longtitude" v-model.trim="field.longtitude" type="number" step="any" min="-180" max="180" class="form-control" placeholder="e.g. 123.8854"><small class="merchant-field-help">A value from -180 to 180.</small></div>
          </div>
          <div v-if="field.checkout_options.includes('dine_in') && actionStatus === 'add'" class="merchant-table-notice"><i class="fas fa-info-circle" aria-hidden="true"></i><span>Save this branch first, then edit it to add and manage its dining tables.</span></div>
          <section v-if="field.checkout_options.includes('dine_in') && actionStatus === 'edit'" class="merchant-table-manager">
            <div class="merchant-table-manager-header"><div><strong>Dining tables</strong><small>Add the tables customers can select for dine-in orders at this branch.</small></div><span>{{ (field.dining_tables || []).length }} total</span></div>
            <div class="merchant-table-add">
              <div class="form-group"><label for="new_table_name">Table name or number</label><input id="new_table_name" v-model.trim="newDiningTable.name" type="text" maxlength="100" class="form-control" placeholder="e.g. Table 1"></div>
              <div class="form-group"><label for="new_table_capacity">Guest capacity</label><input id="new_table_capacity" v-model.number="newDiningTable.capacity" type="number" min="1" max="100" class="form-control"></div>
              <button type="button" class="btn admin-btn-primary" :disabled="tableRequestPending" @click="addDiningTable"><i class="fas fa-plus mr-2"></i>Add table</button>
            </div>
            <div v-if="!field.dining_tables || field.dining_tables.length === 0" class="merchant-table-empty">No dining tables have been added to this branch.</div>
            <div v-else class="table-responsive">
              <table class="table merchant-dining-table-list">
                <thead><tr><th>Table</th><th>Capacity</th><th>Enabled</th><th>Available now</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                  <tr v-for="table in field.dining_tables" :key="table.id">
                    <td><input v-model.trim="table.name" type="text" maxlength="100" class="form-control" aria-label="Table name"></td>
                    <td><input v-model.number="table.capacity" type="number" min="1" max="100" class="form-control merchant-table-capacity" aria-label="Table capacity"></td>
                    <td><label class="merchant-table-checkbox"><input v-model="table.active" type="checkbox"><span>{{ table.active ? 'Enabled' : 'Hidden' }}</span></label></td>
                    <td><label class="merchant-table-checkbox"><input v-model="table.is_available" type="checkbox"><span>{{ table.is_available ? 'Available' : 'Occupied' }}</span></label></td>
                    <td class="text-right"><button type="button" class="btn btn-sm admin-btn-secondary mr-2" :disabled="tableRequestPending" @click="saveDiningTable(table)">Save</button><button type="button" class="btn btn-sm merchant-danger-button" :disabled="tableRequestPending" @click="deleteDiningTable(table)">Delete</button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
          <div class="merchant-form-actions"><button type="button" class="btn admin-btn-secondary" @click="cancel">Cancel</button><button type="submit" class="btn admin-btn-primary">{{ actionStatus === 'add' ? 'Add branch' : 'Save changes' }}</button></div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
let leafletPromise = null;

const loadLeaflet = () => {
  if (!leafletPromise) {
    leafletPromise = Promise.all([
      import('leaflet/dist/leaflet-src.esm.js'),
      import('leaflet/dist/leaflet.css'),
    ]).then(([leaflet]) => leaflet);
  }

  return leafletPromise;
};

const DEFAULT_MAP_CENTER = [10.3157, 123.8854];
const MAP_TILE_URL = import.meta.env.VITE_MAP_TILE_URL || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const MAP_TILE_ATTRIBUTION = import.meta.env.VITE_MAP_TILE_ATTRIBUTION
  || '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>';

const emptyLocation = () => ({
  active: false,
  address_1: '',
  address_2: '',
  city: '',
  zip_code: '',
  mobile: '',
  telephone: '',
  latitude: '',
  longtitude: '',
  checkout_options: ['delivery'],
});

     export default {
       data() {
            return {
                field: emptyLocation(),
                Temp: [],
                errors: {},
                isSubmit: false,
                display: {},
                actionStatus: 'view',
                search: '',
                locations: {},
                leaflet: null,
                locationMap: null,
                locationMarker: null,
                isLocating: false,
                locationMessage: 'Choose a point on the map or use your device location.',
                checkoutOptionChoices: [
                  { type: 'delivery', label: 'Delivery', description: 'Deliver orders to customers' },
                  { type: 'pickup', label: 'Pickup', description: 'Customers collect their order' },
                  { type: 'dine_in', label: 'Dine in', description: 'Customers select an available table' },
                ],
                newDiningTable: { name: '', capacity: 2 },
                tableRequestPending: false,
            }
        },
        mounted() {
            console.log('Mounted Merchant View Component')
            this.fetchData(1);
        },
        computed: {
          searchFilter() {
            return this.Temp.filter(location => {
              return (location.address_1 || '').toLowerCase().includes(this.search.toLowerCase())
            })
          }
        },
        watch: {
          'field.latitude': function() {
            this.syncMarkerFromFields(false);
          },
          'field.longtitude': function() {
            this.syncMarkerFromFields(false);
          },
        },
        beforeDestroy() {
          this.destroyMap();
        },
        methods: {
          updateStatus: function(location, val) {
              axios.put('/api/merchant/location/'+location+'/update/status', {
                active: val
              }).then((response) => {
                  if (response.data.status) {
                    toastr.success(response.data.message);
                  }
                  else {
                    toastr.info(response.data.message);
                  }
              }).catch((errors) => {
                    toastr.error(errors);
              }); 
          },
          action: function(action) {
            if (action === 'add') {
              this.field = emptyLocation();
              this.newDiningTable = { name: '', capacity: 2 };
            }

            if (action === 'view') {
              this.destroyMap();
            }

            this.actionStatus = action;

            if (action !== 'view') {
              this.$nextTick(() => this.initializeMap());
            }
          },
          editAction: function(location) {
            this.field = {
              ...location,
              checkout_options: (location.checkout_options || [])
                .filter(option => option.active)
                .map(option => option.type),
              dining_tables: (location.dining_tables || []).map(table => ({ ...table })),
            };
            this.newDiningTable = { name: '', capacity: 2 };
            this.action('edit');
          },
          enabledCheckoutOptions: function(location) {
            const enabledTypes = (location.checkout_options || [])
              .filter(option => option.active)
              .map(option => option.type);

            return this.checkoutOptionChoices.filter(option => enabledTypes.includes(option.type));
          },
          addDiningTable: function() {
            if (!this.newDiningTable.name || !this.validTableCapacity(this.newDiningTable.capacity)) {
              toastr.error('Enter a table name and a capacity from 1 to 100.');
              return;
            }

            this.tableRequestPending = true;
            axios.post('/api/merchant/location/' + this.field.id + '/tables', {
              name: this.newDiningTable.name,
              capacity: this.newDiningTable.capacity,
              active: true,
              is_available: true,
            }).then((response) => {
              this.field.dining_tables = response.data.tables;
              this.newDiningTable = { name: '', capacity: 2 };
              toastr.success(response.data.message);
            }).catch((error) => this.showTableError(error))
              .finally(() => { this.tableRequestPending = false; });
          },
          saveDiningTable: function(table) {
            if (!table.name || !this.validTableCapacity(table.capacity)) {
              toastr.error('Enter a table name and a capacity from 1 to 100.');
              return;
            }

            this.tableRequestPending = true;
            axios.put('/api/merchant/location/' + this.field.id + '/tables/' + table.id, {
              name: table.name,
              capacity: table.capacity,
              active: Boolean(table.active),
              is_available: Boolean(table.is_available),
            }).then((response) => {
              this.field.dining_tables = response.data.tables;
              toastr.success(response.data.message);
            }).catch((error) => this.showTableError(error))
              .finally(() => { this.tableRequestPending = false; });
          },
          deleteDiningTable: function(table) {
            if (!confirm('Delete ' + table.name + '?')) {
              return;
            }

            this.tableRequestPending = true;
            axios.delete('/api/merchant/location/' + this.field.id + '/tables/' + table.id)
              .then((response) => {
                this.field.dining_tables = response.data.tables;
                toastr.success(response.data.message);
              }).catch((error) => this.showTableError(error))
              .finally(() => { this.tableRequestPending = false; });
          },
          validTableCapacity: function(capacity) {
            const value = Number(capacity);
            return Number.isInteger(value) && value >= 1 && value <= 100;
          },
          showTableError: function(error) {
            toastr.error(error.response?.data?.message || 'Unable to update the dining table.');
          },
          cancel: function() {
            this.action('view');
            this.field = emptyLocation();
          },
          filterCategory: function() {
            console.log('filter-category');
          },
          fetchData: function(page = 1) {
              var self = this;
              axios.get('/api/merchant/location/list?page='+page).then(function (response) {
                  self.locations = response.data.location;
                  self.Temp = self.locations.data;
                  
              })
              .catch(function (error) {
                  console.log(error);
              });
          },
          initializeMap: async function() {
            if (!this.$refs.locationMap) {
              return;
            }

            this.destroyMap();

            try {
              this.leaflet = await loadLeaflet();
            } catch (error) {
              this.locationMessage = 'The map could not be loaded. You can still enter the coordinates manually.';
              console.error('Unable to load the merchant location map.', error);
              return;
            }

            if (!this.$refs.locationMap || this.actionStatus === 'view') {
              return;
            }

            const coordinates = this.validCoordinates();
            const center = coordinates || DEFAULT_MAP_CENTER;
            const zoom = coordinates ? 17 : 13;

            this.locationMap = this.leaflet.map(this.$refs.locationMap, {
              scrollWheelZoom: false,
            }).setView(center, zoom);

            this.leaflet.tileLayer(MAP_TILE_URL, {
              attribution: MAP_TILE_ATTRIBUTION,
              maxZoom: 19,
            }).addTo(this.locationMap);

            this.locationMap.on('click', (event) => {
              this.setCoordinates(event.latlng.lat, event.latlng.lng, true);
            });

            if (coordinates) {
              this.placeMarker(coordinates);
              this.locationMessage = 'Saved coordinates loaded. Drag the pin to adjust them.';
            } else {
              this.locationMessage = 'Choose a point on the map or use your device location.';
            }

            window.setTimeout(() => {
              if (this.locationMap) {
                this.locationMap.invalidateSize();
              }
            }, 0);
          },
          destroyMap: function() {
            if (this.locationMap) {
              this.locationMap.remove();
            }

            this.locationMap = null;
            this.locationMarker = null;
          },
          validCoordinates: function() {
            if (!this.field || this.field.latitude === '' || this.field.latitude === null
              || this.field.longtitude === '' || this.field.longtitude === null) {
              return null;
            }

            const latitude = Number(this.field.latitude);
            const longitude = Number(this.field.longtitude);

            if (!Number.isFinite(latitude) || latitude < -90 || latitude > 90
              || !Number.isFinite(longitude) || longitude < -180 || longitude > 180) {
              return null;
            }

            return [latitude, longitude];
          },
          placeMarker: function(coordinates) {
            if (!this.locationMap) {
              return;
            }

            if (this.locationMarker) {
              this.locationMarker.setLatLng(coordinates);
              return;
            }

            const pinIcon = this.leaflet.divIcon({
              className: 'merchant-location-pin-wrapper',
              html: '<span class="merchant-location-pin"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>',
              iconSize: [38, 46],
              iconAnchor: [19, 43],
            });

            this.locationMarker = this.leaflet.marker(coordinates, {
              autoPan: true,
              draggable: true,
              icon: pinIcon,
            }).addTo(this.locationMap);

            this.locationMarker.on('dragend', (event) => {
              const position = event.target.getLatLng();
              this.setCoordinates(position.lat, position.lng, false);
            });
          },
          syncMarkerFromFields: function(recenter) {
            const coordinates = this.validCoordinates();

            if (!this.locationMap) {
              return;
            }

            if (!coordinates) {
              if (this.locationMarker) {
                this.locationMap.removeLayer(this.locationMarker);
                this.locationMarker = null;
              }

              return;
            }

            this.placeMarker(coordinates);

            if (recenter) {
              this.locationMap.setView(coordinates, Math.max(this.locationMap.getZoom(), 17));
            }
          },
          setCoordinates: function(latitude, longitude, recenter) {
            this.field.latitude = Number(latitude).toFixed(7);
            this.field.longtitude = Number(longitude).toFixed(7);
            this.syncMarkerFromFields(recenter);
            this.locationMessage = 'Coordinates selected. You can drag the pin for a more precise position.';
          },
          useCurrentLocation: function() {
            if (!navigator.geolocation) {
              this.locationMessage = 'Location access is not supported by this browser.';
              return;
            }

            this.isLocating = true;
            this.locationMessage = 'Waiting for location permission…';

            navigator.geolocation.getCurrentPosition((position) => {
              this.setCoordinates(position.coords.latitude, position.coords.longitude, true);
              this.locationMessage = 'Device location found (accuracy about '
                + Math.round(position.coords.accuracy) + ' metres). Drag the pin if needed.';
              this.isLocating = false;
            }, (error) => {
              const messages = {
                1: 'Location permission was denied. Click the map to place the pin manually.',
                2: 'Your location is currently unavailable. Click the map to place the pin manually.',
                3: 'Getting your location timed out. Please try again or place the pin manually.',
              };

              this.locationMessage = messages[error.code] || 'Unable to get your location. Place the pin manually.';
              this.isLocating = false;
            }, {
              enableHighAccuracy: true,
              maximumAge: 0,
              timeout: 15000,
            });
          },
          onDelete: function() {
            self = this;
            
            var txt;
            var r = confirm("Delete this record?");
            if (r == true) {
              axios.delete('/api/merchant/location/'+self.field.id+'/delete', {}).then((response) => {
                if (response.data.status) {
                  toastr.success(response.data.message);
                  this.fetchData();
                  this.action('view');
                }
                else {
                  toastr.info(response.data.message);
                }
              }).catch((errors) => {
                  if (errors.response.status == 422) {
                    toastr.error(errors.response.data.message);
                  }
                  else {
                    toastr.error(errors);  
                  }
              }); 
            }            
          },
          onSubmit: function() {

              if (this.validateForm()) {
                if (this.actionStatus == 'add') {
                     axios.post('/api/merchant/location/submit', {
                        address_1: this.field.address_1,
                        address_2: this.field.address_2,
                        zip: this.field.zip_code,
                        city: this.field.city,
                        mobile: this.field.mobile,
                        telephone: this.field.telephone,
                        latitude: this.field.latitude,
                        longtitude: this.field.longtitude,
                        active: this.field.active,
                        checkout_options: this.field.checkout_options,

                      }).then((response) => {
                        if (response.data.status) {
                          toastr.success(response.data.message);
                          this.clearForm();
                          this.fetchData();
                          this.action('view');
                        }
                        else {
                          toastr.info(response.data.message);
                        }
                      }).catch((errors) => {
                          toastr.error(errors);
                      }); 
                }
                else if (this.actionStatus == 'edit') {

                   axios.put('/api/merchant/location/'+ this.field.id +'/submit', {
                      address_1: this.field.address_1,
                        address_2: this.field.address_2,
                        zip: this.field.zip_code,
                        city: this.field.city,
                        mobile: this.field.mobile,
                        telephone: this.field.telephone,
                        latitude: this.field.latitude,
                        longtitude: this.field.longtitude,
                        active: this.field.active,
                        checkout_options: this.field.checkout_options,

                    }).then((response) => {
                      if (response.data.status) {
                        toastr.success(response.data.message);
                        this.clearForm();
                        this.fetchData();
                        this.action('view');
                      }
                      else {
                        toastr.info(response.data.message);
                      }
                    }).catch((errors) => {
                        toastr.error(errors);
                    }); 

                }
              }
            },
            validateForm: function () 
            {
                var self = this;
                var i;
                this.errors = [];
                $('#address_1').removeClass('is-invalid ding');
                $('#address_2').removeClass('is-invalid ding');
                $('#zip').removeClass('is-invalid ding');
                $('#city').removeClass('is-invalid ding');
                $('#mobile').removeClass('is-invalid ding');
                $('#telephone').removeClass('is-invalid ding');
                $('#latitude').removeClass('is-invalid ding');
                $('#longtitude').removeClass('is-invalid ding');
                
                if (!this.field.address_1) {
                    this.errors.push("Address line 1 is required.");
                  $('#address_1').addClass('is-invalid ding');
                }
                // if (!this.field.address_2) {
                //   this.errors.push("Address line 2 is required.");
                //   $('#address_2').addClass('is-invalid ding');
                // }
                if (!this.field.zip_code) {
                  this.errors.push("zip is required.");
                  $('#zip').addClass('is-invalid ding');
                }
                if (!this.field.city) {
                  this.errors.push("City is required.");
                  $('#city').addClass('is-invalid ding');
                }
                if (!this.field.mobile) {
                  this.errors.push("Mobile is required.");
                  $('#mobile').addClass('is-invalid ding');
                }
                if (!this.field.telephone) {
                  this.errors.push("Telephone is required.");
                  $('#telephone').addClass('is-invalid ding');
                }
                const latitude = Number(this.field.latitude);
                const longitude = Number(this.field.longtitude);
                if (this.field.latitude === '' || this.field.latitude === null || !Number.isFinite(latitude) || latitude < -90 || latitude > 90) {
                  this.errors.push("A valid latitude is required.");
                  $('#latitude').addClass('is-invalid ding');
                }
                if (this.field.longtitude === '' || this.field.longtitude === null || !Number.isFinite(longitude) || longitude < -180 || longitude > 180) {
                  this.errors.push("A valid longitude is required.");
                  $('#longtitude').addClass('is-invalid ding');
                }
                if (!Array.isArray(this.field.checkout_options) || this.field.checkout_options.length === 0) {
                  this.errors.push("Select at least one checkout option.");
                }
              
                if (!this.errors.length) {
                  return true;
                } 
                else {
                  return false;
                }

              },
              clearForm: function() 
              {
                this.field = emptyLocation();
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>

<style scoped>
.merchant-location-picker {
  background: #faf9f7;
  border: 1px solid #e5dfd9;
  border-radius: 12px;
  padding: 16px;
}

.merchant-checkout-options {
  background: #faf9f7;
  border: 1px solid #e5dfd9;
  border-radius: 12px;
  margin-bottom: 20px;
  padding: 16px;
}

.merchant-checkout-options > div:first-child strong,
.merchant-checkout-options > div:first-child small,
.merchant-checkout-option strong,
.merchant-checkout-option small {
  display: block;
}

.merchant-checkout-options > div:first-child small,
.merchant-checkout-option small {
  color: #697277;
  font-size: 11px;
}

.merchant-checkout-option-grid {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-top: 12px;
}

.merchant-checkout-option {
  align-items: flex-start;
  background: #fff;
  border: 1px solid #ddd5ce;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  gap: 10px;
  margin: 0;
  padding: 12px;
}

.merchant-checkout-option.selected {
  border-color: #d5322f;
  box-shadow: 0 0 0 1px #d5322f;
}

.merchant-checkout-option input {
  margin-top: 4px;
}

.merchant-option-badge {
  background: #f4eeea;
  border-radius: 999px;
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  margin: 2px 4px 2px 0;
  padding: 4px 8px;
}

.merchant-table-notice,
.merchant-table-empty {
  align-items: center;
  background: #fff8e8;
  border: 1px solid #f0d99d;
  border-radius: 10px;
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  padding: 12px 14px;
}

.merchant-table-manager {
  border: 1px solid #e5dfd9;
  border-radius: 12px;
  margin-bottom: 20px;
  overflow: hidden;
}

.merchant-table-manager-header {
  align-items: center;
  background: #faf9f7;
  display: flex;
  justify-content: space-between;
  padding: 16px;
}

.merchant-table-manager-header strong,
.merchant-table-manager-header small {
  display: block;
}

.merchant-table-manager-header small,
.merchant-table-manager-header > span {
  color: #697277;
  font-size: 11px;
}

.merchant-table-add {
  align-items: end;
  display: grid;
  gap: 12px;
  grid-template-columns: minmax(180px, 1fr) 150px auto;
  padding: 16px;
}

.merchant-table-add .form-group {
  margin: 0;
}

.merchant-table-empty {
  background: #faf9f7;
  border: 0;
  border-radius: 0;
  color: #697277;
  margin: 0;
}

.merchant-dining-table-list {
  margin: 0;
}

.merchant-dining-table-list td {
  vertical-align: middle;
}

.merchant-table-capacity {
  max-width: 100px;
}

.merchant-table-checkbox {
  align-items: center;
  display: flex;
  gap: 7px;
  margin: 0;
  white-space: nowrap;
}

.merchant-location-picker-header {
  align-items: center;
  display: flex;
  gap: 16px;
  justify-content: space-between;
  margin-bottom: 12px;
}

.merchant-location-picker-header label,
.merchant-location-picker-header small {
  display: block;
  margin: 0;
}

.merchant-location-picker-header small,
.merchant-location-message {
  color: #697277;
  font-size: 11px;
}

.merchant-location-button {
  flex: 0 0 auto;
}

.merchant-location-button i {
  margin-right: 6px;
}

.merchant-location-map {
  background: #ebe8e4;
  border: 1px solid #ddd7d1;
  border-radius: 10px;
  height: 320px;
  overflow: hidden;
  width: 100%;
}

.merchant-location-message {
  display: block;
  margin-top: 9px;
}

@media (max-width: 767px) {
  .merchant-checkout-option-grid,
  .merchant-table-add {
    grid-template-columns: 1fr;
  }

  .merchant-location-picker-header {
    align-items: stretch;
    flex-direction: column;
  }

  .merchant-location-map {
    height: 280px;
  }
}
</style>

<style>
.merchant-location-pin-wrapper {
  background: transparent;
  border: 0;
}

.merchant-location-pin {
  align-items: center;
  background: #ef3434;
  border: 3px solid #fff;
  border-radius: 50% 50% 50% 8px;
  box-shadow: 0 5px 14px rgba(33, 25, 22, .28);
  color: #fff;
  display: flex;
  font-size: 16px;
  height: 36px;
  justify-content: center;
  transform: rotate(-45deg);
  width: 36px;
}

.merchant-location-pin i {
  transform: rotate(45deg);
}
</style>
