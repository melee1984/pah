<template>
    <div>
    	 <div class="row">
            <div class="col-md-6 col-lg-6 col-xs-12 col-sm-12">
              <div class="card card-primary card-outline">
                <div class="card-header">
                  <h3 class="card-title">
                    ADDING PRODUCT
                  </h3>
                  <div class="card-tools">
                    <a href="javascript:void(0)" class="btn btn-secondary" v-on:click="actionSend('view')"><i class="fas window-close"></i> CLOSE</a>
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
                              <!-- text input -->
                              <div class="form-group">
                                <label>Title</label>
                                <input type="text" class="form-control" placeholder="Enter Title" id="title" v-model="field.title">
                              </div>
                            </div>
                          </div>
                           <div class="row">
                            <div class="col-sm-6">
                              <div class="form-group">
                                <label>Price</label>
                                <input type="number" class="form-control" placeholder="Enter Price" id="price" v-model="field.price" @change="updatePriceComm()" step="any">

                              </div>
                            </div>
                             <div class="col-sm-6">
                              <div class="form-group">
                                <label>Comm {{ merchant.percentage }}%</label>
                                <input type="number" class="form-control" disabled="" id="price_comm" v-model="field.price_comm">
                              </div>
                            </div>
                          </div>
                            <div class="row">
                            <div class="col-sm-6">
                              <div class="form-group">
                                <label>Markdown Price</label>
                                <input type="number" class="form-control" placeholder="Enter Markdown Price" id="markdown_price" v-model="field.markdown_price" @change="updatePriceMarkdownComm()">

                              </div>
                            </div>
                             <div class="col-sm-6">
                              <div class="form-group">
                                <label>Comm {{ merchant.percentage }}%</label>
                                <input type="number" class="form-control" placeholder="" id="markdown_price_comm" disabled="" v-model="field.markdown_price_comm">

                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" rows="3" id="description" placeholder="Short Description" v-model="field.description"></textarea>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="form-group">
                                <label>Category</label>
                                <select class="form-control" id="category" v-model="field.category_id">
                                  <option value="">Select Category</option>
                                  <option v-for="category in categories" :value=category.id>
                                     {{ category.name }}
                                   </option>
                              </select>
                              </div>
                            </div>
                          </div>
                       
                         

                          <br>
                          <div class="card-footer">
                            <button type="submit" class="btn btn-pahatud float-left">Submit</button>
                            <a href="javascript:void(0)" class="btn btn-default float-right" v-on:click="actionSend('view')"><i class="fas window-close"></i> Cancel</a>
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
</template>

<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return {
                field: {
                  title: '',
                  description:'',
                  short_description:'',
                  file: '',
                  category_id: '',
                  price: '',
                  active: true,
                  file: null,
                  markdown_price: '',
                  price_comm: '',
                  markdown_price_comm: '',
                   user: {
                    merchant: {}
                  }
                },
                productsTemp: [],
                errors: {},
                isSubmit: false,
                display: {},
                actionStatus: 'view',
                productFilterVal: '',
                categories: {},
                locations: {},
                merchant: {},
                percentage: .15,
            }
        },
        mounted() {
            // toastr.info("Successfully added new category");
            console.log('Mounted Merchant View Component')
            this.productsTemp = this.products;

            this.fetchRecord();
           
            if(this.merchant.percentage == 15) {
              this.percentage  = .15;
            }
            else if(this.merchant.percentage == 10) {
              this.percentage  = .10;
            }

        },
        computed: {
          filteredList() {
            return this.productsTemp.filter(product => {
              return product.title.toLowerCase().includes(this.productFilterVal.toLowerCase())
            })
          }

        },
        methods: {
        onFileSelected: function(e) {
          this.field.file = e.target.files[0];
        },
        actionSend: function(action) {
          this.$emit('actionProduct', action);
        },
        fetchRecord: function() {
            var self = this;
            axios.get('/api/merchant/product/list').then(function (response) {
                self.categories = response.data.categories;
                self.merchant = response.data.merchant;
            })
            .catch(function (error) {
                console.log(error);
            });
        },
        onSubmit: function() {
              this.isSubmit = true;
              if (this.validateForm()) {
                 axios.post('/api/merchant/product/submit', {
                    title: this.field.title,
                    description: this.field.description,
                    short_description: this.field.short_description,
                    img: this.field.img,
                    active: this.field.active,
                    category_id: this.field.category_id,
                    markdown_price: this.field.markdown_price,
                    markdown_price_comm: this.field.markdown_price_comm,
                    price_comm: this.field.price_comm,
                    price: this.field.price
                   
                  }).then((response) => {
                    if (response.data.status) {
                      toastr.success(response.data.message);
                      this.clearForm();
                      this.$emit('fetchData');
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
                      this.isSubmit = false;
                  }); 
              }
          },
          validateForm: function () 
          {
              var self = this;
              var i;
              this.errors = [];
              $('#active').removeClass('is-invalid ding');
              $('#title').removeClass('is-invalid ding');
              $('#short_description').removeClass('is-invalid ding');
              $('#price').removeClass('is-invalid ding');
              $('#location').removeClass('is-invalid ding');
              $('#file').removeClass('is-invalid ding');
              
              if (!this.field.title) {
                $('#title').addClass('is-invalid ding');
                this.errors.push("Title is required.");
              }
               if (!this.field.price) {
                $('#price').addClass('is-invalid ding');
                this.errors.push("Starting Price is required.");
              }
              if (!this.field.description) {
                $('#description').addClass('is-invalid ding');
                this.errors.push("Description is required.");
              }
              if (!this.field.category_id) {
                $('#category').addClass('is-invalid ding');
                this.errors.push("Category is required.");
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
              this.field.title = "";
              this.field.description = "";
              this.field.category_id = "";
              this.field.price = "";
              this.field.markdown_price = "";
     
              this.errors = {};
              this.actionSend('view');
              this.isSubmit = false;
            },
            updatePriceComm: function(event) {
              this.field.price_comm = this.field.price * this.percentage;
            },
            updatePriceMarkdownComm: function(event) {
              this.field.markdown_price_comm = this.field.markdown_price * this.percentage;
            }
        }
    }
</script>
