<template>
    <div>
    	 <div class="row">
            <div class="col-md-4 col-lg-4 col-xs-12 col-sm-12">
              <div class="card card-primary card-outline">
                <div class="card-header">
                  <h3 class="card-title">
                    EDITING PRODUCT
                  </h3>
                  <div class="card-tools">
                    <a href="/merchant/products" class="btn btn-secondary"><i class="fas window-close"></i> CLOSE</a>
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
                                <input type="text" class="form-control" placeholder="Enter Starting Price" id="price" v-model="field.price" @change="updatePriceComm()">
                              </div>
                            </div>
                             <div class="col-sm-6">
                              <div class="form-group">
                                <label>{{ field.user.merchant.percentage }}%</label>
                                <input type="text" class="form-control" placeholder="" id="price" v-model="field.price_comm" disabled>
                              </div>
                            </div>
                          </div>
                           <div class="row">
                            <div class="col-sm-6">
                              <div class="form-group">
                                <label>Markdown Price</label>
                                <input type="text" class="form-control" placeholder="Enter Markdown Price" id="markdown_price" v-model="field.markdown_price" @change="updatePriceMarkdownComm()">
                              </div>
                            </div>
                              <div class="col-sm-6">
                              <div class="form-group">
                                <label>{{ field.user.merchant.percentage }}%</label>
                                <input type="text" class="form-control" placeholder="" id="markdown_price_comm" v-model="field.markdown_price_comm" disabled="">
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" rows="3" id="description" placeholder="Enter Description" v-model="field.description"></textarea>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-sm-12">
                              <div class="form-group">
                                <label>Category</label>
                                <select class="form-control" id="category" v-model="field.category_id">
                                   <option value="0">Select Category</option>
                                   <option v-for="category in categories" :value=category.id>
                                     {{ category.name }}
                                   </option>
                              </select>
                              </div>
                            </div>
                          </div>
                         
                          <br>
                          <div class="card-footer">
                            <button type="submit" class="btn btn-pahatud float-left">Update</button>
                            <a href="/merchant/products" class="btn btn-default float-right"><i class="fas window-close"></i> Cancel</a>
                          </div>

                        </form>
                      </div>
                  </div> 
                </div>
                <!-- /.card-body -->
              </div>
            </div>
            <div class="col-md-8">
                <!-- Other Side --> 
                <div class="card card-primary card-outline">
                <div class="card-header">
                  <h3 class="card-title">
                    Product Properties
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
                                  <ul class="nav nav-pills ml-auto p-2">
                                    <li class="nav-item"><a class="nav-link active" href="#addons" data-toggle="tab">Variation</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#img" data-toggle="tab">Photo</a></li>
                                    <li class="nav-item"><a class="nav-link " href="#set" data-toggle="tab">Settings</a></li>
                                  </ul>
                                </div><!-- /.card-header -->
                                <div class="card-body">
                                  <div class="tab-content">
                                    <div class="tab-pane active" id="addons">
                                      <product-variants-view 
                                        :product="product"></product-variants-view>
                                    </div>
                                    <!-- /.tab-pane -->
                                    <div class="tab-pane" id="img">
                                        <div class="form-group">
                                          <div class="col-md-12">
                                              <img :src="field.imgname+'&s=banner'" class="img-fluid" width="400px">  
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

                                    <div class="tab-pane" id="set">
                                      <label><a href="javascript:void(0)" v-on:click="onDelete" class="btn btn-danger">Delete Product</a> This will be remove from your product database</label>
                                    </div>
                                    
                                    <!-- /.tab-pane -->
                                    <div class="tab-pane" id="tab_3">
                                      Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                      Lorem Ipsum has been the industry's standard dummy text ever since the 1500s,
                                      when an unknown printer took a galley of type and scrambled it to make a type specimen book.
                                      It has survived not only five centuries, but also the leap into electronic typesetting,
                                      remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset
                                      sheets containing Lorem Ipsum passages, and more recently with desktop publishing software
                                      like Aldus PageMaker including versions of Lorem Ipsum.
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
  
    import AddingProducts from '../../../merchant/pages/products/VariantsComponent.vue';

    export default {
        components: {
           'product-variants-view': AddingProducts,
        },
        props: ['product', 'selectedcategoryid', 'active'],
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
                  active: false,
                  file: null,
                  markdown_price: '',
                  price_comm: '',
                  markdown_price_comm: '',
                  user: {
                    merchant: {}
                  }

                }, 
                errors: {},
                isSubmit: false,
                display: {},
                actionStatus: 'view',
                productFilterVal: '',
                locations: {},
                categories: {},
                fileImage: null,
                uploadStatus: 'Upload',
                percentage: .15,
            }
        },
        mounted() {
            console.log('Mounted Merchant View Component')
            this.fetchRecord();
            this.field = this.product;

            if(this.field.user.merchant.percentage == 15) {
              this.percentage  = .15;
            }
            else if(this.field.user.merchant.percentage == 10) {
              this.percentage  = .10;
            }
            
        },
        computed: {
          
        },
        methods: {
          onFileSelected: function(e) {
            this.fileImage = e.target.files[0];
          },
          onDelete: function() {
              var txt;
              var r = confirm("Delete this record?");
              if (r == true) {
                axios.delete('/api/merchant/product/'+this.field.id+'/delete').then(function (response) {
                  if (response.data.status) {
                    toastr.success(response.data.message);
                      setTimeout(function () {
                        window.location.href = MainURL + "/merchant/products";
                      }, 5);
                  }
                  else   {
                    toastr.info(response.data.message);
                  }
                }).catch(function (error) {
                    toastr.error(response.data.message);
                });
              }
          },
          onUploadImage: function() {
              var self = this;
              const fd = new FormData();
              fd.append('file', this.fileImage, this.fileImage.name);

              self.uploadStatus = "Please wait...";
              axios.post('/api/merchant/product/'+this.field.id+'/upload/submit', fd, {
                onUploadImage: uploadEvent => {
                  // console.log('Upload Progress:' );
                  this.uploadStatus = "Upload" + Math.round(uploadEvent.loaded / uploadEvent.total * 100) + "%"
                }
              }).then(function (response) {
                if (response.data.status) {
                  toastr.success(response.data.message);
                  self.field.imgname = response.data.img;
                  self.uploadStatus = "Upload";
                }
                else   {
                  toastr.info(response.data.message);
                  self.uploadStatus = "Upload";
                }
              }).catch(function (error) {
                toastr.error(error.response.data.errors['file'][0]);
                this.uploadStatus = "Upload";
              });
          },
          fetchRecord: function() {
              var self = this;
              axios.get('/api/merchant/product/requirement').then(function (response) {
                  self.categories = response.data.categories;
              })
              .catch(function (error) {
                  console.log(error);
              });
          },
          
          onSubmit: function() {
              this.isSubmit = true;
              if (this.validateForm()) {
                   axios.put('/api/merchant/product/'+this.product.id+'/submit', {
                      title: this.field.title,
                      description: this.field.description,
                      short_description: this.field.short_description,
                      img: this.field.img,
                      active: this.field.active,
                      category_id: this.field.category_id,
                      price: this.field.price,
                      markdown_price: this.field.markdown_price,
                      price_comme: this.field.price_comm,
                      markdown_price_comm: this.field.markdown_price_comm,
                    }).then((response) => {
                      if (response.data.status) {
                          toastr.success(response.data.message);
                           setTimeout(function () {
                              window.location.href = MainURL + "/merchant/products";
                            }, 3);

                          this.clearForm();
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
                // $('.is-invalid').removeClass('border-danger ding');
                $('#active').removeClass('is-invalid ding');
                $('#title').removeClass('is-invalid ding');
                $('#description').removeClass('is-invalid ding');
                $('#category').removeClass('is-invalid ding');
                $('#file').removeClass('is-invalid ding');
                if (!this.field.title) {
                  $('#title').addClass('is-invalid ding');
                  this.errors.push("Title is required.");
                }
                if (!this.field.description) {
                  $('#description').addClass('is-invalid ding');
                  this.errors.push("Description is required.");
                }
                if (!this.field.category_id) {
                  $('#category').addClass('is-invalid ding');
                  this.errors.push("Category is required.");
                }
                if (!this.field.price) {
                  $('#price').addClass('is-invalid ding');
                  this.errors.push("Starting Price is required.");
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
                this.field.price_comm = "";
                this.field.markdown_price = "";
                this.field.markdown_price_comm = "";
                this.errors = {};
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
