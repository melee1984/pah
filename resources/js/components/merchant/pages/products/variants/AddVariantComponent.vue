<template>
  <div class="modal fade" id="modalAddVariantDetails" role="dialog">
    <div class="modal-dialog modal-xl">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4><span class="glyphicon glyphicon-lock"></span> Manage Variant Details</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" style="padding:40px 50px;">
        <div class="alert alert-success" v-if="actionSuccess">
            {{ display.message }}
        </div>
        <div class="row">
          <div class="col-8">
            <div class="row">
              <div class="col-6"></div>
              <div class="col-6 text-right">
                <a href="javascript:void(0)" v-if="actionVariant=='view'"  v-on:click="manageVariant('manage')" class="btn btn-pahatud btn-sm mb-2 pull-right">
                  <i class="fas fa-cog"></i>
                </a>
                <a href="javascript:void(0)" v-if="actionVariant!='view'" v-on:click="manageVariant('view')" class="btn btn-pahatud btn-sm mb-2 pull-right">
                  DONE
                </a>
              </div>
            </div>
            <table class="table table-striped text-nowrap">
              <thead>
                <tr>
                   <th v-if="actionVariant!='view'"></th>
                  <th>Title</th>
                  <th>Price</th>
                  <th>15%</th
                  <th>Select By Default</th> 
                  <th v-if="actionVariant!='view'"></th>
                </tr>
              </thead>
              <tbody>
                 <tr v-if="!details.length > 0">
                  <td colspan="5">No Record Found</td>
                </tr>
                <tr v-for="detail in details" :id="'list_'+detail.id" class="connectedSortable">
                    <td v-if="actionVariant!='view'" align="center" width="5%">
                      <a href="javascript:void(0)" class="text-danger" v-on:click="editVariantDetails(detail)">
                        <i class="fas fa-pen"></i>
                      </a>
                    </td>
                      <td>
                        {{ detail.title }}
                      </td>
                      <td>+ &#8369; {{ detail.price }}</td>
                       <td>{{ detail.price_comm }}</td>
                      <td class="pull-right">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input" v-model="detail.active">
                          <label class="custom-control-label" for="active"></label>
                        </div>
                      </td> 
                      <td v-if="actionVariant!='view'" align="center" width="5%">
                      <a href="javascript:void(0)" class="text-danger" v-on:click="deleteVariantDetails(detail)">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="col-4">

            <!-- Form --> 
              <div class="card card-pahatud">
                <div class="card-header">
                  <h3 class="card-title">New Variant Details</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <!-- Form --> 
                      <form method="post" v-on:submit.prevent="submitRecord" v-if="!actionSuccess">
                         <div class="form-group">
                          <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" :id="'details_'+field.active" v-model="field.active">
                            <label class="custom-control-label" :for="'details_'+field.active"> Selected by Default</label>
                          </div>
                        </div>
                        <div class="form-group">
                          <input type="text" class="form-control" id="variant_title" name="variant_title" placeholder="Enter Title *" required v-model="field.title">
                        </div>
                        <div class="form-group">
                          <input type="number" class="form-control" id="variant_price" step="any" name="variant_price" placeholder="Enter Add up Price" required v-model="field.price" @change="updatePriceComm()">
                        </div>
                        <div class="form-group">
                          <input type="text" class="form-control" id="variant_price_comm" name="variant_price" placeholder="" disabled="" v-model="field.price_comm">
                        </div>
                        <button type="submit" class="btn btn-pahatud btn-block">
                            <span v-if="!isSubmit">Submit</span> <span v-if="isSubmit">Please wait...</span>
                        </button> 
                    </form> 
                    <!-- /Form -->                
              </div>
              <!-- /.card-body -->
            </div>
            <!-- Form --> 
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-default" data-dismiss="modal" v-if="!actionSuccess">Cancel</button>
      </div>
    </div>
    </div>
  </div>
</template>
<script>
    export default {
      props: ['product_header_id'],
      data() {
            return {
                field: {
                  active: true,
                  title:'',
                  price: '',
                  price_comm: '',
                },
                errors: {},
                isSubmit: false,
                display: {},
                actionSuccess: false,
                details: {},
                actionVariant: 'view',
            }
        },
        mounted() {
          console.log('Component Add Variant Controller.');
        },
        created() {
          console.log(1);
        },
        computed: {
        },
        methods: {
          updatePriceComm: function(event) {
              this.field.price_comm = this.field.price * .15;
          },
          manageVariant: function(action) {
            this.actionVariant = action;
          },
          reloadVariantDetails: function(product_header_id) {
              console.log('reloading');
              var self = this;
              axios.get('/api/merchant/product/variant/'+product_header_id+'/details').then(function (response) {
                  self.details = response.data.product_details;
              }).catch(function (error) {
                  console.log(error);
              });
          },
          submitRecord: function() {

                this.isSubmit = true;
                 var self = this;
                if (this.validateForm()) {
                     if (this.actionVariant != "edit") {
                        axios.post('/api/merchant/product/variant/'+self.product_header_id+'/detail/submit', {
                          title: this.field.title,
                          price: this.field.price,
                          active: this.field.active,
                        })
                        .then((response) => {
                          if (response.data.status) 
                          {
                            toastr.success(response.data.message);
                            this.reloadVariantDetails(self.product_header_id);
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
                     else if (this.actionVariant == "edit") {

                        axios.put('/api/merchant/product/variant/'+self.product_header_id+'/detail/submit', {
                          id: this.field.id,
                          title: this.field.title,
                          price: this.field.price,
                          active: this.field.active,
                        })
                        .then((response) => {
                          if (response.data.status) 
                          {
                            toastr.success(response.data.message);
                            this.reloadVariantDetails(self.product_header_id);
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
                }
            },
            editVariantDetails: function(detail) {

               this.manageVariant('edit');
               this.field.active = detail.active;
               this.field.title = detail.title;
               this.field.price = detail.price;
               this.field.id = detail.id;
            },
            deleteVariantDetails: function(detail) {
              var self = this;
              var txt;
              var r = confirm("Delete this record?");
              if (r == true) {
                axios.delete('/api/merchant/product/variant/detail/'+detail.id+'/delete').then(function (response) {
                  if (response.data.status) {
                      toastr.success(response.data.message);
                      self.reloadVariantDetails(detail.product_header_id);
                  }
                  else   {
                    toastr.info(response.data.message);
                  }
                }).catch(function (errors) {
                    console.log(errors);
                });
              }
            },
            validateForm: function () {

                var self = this;
                var i;
                this.errors = [];
                $('#variant_title').removeClass('border-danger ding');
               
                if (!this.field.title) {
                  $('#variant_title').addClass('is-invalid ding');
                  this.errors.push("Title is required.");
                }
                if (!this.field.price) {
                  $('#variant_price').addClass('is-invalid ding');
                  this.errors.push("Price is required.");
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
              this.field.price = "";
              this.field.price_comm = "";
              this.manageVariant('view');
              this.errors = {};
              this.isSubmit = false;
            },
        }
    }
</script>


