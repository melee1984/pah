<template>
  <div class="merchant-settings-page merchant-profile-layout">
    <div class="card admin-card merchant-profile-card">
      <div class="admin-card-header">
        <div><span class="admin-eyebrow">Business information</span><h2>Store details</h2><p>These details identify your business across Pahatud.</p></div>
      </div>
      <form class="merchant-settings-form" @submit.prevent="onSubmit">
        <div class="merchant-profile-statuses">
          <div class="merchant-toggle-panel">
            <div><strong>Profile active</strong><small>Controls whether this merchant profile is active.</small></div>
            <label class="merchant-toggle" for="active"><input id="active" v-model="field.active" type="checkbox"><span><i></i></span><strong>{{ field.active ? 'Active' : 'Inactive' }}</strong></label>
          </div>
          <div class="merchant-toggle-panel">
            <div><strong>Store availability</strong><small>Let customers know whether you are accepting orders.</small></div>
            <label class="merchant-toggle" for="store_open"><input id="store_open" v-model="field.store_open" type="checkbox"><span><i></i></span><strong>{{ field.store_open ? 'Open' : 'Closed' }}</strong></label>
          </div>
        </div>
        <div class="merchant-form-grid">
          <div class="form-group merchant-form-span"><label for="restaurant_name">Restaurant name</label><input id="restaurant_name" v-model.trim="field.restaurant_name" type="text" class="form-control" placeholder="Restaurant name"></div>
          <div class="form-group merchant-form-span"><label for="email">Email address</label><input id="email" v-model.trim="field.email" type="text" class="form-control" placeholder="Email address"></div>
          <div class="form-group"><label for="telephone">Telephone</label><input id="telephone" v-model.trim="field.telephone" type="text" class="form-control" placeholder="Telephone"></div>
          <div class="form-group"><label for="mobile">Mobile</label><input id="mobile" v-model.trim="field.mobile" type="text" class="form-control" placeholder="Mobile"></div>
          <div class="form-group merchant-form-span"><label for="address">Office address</label><input id="address" v-model.trim="field.address" type="text" class="form-control" placeholder="Office address"></div>
          <div class="form-group"><label for="city">City</label><input id="city" v-model.trim="field.city" type="text" class="form-control" placeholder="City"></div>
          <div class="form-group"><label for="verified_at">Verified date</label><input id="verified_at" v-model="field.verified_at" type="text" class="form-control" placeholder="Not yet verified" disabled></div>
          <div class="form-group merchant-form-span"><label for="description">Store description</label><textarea id="description" v-model="field.description" class="form-control" rows="5" placeholder="Tell customers about your store"></textarea></div>
        </div>
        <div class="merchant-form-actions"><button type="submit" class="btn admin-btn-primary"><i class="fas fa-save mr-2"></i>Save profile</button></div>
      </form>
    </div>

    <div class="card admin-card merchant-profile-card">
      <div class="admin-card-header">
        <div><span class="admin-eyebrow">Storefront</span><h2>Branding &amp; discovery</h2><p>Manage the visuals and tags customers use to recognize your store.</p></div>
      </div>
      <div class="merchant-profile-tabs">
        <ul class="nav dashboard-table-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" href="#profile-image" data-toggle="tab"><i class="fas fa-store mr-1"></i>Logo</a></li>
          <li class="nav-item"><a class="nav-link" href="#profile-banner" data-toggle="tab"><i class="fas fa-image mr-1"></i>Banner</a></li>
          <li class="nav-item"><a class="nav-link" href="#profile-tags" data-toggle="tab"><i class="fas fa-tags mr-1"></i>Tags</a></li>
        </ul>
      </div>
      <div class="card-body merchant-media-body">
        <div class="tab-content">
          <div id="profile-image" class="tab-pane active">
            <div class="merchant-media-preview merchant-logo-preview"><img v-if="field.img" :src="field.img" alt="Current store logo"><span v-else><i class="fas fa-store"></i>No logo uploaded</span></div>
            <div class="admin-form-note"><i class="fas fa-info-circle"></i><span>Use a square 500 × 500 JPG, JPEG, or PNG image for the clearest result.</span></div>
            <div class="merchant-upload-control"><div class="custom-file"><input id="profile-logo-file" type="file" class="custom-file-input" accept="image/jpeg,image/png" @change="onFileSelected"><label class="custom-file-label" for="profile-logo-file">Choose logo</label></div><button type="button" class="btn admin-btn-primary" @click="onUploadImage">{{ uploadStatus }}</button></div>
          </div>
          <div id="profile-banner" class="tab-pane">
            <div class="merchant-media-preview merchant-banner-preview"><img v-if="field.banner" :src="field.banner" alt="Current store banner"><span v-else><i class="fas fa-image"></i>No banner uploaded</span></div>
            <div class="admin-form-note"><i class="fas fa-info-circle"></i><span>Use a 1920 × 600 JPG, JPEG, or PNG image so the banner stays sharp on wide screens.</span></div>
            <div class="merchant-upload-control"><div class="custom-file"><input id="profile-banner-file" type="file" class="custom-file-input" accept="image/jpeg,image/png" @change="onFileSelectedBanner"><label class="custom-file-label" for="profile-banner-file">Choose banner</label></div><button type="button" class="btn admin-btn-primary" @click="onUploadImageBanner">{{ uploadStatusBanner }}</button></div>
          </div>
          <div id="profile-tags" class="tab-pane">
            <div class="merchant-sector-heading"><strong>Catalog tags</strong><p>Select every category that helps customers discover your store.</p></div>
            <div class="merchant-sector-grid">
              <label v-for="sector in sectors" :key="sector.id" class="merchant-sector-option" :for="'sector' + sector.id">
                <span><strong>{{ sector.name }}</strong><small>{{ sector.selected ? 'Included in your profile' : 'Not currently included' }}</small></span>
                <span class="merchant-toggle"><input :id="'sector' + sector.id" v-model="sector.selected" type="checkbox" @change="updateSector(sector.id, sector.selected)"><span><i></i></span></span>
              </label>
            </div>
          </div>
        </div>
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
                },
                Temp: [],
                errors: {},
                isSubmit: false,
                display: {},
                actionStatus: 'view',
                search: '',
                profile:[],
                uploadStatus: 'Upload',
                uploadStatusBanner: "Upload",
                timings: {},
                sectors: {},
            }
        },
        mounted() {
            this.fetchData();
        },
        computed: {
          searchFilter: function() {
            return this.Temp;
          }
        },
        methods: {
        fetchData: function() {
            var self = this;
            axios.get('/api/merchant/profile').then(function (response) {
                self.field = response.data.profile;
                self.sectors = response.data.sectors;
            })
            .catch(function (error) {
                toastr.error(error.message);
            });
        },
        onFileSelected: function(e) {
          this.fileImage = e.target.files[0];
        },
        onFileSelectedBanner: function(e) {
          this.fileImageBanner = e.target.files[0];
        },
        onUploadImageBanner: function() {
            var self = this;
            if (!this.fileImageBanner) return;
            self.uploadStatusBanner = "Please wait...";
            const fd = new FormData();
            fd.append('file', this.fileImageBanner, this.fileImageBanner.name);
            axios.post('/api/merchant/banner/upload/submit', fd, {
              onUploadImage: uploadEvent => {
                self.uploadStatusBanner = "Upload" + Math.round(uploadEvent.loaded / uploadEvent.total * 100) + "%"
              }
            }).then(function (response) {
              if (response.data.status) {
                toastr.success(response.data.message);
                  self.field.banner = response.data.banner;
                  self.uploadStatusBanner = "Upload";
              }
              else   {
                toastr.info(response.data.message);
                self.uploadStatusBanner = "Upload";
              }
              
            }).catch(function (error) {
              toastr.error(error.response.data.errors['file'][0]);
              self.uploadStatusBanner = "Upload";
            });
        },
        onUploadImage: function() {
              var self = this;

              if (!this.fileImage) return;

              self.uploadStatus = "Please wait...";

              const fd = new FormData();
              fd.append('file', this.fileImage, this.fileImage.name);
              axios.post('/api/merchant/profile/upload/submit', fd, {
                onUploadImage: uploadEvent => {
                  self.uploadStatus = "Upload" + Math.round(uploadEvent.loaded / uploadEvent.total * 100) + "%"
                }
              }).then(function (response) {
                if (response.data.status) {
                  toastr.success(response.data.message);
                    self.field.img = response.data.img;
                    self.uploadStatus = "Upload";
                }
                else   {
                  toastr.info(response.data.message);
                  self.uploadStatus = "Upload";
                }
                
              }).catch(function (error) {
                   toastr.error(error.response.data.errors['file'][0]);
                self.uploadStatus = "Upload";

              });


          },
          action: function(action) {
            this.actionStatus = action;
          },
          onSubmit: function() {
              if (this.validateForm()) {
                   axios.put('/api/merchant/profile/submit', {
                      active: this.field.active,
                      store_open: this.field.store_open,
                      restaurant_name: this.field.restaurant_name,
                      email: this.field.email,
                      telephone: this.field.telephone,
                      mobile: this.field.mobile,
                      address: this.field.address,
                      city: this.field.city,
                      description: this.field.description,
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
                        toastr.error(error.response.data.errors['file'][0]);
                    }); 
              }
            },
            validateForm: function () {
                var self = this;
                var i;
                this.errors = [];

                $('#restaurant_name').removeClass('is-invalid ding');
                $('#email').removeClass('is-invalid ding');
                $('#telephone').removeClass('is-invalid ding');
                $('#mobile').removeClass('is-invalid ding');
                $('#city').removeClass('is-invalid ding');
                $('#description').removeClass('is-invalid ding');

                if (!this.field.restaurant_name) {
                    this.errors.push("Restaurant name is required.");
                  $('#restaurant_name').addClass('is-invalid ding');
                }
                if (!this.field.email) {
                  this.errors.push("Email is required.");
                  $('#email').addClass('is-invalid ding');
                }
                if (!this.field.telephone) {
                  this.errors.push("Telephone is required.");
                  $('#telephone').addClass('is-invalid ding');
                }
                if (!this.field.mobile) {
                  this.errors.push("Mobile is required.");
                  $('#mobile').addClass('is-invalid ding');
                }
                if (!this.field.city) {
                  this.errors.push("City is required.");
                  $('#city').addClass('is-invalid ding');
                }
                if (!this.errors.length) {
                  return true;
                } 
                else {
                  return false;
                }
              },
              clearForm: function() {
                this.field.address_1 = "";
                this.field.address_2 = "";
                this.field.zip_code = "";
                this.field.city = "";
                this.field.mobile = "";
                this.field.telephone = "";
                this.field.active = false;
                this.field.store_open = false;
                this.errors = {};
                this.isSubmit = false;
              },

              updateSector: function(sector, val) {
                axios.put('/api/merchant/sector/'+sector+'/update/status', {
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

            }
    }

</script>
