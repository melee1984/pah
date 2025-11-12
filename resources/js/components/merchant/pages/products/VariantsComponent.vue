<template>
<div>
	<div class="viewVariant">
		<div class="row">
			<div class="col-6">
				<a href="javascript:void(0)" v-on:click="addVariant" class="btn btn-pahatud btn-sm mb-2">
					<i class="fas fa-plus"></i> ADD
				</a>
			</div>
			<div class="col-6 text-right">
				<a href="javascript:void(0)" v-if="actionVariant=='view'"  v-on:click="manageVariant('manage')" class="btn btn-pahatud btn-sm mb-2 pull-right">
					<i class="fas fa-cog"></i>
				</a>
				<a href="javascript:void(0)" v-if="actionVariant=='manage'" v-on:click="manageVariant('view')" class="btn btn-pahatud btn-sm mb-2 pull-right">
					DONE
				</a>
			</div>
		</div>
		<table class="table table-striped text-nowrap">
	      <thead>
	        <tr>
	          <th>Active</th>
	          <th>Required</th>
	          <th>Multiple</th>
	          <th>Title</th>
	          <th>Branch/Location</th>
	          <th v-if="actionVariant=='manage'"></th>
	        </tr>
	      </thead>
	      <tbody>
	      	<tr v-if="!product.variants">
	      		<td colspan="4">No Record Found</td>
	      	</tr>
	        <tr v-for="variant in product.variants">
	          <td class="pull-right" width="10%">
	            <div class="custom-control custom-switch">
	              <input type="checkbox" class="custom-control-input"  :id="'active_'+variant.id" v-model="variant.active" v-on:click="updateStatus(variant.id, 1)">
	              <label class="custom-control-label" :for="'active_'+variant.id" ></label>
	            </div>
	          </td>
	           <td class="pull-right" width="10%">
	            <div class="custom-control custom-switch">
	              <input type="checkbox" class="custom-control-input"  :id="'required_'+variant.id" v-model="variant.is_required" v-on:click="updateStatus(variant.id, 2)">
	              <label class="custom-control-label"  :for="'required_'+variant.id" ></label>
	            </div>
	          </td>
	           <td class="pull-right" width="10%">
	            <div class="custom-control custom-switch">
	              <input type="checkbox" class="custom-control-input"  :id="'multiple_'+variant.id" v-model="variant.is_multiple" v-on:click="updateStatus(variant.id, 3)">
	              <label class="custom-control-label"  :for="'multiple_'+variant.id" ></label>
	            </div>
	          </td>
	          <td  width="35%"><a href="javascript:void(0)" v-on:click="viewVariantDetails(variant)">{{ variant.title }}</a></td>
	          <td width="35%">-</td>
	          <td v-if="actionVariant=='manage'" align="center" width="5%">
	          	<a href="javascript:void(0)" class="text-danger" v-on:click="deleteVariant(variant)">
	          		<i class="fas fa-trash"></i>
	          	</a>
	          </td>
	        </tr>
	      </tbody>
	    </table>
	</div>
    <variant-add :productId="product.id" @populatedVariant="fetchVariantRecord"/>
	<variant-details-view :product_header_id="selected_product_header_id" ref="variantDetails" />
</div>
</template>

<script>
	import AddVariantPop from '../../../merchant/pages/products/variants/AddComponent.vue';
	import AddingVariantDetailsProducts from '../../../merchant/pages/products/variants/AddVariantComponent.vue';

    export default {
    	components: {
	        'variant-add': AddVariantPop,
			'variant-details-view': AddingVariantDetailsProducts,
        },
        props: ['product'],
        mounted() {
            console.log('Variant Component Mounted.')
        },
        data() {
            return {
                field: {}, 
                errors: {},
                isSubmit: false,
                display: {},
                actionStatus: 'view',
                productFilterVal: '',
                locations: {},
                categories: {},
                fileImage: null,
                uploadStatus: 'Upload',
                actionVariant: 'view',
                selected_product_header_id: "",
                selected_product_variant: "",
                displayVariantDetails: false,
            }
        },
        mounted() {
            console.log('Mounted Merchant Variants Component')
            this.fetchRecord();
        },
        computed: {
          
        },
        methods: {
        	viewVariantDetails: function(variantHeader) {
        		this.selected_product_header_id = variantHeader.id;
        		this.selected_product_variant = variantHeader.product_details;
        		this.$refs.variantDetails.reloadVariantDetails(variantHeader.id); 
        		$('#modalAddVariantDetails').modal();
        	}, 
        	viewVariantDetailsSet: function(action) {
        		this.displayVariantDetails = action;
        	},
        	manageVariant: function (action) {
        		this.actionVariant = action;
        	},
        	addVariant: function() {
				$('#modalAddVariant').modal();
			},
			deleteVariant: function(variant) {
	            self = this;
	            var txt;
	            var r = confirm("Delete this record?");
	            if (r == true) {
	              axios.delete('/api/merchant/product/variant/'+variant.id+'/delete').then((response) => {
	                if (response.data.status) {
	                  toastr.success(response.data.message);
	                  this.fetchVariantRecord();
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
			fetchVariantRecord: function() {
                var self = this;
                axios.get('/api/merchant/product/'+this.product.id+'/variant/list').then(function (response) {
                    self.product = response.data.product;
                })
                .catch(function (error) {
                    console.log(error);
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
			       axios.put('/api/merchant/product/'+this.field.id+'/submit', {
			          title: this.field.title,
			          description: this.field.description,
			          short_description: this.field.short_description,
			          img: this.field.img,
			          active: this.field.active,
			          category_id: this.field.category_id,
			          price: this.field.price
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
			updateStatus: function(variant_id, option) {
				axios.post('/api/merchant/variant/'+variant_id+'/submit?api_token='+api_token, {
                  option: option,
                }).then((response) => {
                    if (response.data.status) {
						toastr.success(response.data.message);
						this.fetchVariantRecord();
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
			    $('#active').removeClass('is-invalid ding');
			    $('#title').removeClass('is-invalid ding');
			    $('#short_description').removeClass('is-invalid ding');
			    $('#category').removeClass('is-invalid ding');
			    $('#file').removeClass('is-invalid ding');
			    
			    if (!this.field.title) {
			      $('#title').addClass('is-invalid ding');
			      this.errors.push("Title is required.");
			    }
			    if (!this.field.short_description) {
			      $('#short_description').addClass('is-invalid ding');
			      this.errors.push("Short Description is required.");
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
			    this.field.short_description = "";
			    this.field.category_id = "";
			    this.field.price = "";

			    this.errors = {};
			    this.isSubmit = false;
			  },
        }
    }
</script>
