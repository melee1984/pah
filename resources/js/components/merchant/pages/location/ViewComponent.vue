<template>
  <div class="merchant-settings-page">
    <div v-if="actionStatus === 'view'" class="card admin-card dashboard-data-card">
      <div class="admin-card-header merchant-settings-card-header">
        <div><h2>Store locations</h2><p>{{ searchFilter.length }} locations shown. Select a row to edit its details.</p></div>
        <div class="merchant-settings-toolbar">
          <label class="admin-search" for="location-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input id="location-search" v-model.trim="search" type="search" placeholder="Search addresses">
          </label>
          <button type="button" class="btn admin-btn-primary" @click="action('add')"><i class="fas fa-plus mr-2"></i>Add location</button>
        </div>
      </div>
      <div class="card-body table-responsive p-0">
        <table class="table dashboard-data-table merchant-settings-table merchant-location-table">
          <thead><tr><th>Location</th><th>Telephone</th><th>Mobile</th><th>Coordinates</th><th class="text-right">Availability</th></tr></thead>
          <tbody>
            <tr v-if="searchFilter.length === 0"><td colspan="5" class="dashboard-table-empty">{{ search ? 'No locations match your search.' : 'No store locations have been added yet.' }}</td></tr>
            <tr v-for="location in searchFilter" :key="location.id" class="merchant-settings-row" @click="editAction(location)">
              <td><strong>{{ location.address_1 }}</strong><small>{{ [location.address_2, location.city, location.zip_code].filter(Boolean).join(', ') || 'No additional address details' }}</small></td>
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
          <div><span class="admin-eyebrow">Location details</span><h2>{{ actionStatus === 'add' ? 'Add a store location' : 'Edit store location' }}</h2><p>Provide an address and reliable contact details for this branch.</p></div>
          <button v-if="actionStatus === 'edit'" type="button" class="btn merchant-danger-button" @click="onDelete"><i class="fas fa-trash-alt mr-2"></i>Delete</button>
        </div>
        <form class="merchant-settings-form" @submit.prevent="onSubmit">
          <div class="merchant-toggle-panel">
            <div><strong>Location availability</strong><small>Active locations can receive customer orders.</small></div>
            <label class="merchant-toggle" for="active"><input id="active" v-model="field.active" type="checkbox"><span><i></i></span><strong>{{ field.active ? 'Active' : 'Hidden' }}</strong></label>
          </div>
          <div class="merchant-form-grid">
            <div class="form-group merchant-form-span"><label for="address_1">Address line 1</label><input id="address_1" v-model.trim="field.address_1" type="text" class="form-control" placeholder="Street, building, or unit"></div>
            <div class="form-group merchant-form-span"><label for="address_2">Address line 2 <small>(optional)</small></label><input id="address_2" v-model.trim="field.address_2" type="text" class="form-control" placeholder="Barangay or landmark"></div>
            <div class="form-group"><label for="city">City</label><input id="city" v-model.trim="field.city" type="text" class="form-control" placeholder="City"></div>
            <div class="form-group"><label for="zip">ZIP code</label><input id="zip" v-model.trim="field.zip_code" type="text" class="form-control" placeholder="ZIP code"></div>
            <div class="form-group"><label for="mobile">Mobile</label><input id="mobile" v-model.trim="field.mobile" type="text" class="form-control" placeholder="Mobile number"></div>
            <div class="form-group"><label for="telephone">Telephone</label><input id="telephone" v-model.trim="field.telephone" type="text" class="form-control" placeholder="Telephone number"></div>
            <div class="form-group"><label for="latitude">Latitude</label><input id="latitude" v-model.trim="field.latitude" type="number" step="any" min="-90" max="90" class="form-control" placeholder="e.g. 10.3157"><small class="merchant-field-help">A value from -90 to 90.</small></div>
            <div class="form-group"><label for="longtitude">Longitude</label><input id="longtitude" v-model.trim="field.longtitude" type="number" step="any" min="-180" max="180" class="form-control" placeholder="e.g. 123.8854"><small class="merchant-field-help">A value from -180 to 180.</small></div>
          </div>
          <div class="merchant-form-actions"><button type="button" class="btn admin-btn-secondary" @click="cancel">Cancel</button><button type="submit" class="btn admin-btn-primary">{{ actionStatus === 'add' ? 'Add location' : 'Save changes' }}</button></div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>

     export default {
       data() {
            return {
                field: {
                  active: false,
                  address_1: '',
                  address_2: '',
                  city: '',
                  zip_code: '',
                  mobile: '',
                  telephone: '',
                  latitude: '',
                  longtitude: '',
                },
                Temp: [],
                errors: {},
                isSubmit: false,
                display: {},
                actionStatus: 'view',
                search: '',
                locations: {},
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
            this.actionStatus = action;
          },
          editAction: function(location) {
            this.action('edit');
            this.field = location;
          },
          cancel: function(location) {
            this.action('view');
            this.field = {};
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
              
                if (!this.errors.length) {
                  return true;
                } 
                else {
                  return false;
                }

              },
              clearForm: function() 
              {
                this.field.address_1 = "";
                this.field.address_2 = "";
                this.field.zip_code = "";
                this.field.city = "";
                this.field.mobile = "";
                this.field.telephone = "";
                this.field.latitude = "";
                this.field.longtitude = "";
                this.field.active = false;
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>
