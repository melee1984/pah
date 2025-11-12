<template>
	<div class="col-md-12">
		<div class="row">
		  <div class="col-12" v-if="actionStatus=='view'">
		    <div class="card">
		     <div class="card-header">
                  <h3 class="card-title">
                 <div class="input-group input-group-sm" style="width: 150px;">
                  <input type="text" name="table_search" class="form-control float-right" placeholder="Category Name" v-model="search" @keyup.enter="searchFilter">
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
		              <th>Parent Category</th>
		              <th>Name</th>
		              <th class="text-right">Active</th>
		            </tr>
		          </thead>
		          <tbody>
		            <tr v-for="category in searchFilter" v-on:click="editAction(category)">
		             <!--  <td>{{ category.id }}</td> -->
                  <td width="25%">
                    <div v-if="category.parent">
                      {{ category.parent.name }}
                    </div>
                    <div v-else>
                        -
                    </div>
                  </td>
		              <td width="25%">{{ category.name }}</td>
		              <td width="50%" align="right">
		              	<div class="custom-control custom-switch">
		                <input type="checkbox" class="custom-control-input" :id="'is_active'+category.id" v-model="category.active" v-on:click="updateStatus(category.id, category.active)">
		                <label class="custom-control-label" :for="'is_active'+category.id"></label>
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
                    ADDING Category
                  </h3>
                  <h3 class="card-title" v-if="actionStatus=='edit'">
                    Edit Category
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
                            <input type="checkbox" class="custom-control-input" id="active" v-model="field.active">
                            <label class="custom-control-label" for="active">Active</label>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <!-- text input -->
                            <div class="form-group">
                              <label>Category Title</label>
                              <input type="text" class="form-control" placeholder="Enter name" id="name" v-model="field.name">
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="form-group">
                              <label>Parent Category</label>
                              <select class="form-control" id="category" v-model="field.parent_category">
                                <option value="">Parent Category</option>
                                <option v-for="category in parent_category" :value=category.id>
                                   {{ category.name }}
                                 </option>
                            </select>
                            </div>
                          </div>
                        </div>
                        <br>
                        <br>
                        <div class="card-footer">
                          <button type="submit" class="btn btn-pahatud float-left">Submit</button>
                          <a href="/merchant/category" class="btn btn-default float-right"><i class="fas window-close"></i> Cancel</a>
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
                  name: '',
                  parent_category: "",
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
            return this.Temp.filter(category => {
              return category.name.toLowerCase().includes(this.search.toLowerCase())
            })
          }
        },
        methods: {
          updateStatus: function(category, val) {
              axios.put('/api/merchant/category/'+category+'/update/status', {
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
            this.clearForm();

            if (action == 'add') {
              this.clearForm();
            }

            this.actionStatus = action;
          },
          editAction: function(category) {
            this.clearForm();
            this.action('edit');
            this.field = category;

            if (!category.parent_category_id) {
              this.field.parent_category = "";
            }

          },
          cancel: function(category) {
            this.action('view');
          },

          filterCategory: function() {
            console.log('filter-category');
          },
          fetchData: function(page = 1) {
              var self = this;
              axios.get('/api/merchant/category/list?page='+page).then(function (response) {
                  self.categories = response.data.categories;
                  self.Temp = self.categories.data;
                  self.parent_category = response.data.parent_category;
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
              axios.delete('/api/merchant/category/'+self.field.id+'/delete', {}).then((response) => {
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
                     axios.post('/api/merchant/category/submit', {
                        name: this.field.name,
                        parent_category_id: this.field.parent_category,
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
                          if (errors.response.status == 422) {
                            toastr.error(errors.response.data.message);
                          }
                          else {
                            toastr.error(errors);  
                          }
                      }); 
                }
                else if (this.actionStatus == 'edit') {

                   axios.put('/api/merchant/category/'+ this.field.id +'/submit', {
                      name: this.field.name,
                      parent_category_id: this.field.parent_category,
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
                         if (errors.response.status == 422) {
                            toastr.error(errors.response.data.message);
                          }
                          else {
                            toastr.error(errors);  
                          }
                    }); 

                }
              }
            },
            validateForm: function () 
            {
                var self = this;
                var i;
                this.errors = [];
                $('#category').removeClass('is-invalid ding');
                $('#name').removeClass('is-invalid ding');
                if (!this.field.name) {
                  this.errors.push("name name is required.");
                  $('#name').addClass('is-invalid ding');
                }
              
                if (this.field.parent_category=="0") {
                  this.errors.push("Parent Category is required.");
                  $('#category').addClass('is-invalid ding');
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
                this.field = {};
                this.field.name = "";
                this.field.parent_category = "";
                this.field.active = false;
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>
