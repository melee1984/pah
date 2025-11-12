<template>
  <div class="col-md-12">
    <div class="row">
      <div class="col-12" v-if="actionStatus=='view'">
        <div class="card">
         <div class="card-header">
                  <h3 class="card-title">
                 <div class="input-group input-group-sm" style="width: 150px;">
                  <input type="text" name="table_search" class="form-control float-right" placeholder="Address" v-model="search" @keyup.enter="searchFilter">
                  <div class="input-group-append">
                    <button type="submit" class="btn btn-default" v-on:click="searchFilter"><i class="fas fa-search"></i></button>
                  </div>
                </div>
              </h3>

              <div class="card-tools">
                <a href="javascript:void(0)" class="btn btn-pahatud" v-on:click="action('add')"><i class="fas fa-plus"></i> ADD</a>
              </div>
            </div>
          <!-- /.card-header -->
          <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
              <thead>
                <tr>
                 <tr>
                  <th>Location</th>
                  <th>Telephone</th>
                  <th>Mobile</th>
                  <th>Latitude</th>
                  <th>Longtitude</th>
                  <th class="text-right">Active</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="location in searchFilter" v-on:click="editAction(location)">
                  <td width="50%">
                      {{ location.address_1 }} <br/>
                      <div v-if="location.address_2 !=''">
                        {{ location.address_2 }} <br/>
                      </div>
                      {{ location.city }} <br/>
                      {{ location.zip_code }}  <br/>
                  </td>
                  <td>{{ location.telephone }}</td>
                  <td>{{ location.mobile }}</td>
                  <td>{{ location.longtitude }}</td>
                  <td>{{ location.latitude }}</td>
                  <td width="50%" align="right">
                    <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" :id="'is_active'+location.id" v-model="location.active" v-on:click="updateStatus(location.id, location.active)">
                    <label class="custom-control-label" :for="'is_active'+location.id"></label>
                  </div>
                  </td>
                 
                </tr>
              </tbody>
              <tfoot v-if="categories.last_page > 1">
                <tr>
                  <td colspan="4">
                    <pagination-display :data="categories" @pagination-change-page="fetchData"></pagination-display>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
    </div>
    <div class="col-12" v-if="actionStatus!='view'">
          <div class="row">
            <div class="col-md-6 col-lg-6 col-xs-12 col-sm-12">
              <div class="card card-primary card-outline">
                <div class="card-header">
                  <h3 class="card-title" v-if="actionStatus=='add'">
                    Adding Location
                  </h3>
                  <h3 class="card-title" v-if="actionStatus=='edit'">
                    Edit Location
                  </h3>
                  <div class="card-tools">
                    <a href="javascript:void(0)" v-if="actionStatus=='edit'" class="btn btn-danger btn-sm" v-on:click="onDelete()"><i class="fas fa-close"></i> DELETE</a>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive">
                  <div class="row"> 
                    <div class="col-12">
                        <form role="form" v-on:submit.prevent="onSubmit" method="post">
                         <div class="form-group">
                          <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="active" checked="" v-model="field.active">
                            <label class="custom-control-label" for="active">Active</label>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Address 1</label>
                              <input type="text" class="form-control" placeholder="Enter Address line 1" id="address_1" v-model="field.address_1">
                            </div>
                          </div>
                        </div>
                         <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Address 2</label>
                              <input type="text" class="form-control" placeholder="Enter Address line 2" id="address_2" v-model="field.address_2">
                            </div>
                          </div>
                        </div>
                         <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>City</label>
                              <input type="text" class="form-control" placeholder="Enter City" id="city" v-model="field.city">
                            </div>
                          </div>
                        </div>
                         <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Zip Code</label>
                              <input type="text" class="form-control" placeholder="Enter Zip code" id="zip" v-model="field.zip_code">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Mobile</label>
                              <input type="text" class="form-control" placeholder="Enter Mobile" id="mobile" v-model="field.mobile">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Telephone</label>
                              <input type="text" class="form-control" placeholder="Enter Telephone" id="telephone" v-model="field.telephone">
                            </div>
                          </div>
                        </div>
                        
                        
                        <br>
                        <br>
                        <div class="card-footer">
                          <button type="submit" class="btn btn-pahatud float-left">Submit</button>
                          <a href="javascript:void(0)" class="btn btn-default float-right" v-on:click="cancel"><i class="fas window-close"></i> Cancel</a>
                        </div>

                        </form>
                      </div>
                  </div> 
                </div>
                <!-- /.card-body -->
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
                  address_1: '',
                  address_2: '',
                  city: '',
                  zip_code: '',
                  mobile: '',
                  telephone: '',
                },
                Temp: [],
                errors: {},
                isSubmit: false,
                display: {},
                actionStatus: 'view',
                search: '',
                categories:[],
                parent_category: {},
            }
        },
        mounted() {
            console.log('Mounted Merchant View Component')
            this.fetchData(1);
        },
        computed: {
          searchFilter() {
            return this.Temp.filter(location => {
              return location.address_1.toLowerCase().includes(this.search.toLowerCase())
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
                  self.location = response.data.location;
                  self.Temp = self.location.data;
                  
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
                this.field.active = false;
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>
