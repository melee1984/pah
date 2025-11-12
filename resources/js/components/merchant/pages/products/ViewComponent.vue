<template>

  <div>
    <div class="row">
      <div class="col-md-3"  v-if="actionStatus=='view'">
          <div class="row">
          <div class="col-12">
              <div class="card card-primary card-outline">
               
                <!-- /.card-header -->
                <div class="card-body table-responsive p-0">
                  <table class="table table-hover text-nowrap">
                    <thead>
                      <tr>
                        <th>Category</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr  v-for="category in categories">
                        <td v-on:click="filterCategory(category)">
                           <a href="javascript:void(0)" disabled>
                              {{ category.name }}
                           </a>
                        </td>
                      </tr>
                    </tbody>
                   
                  </table>
                </div>
                <!-- /.card-body -->
              </div>
            <!-- /.card -->
          </div>
        </div>
      </div>

      <div class="col-md-9" v-if="actionStatus=='view'">
        <div class="row">
          <div class="col-12">
              <div class="card card-primary card-outline">
                <div class="card-header">
                  <h3 class="card-title">
                     <div class="input-group input-group-sm" style="width: 150px;">
                      <input type="text" name="table_search" class="form-control float-right" placeholder="Product Title" v-model="productFilterVal" @keyup.enter="filterProduct">
                      <div class="input-group-append">
                        <button type="submit" class="btn btn-default" v-on:click="filterProduct"><i class="fas fa-search"></i></button>
                      </div>
                    </div>
                  </h3>
                  <div class="card-tools">
                    <a href="javascript:void(0)" class="btn btn-pahatud" v-on:click="actionProduct('add')"><i class="fas fa-plus"></i> ADD</a>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive p-0">
                  <table class="table table-hover text-nowrap">
                    <thead>
                      <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!productsTemp.length > 0">
                          <td colspan="4">No Record Found</td>
                        </tr>
                        <tr  v-for="product in filteredList">
                          <td width="10%">
                            <a :href="'/merchant/product/'+product.id+'/edit'" >
                              <img :src="product.imgname+'&s=thumb'" class="img-size-50">  
                            </a>
                          </td>
                          <td width="25%">{{ product.title }}</td>
                          <td width="50%">
                            {{ product.description }}
                          </td>
                          <td width="15%"><span class="tag tag-success">{{ product.price }} PHP</span></td>
                          <td class="pull-right">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" :id="'customSwitch'+product.id" v-model="product.active" v-on:click="updateStatus(product.id, product.active)">
                    <label class="custom-control-label" :for="'customSwitch'+product.id"></label>
                            </div>
                          </td>
                        </tr>
                    </tbody>
                      <tfoot v-if="products.last_page > 1">
                      <tr>
                        <td colspan="4">
                          <pagination-display :data="products" @pagination-change-page="fetchData"></pagination-display>
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
      </div>
      <!-- Adding Products --> 
      <div class="col-md-12" v-if="actionStatus=='add'">
         <product-add @actionProduct="actionProduct" @fetchData="fetchData" ></product-add>
      </div>
      <!-- End Adding Products --> 
  </div>

  </div>
</template>

<script>
    
    import AddingProducts from '../../../merchant/pages/products/AddComponent.vue';


     export default {
        components: {
           'product-add': AddingProducts,
        },
        props: ['categories'],
        data() {
          return {
              field: {
                restaurant_name: '',
                firstname:'',
                lastname:'',
                telephone: '',
                mobile: '',
                address: '',
                city: '',
                email: '',
                active: '',

              },
              products: '',
              productsTemp: [],
              errors: {},
              isSubmit: false,
              display: {},
              actionStatus: 'view',
              productFilterVal: '',
          }
        },
        mounted() {
          console.log('Mounted Merchant View Component')
          // / this.productsTemp = this.products;
          this.fetchData(1);
        },
        computed: {
          filteredList() {
            return this.productsTemp.filter(product => {
              return product.title.toLowerCase().includes(this.productFilterVal.toLowerCase())
            })
          }
        },
        methods: {
          fetchData: function(page = 1) {
              var self = this;
              axios.get('/api/merchant/product/list?page='+page).then(function (response) {
                  self.products = response.data.products;
                  self.productsTemp = self.products.data;
              })
              .catch(function (error) {
                  toastr.error(error.message);
              });
          }, 
          actionProduct: function(action) {
            this.actionStatus = action;
          },
          filterProduct: function() {
            console.log('test');
          },
          filterCategory: function(category) {
              var self = this;
              axios.post('/api/merchant/product/category/list', {
                category_id: category.id
              }).then(function (response) {
                  self.products = response.data.products;
                  self.productsTemp = self.products.data;
              })
              .catch(function (error) {
                  toastr.error(error.message);
              });
          },
          submitRecord: function() {
                this.isSubmit = true;
                if (this.validateForm()) {
                     axios.post('/api/merchant/register/submit', {
                        restaurant_name: this.field.restaurant_name,
                        firstname: this.field.firstname,
                        lastname: this.field.lastname,
                        telephone: this.field.telephone,
                        mobile: this.field.mobile,
                        address: this.field.address,
                        city: this.field.city,
                        email: this.field.email,
                        password: this.field.password

                      }).then((response) => {
                        if (response.data.status) {
                          toastr.success(response.data.message);
                          this.clearForm();
                        }
                        else {
                          toastr.info(response.data.message);
                        }
                      }).catch((errors) => {
                          toastr.error(errors);
                          this.isSubmit = false;
                      }); 
                }
                return false;
            },
             updateStatus: function(product, val) {
                axios.put('/api/merchant/product/'+product+'/status/submit', {
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

            validateForm: function () 
            {
                var self = this;
                var i;
                this.errors = [];
                // $('.is-invalid').removeClass('border-danger ding');
                $('#restaurant_name').removeClass('border-danger ding');
                $('#firstname').removeClass('border-danger ding');
                $('#lastname').removeClass('border-danger ding');
                $('#mobile').removeClass('border-danger ding');
                $('#telephone').removeClass('border-danger ding');
                $('#address').removeClass('border-danger ding');
                $('#city').removeClass('border-danger ding');
                $('#email').removeClass('border-danger ding');
                $('#password').removeClass('border-danger ding');
                
                 if (!this.field.restaurant_name) {
                  $('#firstname').addClass('border-danger ding');
                  this.errors.push("Firstname is required.");
                }
                if (!this.field.firstname) {
                  $('#firstname').addClass('border-danger ding');
                  this.errors.push("Firstname is required.");
                }
                if (!this.field.lastname) {
                  $('#lastname').addClass('border-danger ding');
                  this.errors.push("Lastname is required.");
                }
                if (!this.field.mobile) {
                  $('#mobile').addClass('border-danger ding');
                  this.errors.push("Mobile is required.");
                }
                if (!this.field.telephone) {
                  $('#telephone').addClass('border-danger ding');
                  this.errors.push("Telephone is required.");
                }
                if (!this.field.address) {
                  $('#address').addClass('border-danger ding');
                  this.errors.push("Address is required.");
                }
                if (!this.field.city) {
                  $('#address').addClass('border-danger ding');
                  this.errors.push("Address is required.");
                }
                if (!this.field.email) {
                  $('#email').addClass('border-danger ding');
                  this.errors.push("Email Address is required.");
                }
                if (!this.field.password) {
                  $('#password').addClass('border-danger ding');
                  this.errors.push("Password is required.");
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
                this.field.restaurant_name = "";
                this.field.firstname = "";
                this.field.lastname = "";
                this.field.mobile = "";
                this.field.telephone = "";
                this.field.address = "";
                this.field.city = "";
                this.field.email = "";
                this.field.password = "";
                
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>
