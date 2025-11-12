<template>
<div class="card checkout-box">
    <div class="card-body">
          <div class="merchant" v-if="cart.partner">
          	<div class="row">
          		<div class="col-md-3 pl-1 pr-1" >
          			<a :href="'restaurant/'+cart.partner.slug" :title="cart.partner.restaurant_name">
	          			<img :src="cart.partner.img" alt="..." class="rounded-square " :alt="cart.partner.restaurant_name">		
	          		</a>
          		</div>
          		<div class="col-md-9 pl-0 ml-0">
					       Your order from <a :href="'restaurant/'+cart.partner.slug">{{ cart.partner.restaurant_name }}</a>
                 <div class="text-muted checkout-address" v-if="cart.partnerlocation" >{{ cart.partnerlocation.address_1 }}</div>
          		</div>
          	</div>
          </div>
        <div class="list" v-for="product in cart.details">
            <div class="row">
              <div class="col-md-2 col-lg-2 col-sm-2 text-center p-0 m-0">
                <span><strong>{{ product.qty }}</strong> x</span>
              </div>
              <div class="col-md-6 col-lg-6 col-sm-6">
                <span class="title">{{ product.item.title }}</span><br>

                <p class="p-0 m-0 variant" v-for="variant in product.variance_content">+ {{ variant.title }}</p>
              </div>
              <div class="col-md-4 col-lg-4 col-sm-4 text-right">
                <p class="price">PHP {{ product.item.price  }}</p>
              </div>
            </div>
        </div>
        <hr>
    		<div class="total-summary">
    			<div class="row">
    				<div class="col-xs-7 col-sm-7 col-md-7 col-lg-7 text-left">
    				  <span>Subtotal</span>
    				</div>
    				<div class="col-xs-5 col-sm-5 col-md-5 col-lg-5 text-right">
    				  <span>{{ summary.sub_total }}</span>
    				</div>
    				 <div class="col-xs-7 col-sm-7 col-md-7 col-lg-7 text-left">
    				  <span>Delivery Fee</span>
    				</div>
    				<div class="col-xs-5 col-sm-5 col-md-5 col-lg-5 text-right">
    				  <span>{{ summary.delivery_fee }}</span>
    				</div>
    			   <div class="col-xs-7 col-sm-7 col-md-7 col-lg-7 text-left">
    			    <span>Discount</span>
    			  </div>
    			  <div class="col-xs-5 col-sm-5 col-md-5 col-lg-5 text-right">
    			    <span>{{ summary.discount }}</span>
    			  </div>
    			</div>
    		</div>
    		<div class="row total mb-4 mt-3">
    			 <div class="col-xs-7 col-sm-7 col-md-7 col-lg-7 text-left">
    			  <span>Total (incl. VAT)</span>
    			</div>
    			<div class="col-xs-5 col-sm-5 col-md-5 col-lg-5 text-right">
    			  <span>{{ summary.total }}</span>
    			</div>
    		</div>
         <hr>
    </div>
   
      <p class="text-center">
        <label>Do you have coupon code? </label>
        <br>
        <a href="javascript:void(0)" v-on:click="applyCoupon" class="text-danger">Enter coupon code</a>
      </p>
    <br>

    <!-- Modal -->
    <div class="modal fade" id="coupon" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Enter Coupon Code</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
                <div class="col-md-12">
                    <div class="input-group">
                      <input type="text" class="form-control" id="couponCode"  v-model="coupon" aria-describedby="couponHelp" placeholder="Please enter your coupon code"> 
                        <div class="input-group-prepend">
                          <span class="input-group-text" id="inputGroupPrepend">
                            <a href="javascript:void(0)" v-on:click="couponSubmit">Submit</a>
                          </span>
                        </div>
                    </div>
                     <small id="couponHelp" class="form-text text-muted"></small>
                </div>
                <br>
          </div>
            <div class="modal-footer">
        
                </div>
        </div>
      </div>
    </div>


</div>
</template>


<script>
    export default {
		props : ['summary', 'cart'],
		data() {
          return {
            coupon: '',
          }
        },
        mounted() {
        	console.log(MAINURL);
        },
        methods: {
          applyCoupon: function() {
              $('#coupon').modal();
          },
          couponSubmit: function() {
             axios.post('/api/checkout/coupon/submit?api_token='+api_token, {
              coupon: this.coupon,
             }).then((response) => {
              if (response.data.status) {
                $('#coupon').modal('toggle');
                Event.$emit('reloadCheckout');
              }
              else {
                $('#coupon').modal('toggle');
                toastr.error(response.data.message);
              }
            }).catch((errors) => {
              toastr.error(errors);
            }); 
          },
        }
    }
</script>