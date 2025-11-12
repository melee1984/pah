<template>
  <!-- Modal HTML -->
  <div id="varianceModal" class="modal fade">
    <div class="modal-dialog modal-dialog modal-confirm modal-lg">
      <div class="modal-content">
        <div class="modal-header justify-content-center" v-bind:class="display.type">
        </div>
        <div class="modal-body p-0">
          <div class="icon-box">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          </div>
           <div class="img-banner" :style="'background: url('+ productInfo.imgname +'&s=banner)'"></div>
           <div class="res-info">
               <h1>{{ productInfo.title }} <span class="default" v-if="productInfo.markdown_price">{{ productInfo.markdown_price }}</span><span class="default" v-if="!productInfo.markdown_price">{{ productInfo.price }}</span></h1> 
           </div>
           <hr class="p-0 m-0" />
           <form id="formVariance">
           <div class="variance">
              <ul>
                <li v-for="variant in productInfo.variants" class="mb-3">
                  <div class="variant-title">
                    {{ variant.title }}<span v-if='variant.is_required'>Required 1</span>
                  </div>
                  <span class="selection-option" v-if='variant.is_required'>Select 1</span>
                  <div class="row mt-2 mb-2" v-for="variantDetail in variant.product_details">
                    <div class="col-6 pl-3" v-if="!variant.is_multiple">
                     <label class="radionContainer">
                        <input type="radio" :name="'variant_'+variant.id" :value="''+variant.id + '|' + variantDetail.id" v-if='variant.is_required' v-on:change="validateFields">
                        <span class="checkmark"></span>
                          {{ variantDetail.title }}
                      </label>
                    </div>
                    <div class="col-6 pl-3" v-if="variant.is_multiple">
                        <label class="radionContainer">
                        <input type="checkbox" :name="'variant_'+variant.id+variantDetail.id" :value="''+variant.id + '|' + variantDetail.id">
                        <span class="checkmark"></span>
                          {{ variantDetail.title }}
                      </label> 
                    </div>
                    <div class="col-6 text-right pr-4">
                      <div class="opt-label" v-if="variantDetail.price!=''">+ PHP {{ variantDetail.price }}</div>
                    </div>

                 </div>
                </li>
                <li>
                  <div class="variant-title">
                    Special instructions
                  </div>
                  <div class="row " >
                    <div class="col-12" >
                      <div class="form-group">
                      <p>Any specific preferences? Let the restaurant know.</p>
                      <textarea class="form-control" name="preferences" v-model="fields.instruction"></textarea>
                      </div>
                    </div>
                 </div>

                 <div class="row" >
                    <div class="col-12">
                      <p>If this product is not available?</p>
                    <div class="form-group">
                      <select name="" id="" class="form-control" v-model="fields.is_not_available">
                        <option value="0" selected>Remove it from my order</option>
                        <option value="1">Cancel my Order</option>
                        <option value="2">Call my number</option>
                      </select>
                    </div>
                    </div>
                 </div>

                </li>
              </ul>
           </div>
           </form>
        </div>

        <div class="modal-footer">
            <button class="btn btn-block btn-pahatud" v-if="is_enabled" v-on:click="addCart(product)"><span>{{ stringAddToCart }}</span> </button>  
            <button class="btn btn-block btn-pahatud disabled"  v-if="!is_enabled"><span>Add Cart</span></button>            
        </div>
      </div>
    </div>
  </div>     

</template>

<script>
    export default {
      props : ['display', 'productInfo', 'addingthisformerchant'],
      data() {
        return {
          fields: {
            instruction: '',
            is_not_available: 0,
          },
          message : '',
          title : '',
          type : '',
          product: {},
          is_enabled: false,
          stringAddToCart: "Add Cart",
        }
      },
      mounted() {

      },
      methods: {
        reloadVariantProductInfo: function(product) {
             this.clear();
            // diri nako display ang number of required fields iloop tapos dapat ma enabled na ang button if ever.
            this.product = product;
            // pag reload should revalidate if the button is active 
         
            this.validateFields();
        },  
        addCart: function(product) {
            if (!this.addingthisformerchant) {
                  this.$confirm(
                  'You are adding item in a different Merchant/Restaurant. Your current cart will be set to empty. Do you want us to proceed?',
                   '',
                   'warning',
                   3000).then((response) => {
                      if (response) {
                        this.emptyAddCart(product);
                        Event.$emit('updateAddingForMerchant');
                      }
                  });
              }
              else {
                // if not empty and not equal just proceed with adding the cart 
                this.simpleAddCart(product);  
              }
        },
        simpleAddCart: function (product) 
        {
          let formData = new FormData();
           $('#formVariance input').each(function(index) {  
                var input = $(this);
                if (this.checked) {
                  formData.append(input.attr('name'), input.val())
                }
              }
          );
          formData.append('item_id', product.id)
          formData.append('instruction', this.fields.instruction)
          formData.append('is_not_available', this.fields.is_not_available)
          formData.append('variant', true)
          this.stringAddToCart = "Adding, please wait..."
          axios.post('/api/item/add-cart', formData).then((response) => {
            if (response.data.status) {
              $('#varianceModal').modal('toggle'); 
              Event.$emit('reloadSummary');
            }
            else {
              toastr.info(response.data.message);
            }
            this.stringAddToCart = "Add to Cart";
          }).catch((errors) => {
              toastr.error(errors);
          }); 
        },
        emptyAddCart: function (product) 
        {
            
          this.stringAddToCart = "Adding, please wait..."

          let formData = new FormData();
          $('#formVariance input').each(function(index) {  
                var input = $(this);
                if (this.checked) {
                  formData.append(input.attr('name'), input.val())
                }
              }
          );
          formData.append('item_id', product.id)
          formData.append('instruction', this.fields.instruction)
          formData.append('is_not_available', this.fields.is_not_available)
          formData.append('variant', true)
          axios.post('/api/item/new/add-cart', formData).then((response) => {
            if (response.data.status) {
              $('#varianceModal').modal('toggle'); 
              Event.$emit('reloadSummary');
            }
            else {
              toastr.info(response.data.message);
            }
            
            this.stringAddToCart = "Add to Cart";

          }).catch((errors) => {
              toastr.error(errors);
          }); 

        },

        clear: function() {

          $('#formVariance input').each(function(index) {  
                var input = $(this);
                if (this.checked) {
                  $(this).prop('checked', false);
                }
              }
          );

        },
        validateFields: function() {

          var productVariants = this.product.variants;
          var enabledNow = true;
          for(var key in productVariants) {
            // console.log(productVariants[key]);
            var is_required = productVariants[key]['is_required'];
            var variant_id = productVariants[key]['id'];
            // Check if the Item need to validate to enable the add cart button 
            if (is_required) {
              for(var key in productVariants[key]['product_details']) {
                if (!$('input[name=variant_'+ variant_id+']:checked').is(':checked')) {
                  enabledNow = false;
                  break;
                }
              }
              if (!enabledNow) break;
            }
          }
          enabledNow==true?this.is_enabled = true:this.is_enabled = false;
        },

      }
    }
</script>

