<template>
  <div class="merchant-settings-page">
    <div v-if="actionStatus === 'view'" class="card admin-card dashboard-data-card">
      <div class="admin-card-header merchant-settings-card-header">
        <div>
          <h2>Product categories</h2>
          <p>{{ searchFilter.length }} categories shown. Select a row to edit it.</p>
        </div>
        <div class="merchant-settings-toolbar">
          <label class="admin-search" for="category-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input id="category-search" v-model.trim="search" type="search" placeholder="Search categories">
          </label>
          <button type="button" class="btn admin-btn-primary" @click="action('add')">
            <i class="fas fa-plus mr-2" aria-hidden="true"></i>Add category
          </button>
        </div>
      </div>

      <div class="card-body table-responsive p-0">
        <table class="table dashboard-data-table merchant-settings-table">
          <thead><tr><th>Category</th><th>Parent category</th><th class="text-right">Availability</th></tr></thead>
          <tbody>
            <tr v-if="searchFilter.length === 0">
              <td colspan="3" class="dashboard-table-empty">{{ search ? 'No categories match your search.' : 'No categories have been added yet.' }}</td>
            </tr>
            <tr v-for="category in searchFilter" :key="category.id" class="merchant-settings-row" @click="editAction(category)">
              <td><strong>{{ category.name }}</strong><small>Click to edit category details</small></td>
              <td>{{ category.parent ? category.parent.name : 'Top-level category' }}</td>
              <td class="text-right" @click.stop>
                <label class="merchant-toggle" :for="'is_active' + category.id">
                  <input :id="'is_active' + category.id" v-model="category.active" type="checkbox" @change="updateStatus(category.id, category.active)">
                  <span><i></i></span><strong>{{ category.active ? 'Active' : 'Hidden' }}</strong>
                </label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="categories.last_page > 1" class="merchant-settings-pagination">
        <pagination-display :data="categories" @pagination-change-page="fetchData"></pagination-display>
      </div>
    </div>

    <div v-else class="merchant-form-shell">
      <div class="card admin-card merchant-form-card">
        <div class="admin-card-header">
          <div>
            <span class="admin-eyebrow">Category details</span>
            <h2>{{ actionStatus === 'add' ? 'Add a category' : 'Edit category' }}</h2>
            <p>Use a parent category only when this belongs inside another group.</p>
          </div>
          <button v-if="actionStatus === 'edit'" type="button" class="btn merchant-danger-button" @click="onDelete">
            <i class="fas fa-trash-alt mr-2"></i>Delete
          </button>
        </div>
        <form class="merchant-settings-form" @submit.prevent="onSubmit">
          <div class="merchant-toggle-panel">
            <div><strong>Category availability</strong><small>Active categories can be used throughout your catalog.</small></div>
            <label class="merchant-toggle" for="active">
              <input id="active" v-model="field.active" type="checkbox"><span><i></i></span><strong>{{ field.active ? 'Active' : 'Hidden' }}</strong>
            </label>
          </div>
          <div class="form-group">
            <label for="name">Category name</label>
            <input id="name" v-model.trim="field.name" type="text" class="form-control" placeholder="e.g. Main dishes">
          </div>
          <div class="form-group">
            <label for="category">Parent category <small>(optional)</small></label>
            <select id="category" v-model="field.parent_category" class="form-control">
              <option value="">No parent category</option>
              <option v-for="category in parent_category" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </div>
          <div class="merchant-form-actions">
            <button type="button" class="btn admin-btn-secondary" @click="cancel">Cancel</button>
            <button type="submit" class="btn admin-btn-primary">{{ actionStatus === 'add' ? 'Add category' : 'Save changes' }}</button>
          </div>
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
              axios.get('/api/merchant/category/list?page='+page+'&api_token='+api_token).then(function (response) {
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
              axios.delete('/api/merchant/category/'+self.field.id+'/delete?api_token='+api_token, {}).then((response) => {
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
                     axios.post('/api/merchant/category/submit?api_token='+api_token, {
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

                   axios.put('/api/merchant/category/'+ this.field.id +'/submit?api_token='+api_token, {
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
