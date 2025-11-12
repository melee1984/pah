<template>
  <div class="col-md-12">
    <div class="row">
      <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <div class="card">
         <div class="card-header">
              <h3 class="card-title">
                Information   
              </h3>

              <div class="card-tools">
                
              </div>
            </div>
          <!-- /.card-header -->
          <div class="card-body table-responsive">
              <div class="row"> 
                    <div class="col-12">
                        <form role="form" v-on:submit.prevent="onSubmit" method="post">

                         <div class="form-group">
                          <div class="col-6">
                              <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="active" checked="" v-model="field.active">
                                <label class="custom-control-label" for="active">Active</label>
                              </div>
                          </div>
                          <div class="col-6">
                               <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="store_open" checked="" v-model="field.store_open">
                                <label class="custom-control-label" for="store_open">Store Open</label>
                              </div>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Restaurant Name</label>
                              <input type="text" class="form-control" placeholder="Restaurant Name" id="restaurant_name" v-model="field.restaurant_name">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Email Address</label>
                              <input type="text" class="form-control" placeholder="Email Address" id="email" v-model="field.email">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Telephone</label>
                              <input type="text" class="form-control" placeholder="Telephone" id="telephone" v-model="field.telephone">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Mobile</label>
                              <input type="text" class="form-control" placeholder="Mobile" id="mobile" v-model="field.mobile">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Office Address</label>
                              <input type="text" class="form-control" placeholder="Office Address" id="address" v-model="field.address">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>City</label>
                              <input type="text" class="form-control" placeholder="City" id="city" v-model="field.city">
                            </div>
                          </div>
                        </div>
                         <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Verified Date</label>
                              <input type="text" class="form-control" placeholder="Verified Date" id="verified_at" v-model="field.verified_at" disabled="">
                            </div>
                          </div>
                        </div>
                        <br>
                        <br>
                        <div class="card-footer">
                          <button type="submit" class="btn btn-pahatud float-left">Submit</button>
                        </div>

                        </form>
                      </div>
                  </div> 
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
       <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

        <div class="card card-outline">
                <div class="card-header">
                  <h3 class="card-title">
                    Store Properties
                  </h3>
                  <div class="card-tools">
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive">
                  <div class="row"> 
                    <div class="col-12">
                          <div class="row">
                            <div class="col-12">
                              <!-- Custom Tabs -->
                              <div class="card pull-right">
                                <div class="card-header d-flex p-0">
                                  <h3 class="card-title p-3"></h3>
                                  <ul class="nav nav-pills ml-auto p-2 pull-left">
                                    <li class="nav-item"><a class="nav-link active" href="#img" data-toggle="tab">Image</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#banner" data-toggle="tab">Banner</a></li>
                                    <li class="nav-item"><a class="nav-link " href="#desc" data-toggle="tab">Description</a></li>
                                    <li class="nav-item"><a class="nav-link " href="#tags" data-toggle="tab">Tags</a></li>
                                  </ul>
                                </div><!-- /.card-header -->
                                <div class="card-body">
                                  <div class="tab-content">
                                    
                                <!-- /.tab-pane -->
                                    <div class="tab-pane active" id="img">
                                        <div class="form-group">
                                          <div class="col-md-12">
                                        <img :src="field.img" class="img-thumbnail">  

                                          </div>
                                          <br>
                                           <p class="text-success">Note: Please upload only 500x500 image dimension. <br>Only accept jpg, jpeg, png extension</p>
                                          <div class="input-group">
                                            <div class="custom-file">
                                              <input type="file" class="custom-file-input" id="file" @change="onFileSelected">
                                              <label class="custom-file-label" for="img">Choose file</label>
                                            </div>
                                             <div class="input-group-append">
                                              <span class="input-group-text" >
                                                <a href="javascript:void(0)" v-on:click="onUploadImage">{{ uploadStatus }}</a>
                                              </span>
                                            </div>
                                          </div>

                                        </div>
                                    </div>
                                    <div class="tab-pane" id="tags">
                                        <div class="form-group">
                                          <div class="col-md-12">
                                            <ul class="sector">
                                              <li v-for="sector in sectors">

                                                 <div class="custom-control custom-switch">
                                                  <input type="checkbox" class="custom-control-input" :id="'sector'+sector.id" v-model="sector.selected" v-on:click="updateSector(sector.id, sector.selected)">
                                                  <label class="custom-control-label" :for="'sector'+sector.id">{{ sector.name }}</label>
                                                </div>

                                              </li>
                                              
                                            </ul>
                                          </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="banner">
                                        <div class="form-group">
                                          <div class="col-md-12">
                                        <img :src="field.banner" class="img-fluid">  

                                          </div>
                                          <br>
                                           <p class="text-success">Note: Please upload only 1920x600 image dimension. <br>Only accept jpg, jpeg, png extension</p>
                                          <div class="input-group">
                                            <div class="custom-file">
                                              <input type="file" class="custom-file-input" id="file" @change="onFileSelectedBanner">
                                              <label class="custom-file-label" for="img">Choose file</label>
                                            </div>
                                             <div class="input-group-append">
                                              <span class="input-group-text" >
                                                <a href="javascript:void(0)" v-on:click="onUploadImageBanner">{{ uploadStatusBanner }}</a>
                                              </span>
                                            </div>
                                          </div>

                                        </div>
                                    </div>
                                    
                                    <!-- /.tab-pane -->
                                    <div class="tab-pane" id="desc">
                                        <div class="row">
                                          <div class="col-sm-12">
                                            <div class="form-group">
                                              <label>Description</label>
                                              <textarea v-model="field.description" class="form-control" placeholder="Enter Description" rows="15">{{ field.description }}</textarea>
                                            </div>
                                          </div>
                                        </div>
                                    </div>
                                    <!-- /.tab-pane -->
                                  </div>
                                  <!-- /.tab-content -->
                                </div><!-- /.card-body -->
                              </div>
                              <!-- ./card -->
                            </div>
                            <!-- /.col -->
                          </div>


                      
                      </div>
                  </div> 
                </div>
                <!-- /.card-body -->
              </div>

                <!-- End of the other side --> 
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

