<template>
  <div class="col-md-12">
    <div class="row">
      <div class="col-12" v-if="actionStatus=='view'">
        <div class="card">
         <div class="card-header">
                  <h3 class="card-title">
                 <div class="input-group input-group-sm" style="width: 150px;">
                 <!--  <input type="text" name="table_search" class="form-control float-right" placeholder="Search" v-model="search" @keyup.enter="searchFilter">
                  <div class="input-group-append">
                    <button type="submit" class="btn btn-default" v-on:click="searchFilter"><i class="fas fa-search"></i></button>
                  </div> -->
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
                <th>Start and End Date</th>

                  <th>Voucher</th>
                  <th>Limit</th>
                  <th>Remaining</th>
                  <th class="text-right">Active</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="obj in searchFilter" v-on:click="editAction(obj)">
                   <td width="20%">
                     {{ obj.start_at }} - {{ obj.expired_at }}
                  </td>
                  <td width="10%">
                      {{ obj.amount }}
                  </td>
                 
                  <td> {{ obj.limit }}</td>
                  <th> 0 </th>
                  <td width="50%" align="right">
                    <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" :id="'is_active'+obj.id" v-model="obj.active" v-on:click="updateStatus(obj.id, obj.active)">
                    <label class="custom-control-label" :for="'is_active'+obj.id"></label>
                  </div>
                  </td>
                 
                </tr>
              </tbody>
              <tfoot v-if="voucher.last_page > 1">
                <tr>
                  <td colspan="4">
                    <pagination-display :data="voucher" @pagination-change-page="fetchData"></pagination-display>
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
                              <label>Amount Discount</label>
                              <input type="number" class="form-control" placeholder="Discount Amount" id="address_1" v-model="field.amount">
                            </div> 
                          </div>
                        </div>
                       <div class="form-group">
                          <label>Begin at</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text">
                                <i class="far fa-calendar-alt"></i>
                              </span>
                            </div>
                             <input type="datetime" class="form-control"  v-model="field.begin_at">
                          </div>
                          <!-- /.input group -->
                        </div>
                        <div class="form-group">
                          <label>Expire at</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text">
                                <i class="far fa-calendar-alt"></i>
                              </span>
                            </div>
                            <input type="datetime" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask v-model="field.expire_at">
                          </div>
                          <!-- /.input group -->
                        </div>
                         <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Limit</label>
                              <input type="text" class="form-control" placeholder="Enter Limit" id="limit" v-model="field.limit">
                            </div>
                          </div>
                        </div>

                        <div class='input-group date' id='datetimepicker1'>
                           <input type='text' class="form-control" />
                           <span class="input-group-addon">
                           <span class="glyphicon glyphicon-calendar"></span>
                           </span>
                        </div>
                  
                        <br>
                        <br>
                        <div class="card-footer">
                          <button type="submit" class="btn btn-pahatud float-left">Submit</button>
                          <a href="javascript:void(0)" class="btn btn-default float-right" v-on:click="action('view')"><i class="fas window-close"></i> Cancel</a>
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
                voucher:[],
            }
        },
        mounted() {
            console.log('Mounted Voucher View Component')
            this.fetchData(1);
        },
        computed: {
          searchFilter: function() {
            return this.Temp;
          }
        },
        methods: {
          updateStatus: function(voucher, val) {
              axios.put('/api/merchant/voucher/'+voucher+'/update/status', {
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
          filterCategory: function() {
            console.log('filter-category');
          },
          fetchData: function(page = 1) {
              var self = this;
              axios.get('/api/merchant/voucher/list?page='+page).then(function (response) {
                  self.voucher = response.data.voucher;
                  self.Temp = self.voucher.data;
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
              axios.delete('/api/merchant/voucher/'+self.field.id+'/delete', {}).then((response) => {
                if (response.data.status) {
                  toastr.success(response.data.message);
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
          },
          onSubmit: function() {

              if (this.validateForm()) {
                if (this.actionStatus == 'add') {
                     axios.post('/api/merchant/voucher/submit', {
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

                   axios.put('/api/merchant/voucher/'+ this.field.id +'/submit', {
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

