<template>
  <section>
    <div class="container mb-5" id="checkout">
        <div class="text-center mt-3 delivery" v-if="cart">
           <span v-if="cart.duration!=null">Delivery - {{ cart.duration }} mins </span>
        </div>
        <h3 class="restaurant_name" v-if="cart">
          <div v-if="cart.partner">
            <a :href="cart.partner.slug">
              Your Cart - {{ cart.partner.restaurant_name }}
            </a>
          </div>
          <p v-if="cart.partnerlocation">{{ cart.partnerlocation.address_1 }}</p>
        </h3>
        <div class="cart-item text-center">
          <p v-if="!items.length > 0">Hungry huh, add items to your Cart.</p>
          <div class="product-information">
               <div class="item-list" v-for="item in items">
                    <div class="row product">
                       <div class="col-xs-7 col-md-7 col-lg-7 text-left name">
                        <span>{{ item.item.title }}</span>
                      </div>
                      <div class="col-xs-5 col-md-5 col-lg-5 text-right">
                        <span>PHP {{ item.item.price   }}  </span>
                      </div>
                   </div>
                   <div class="row">
                      <div class="col-xs-7 col-md-7 col-lg-7 text-left">
                        <p class="p-0 m-0" v-for="variant in item.variance_content">+ {{ variant.title }}</p>
                      </div>
                      <div class="col-xs-5 col-md-5 col-lg-5 text-right">
                          <div class="controls">
                              <span class="min">
                                <a href="javascript:void(0)" v-on:click="updateCartItemDelete(item)" v-if="item.qty == 1"><i class="icofont-trash"></i></a>
                                <a href="javascript:void(0)" v-on:click="updateCartItemMinus(item)" v-if="item.qty > 1"><i class="icofont-minus"></i></a>
                              </span>
                              <span class="qty">{{ item.qty }}</span>
                              <span class="plus">
                                  <a href="javascript:void(0)" v-on:click="updateCartItemAdd(item)"><i class="icofont-plus"></i></a>
                              </span>
                          </div>
                      </div>
                   </div>
               </div>
          </div>
        </div>

         <div v-if="refresh" class="text-center m-b-4">
          <!-- <img src="/images/ajax-loader.gif" alt=""> -->
        </div>

        <div class="total-summary" v-if="!refresh">
            <div class="row">
              <div class="col-xs-7 col-md-7 col-lg-7 text-left">
                <span>Subtotal</span>
              </div>
              <div class="col-xs-5 col-md-5 col-lg-5 text-right">
                <span>{{ summary.sub_total }}</span>
              </div>
               <div class="col-xs-7 col-md-7 col-lg-7 text-left">
                <span>Delivery Fee</span>
              </div>
              <div class="col-xs-5 col-md-5 col-lg-5 text-right">
                <span>{{ summary.delivery_fee }}</span>
              </div>
                 <div class="col-xs-7 col-md-7 col-lg-7 text-left">
                  <span>Discount</span>
                </div>
                <div class="col-xs-5 col-md-5 col-lg-5 text-right">
                  <span>{{ summary.discount }}</span>
                </div>
                </div>

              <div class="row total">
                 <div class="col-xs-7 col-md-7 col-lg-7 text-left">
                  <span>Total (incl. VAT)</span>
                </div>
                <div class="col-xs-5 col-md-5 col-lg-5 text-right">
                  <span>{{ summary.total }}</span>
                </div>
              </div>
          
          </div>
          
            <div class="button" >
                <p class="alert alert-warning text-center" v-if="!store_open">Store is currently offline</p>
                <button type="button" class="btn btn-block btn-pahatud" v-on:click="checkout"v-if="store_open"><span>CHECKOUT</span></button>
                <button type="button" class="btn btn-block btn-pahatud" v-if="!store_open" disabled><span>CHECKOUT</span> </button>
            </div>


        </div>
    </div>

    <!-- Modal -->
      <div class="modal fade" id="cart-summary" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Cart Summary</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body m-0 p-0">
              
                <div class="container m-0 p-0" id="summary-checkout">
              <div class="text-center mt-3 delivery" v-if="cart.duration!=null">
                 Delivery - {{ cart.duration }}  
              </div>
              <h3 class="restaurant_name">
                <div v-if="cart.partner">
                  <a :href="cart.partner.slug">
                    Your Cart - {{ cart.partner.restaurant_name }}
                  </a>
                </div>
              </h3>
              <div class="cart-item text-center">
                <p v-if="!items.length > 0">Hungry huh, add items to your Cart.</p>
                <div class="product-information">
                     <div class="item-list" v-for="item in items">
                          <div class="row product">
                             <div class="col-6 text-left name">
                              <span>{{ item.item.title }}</span>
                            </div>
                            <div class="col-6 text-right">
                              <span>PHP {{ item.item.price }}</span>
                            </div>
                         </div>
                         <div class="row">
                            <div class="col-8 text-left">
                              <p class="p-0 m-0" v-for="variant in item.variance_content">+ {{ variant.title }}</p>
                            </div>
                            <div class="col-4 text-right">
                                <div class="controls">
                                    <span class="min">
                                      <a href="javascript:void(0)" v-on:click="updateCartItemDelete(item)" v-if="item.qty == 1"><i class="icofont-trash"></i></a>
                                      <a href="javascript:void(0)" v-on:click="updateCartItemMinus(item)" v-if="item.qty > 1"><i class="icofont-minus"></i></a>
                                    </span>
                                    <span class="qty">{{ item.qty }}</span>
                                    <span class="plus">
                                        <a href="javascript:void(0)" v-on:click="updateCartItemAdd(item)"><i class="icofont-plus"></i></a>
                                    </span>
                                </div>
                            </div>
                            <hr>
                         </div>
                     </div>
                </div>
              </div>
              <div v-if="refresh" class="text-center m-b-4">
                <!-- <img src="/images/ajax-loader.gif" alt=""> -->
              </div>

              <div class="total-summary" v-if="!refresh">
                  <div class="row">
                    <div class="col-6 text-left">
                      <span class="text-left">Sub Total</span>
                    </div>
                    <div class="col-6 text-right">
                      <span>{{ summary.sub_total }}</span>
                    </div>
                     <div class="col-6 text-left">
                      <span>Delivery Fee</span>
                    </div>
                    <div class="col-6 text-right">
                      <span>{{ summary.delivery_fee }}</span>
                    </div>
                       <div class="col-6 text-left">
                        <span>Discount</span>
                      </div>
                      <div class="col-6 text-right">
                        <span>{{ summary.discount }}</span>
                      </div>
                  </div>

                   <div class="row total">
                     <div class="col-6 text-left">
                      <span>Total (incl. VAT)</span>
                    </div>
                    <div class="col-6 text-right">
                      <span>{{ summary.total }}</span>
                    </div>
                  </div>

                  </div>
                 
                  <div class="button" >
                      <p class="alert alert-warning text-center" v-if="!store_open">Store is currently offline</p>
                      <button type="button" class="btn btn-block btn-pahatud" v-on:click="checkout" v-if="store_open"><span>CHECKOUT</span></button>
                      <button type="button" class="btn btn-block btn-pahatud" v-on:click="checkout" v-if="!store_open" disabled><span>CHECKOUT</span> <!----></button>
                  </div>
              </div>


            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- end of modal --> 


  </section>
</template>
<script>
    export default {
        data() {
          return {
              cart: {},
              items: {},
              summary: {},
              merchant: {},
              refresh: false,
          }
        },
        props: ['store_open'],
        created() {
          Event.$on('reloadSummary', () => {
            console.log('Updating cart');
            this.fetchData();
          });
          Event.$on('proceed-to-cart', () => {
            this.checkout();
          });
        },
        mounted() {
            this.fetchData();
        },
        methods: {
            fetchData: function() {
              var self = this;
              self.refresh = true;
              axios.get('/api/cart/summary').then(function (response) {
                  self.cart = response.data.cart
                  if (self.cart) {
                    self.items = response.data.cart.details;
                    self.summary = response.data.summary;
                    Event.$emit('CartItemCount',  self.summary.qty);
                    Event.$emit('app-summary-cart',  self.summary);  
                  }
                  self.refresh = false;
              })
              .catch(function (error) {
                  toastr.error(error);
              });
            },
            checkout: function() {
                if (isLogged) {
                    window.location = "/checkout";
                }
                else {
                  $('#myModalLogin').modal();
                }
            },
            updateCartItemMinus: function(cartItem) {
              axios.post('/api/cart/'+cartItem.id+'/action/minus/submit').then((response) => {
                if (response.data.status) {
                  Event.$emit('reloadSummary');
                }
                else {
                  toastr.info(response.data.message);
                }
              }).catch((errors) => {
                  toastr.error(errors);
              }); 
            },
            updateCartItemAdd: function (cartItem) {
              axios.post('/api/cart/'+cartItem.id+'/action/add/submit').then((response) => {
                if (response.data.status) {
                  Event.$emit('reloadSummary');
                }
                else {
                  toastr.info(response.data.message);
                }
              }).catch((errors) => {
                  toastr.error(errors);
              }); 
            },
            updateCartItemDelete: function (cartItem) {
              axios.post('/api/cart/'+cartItem.id+'/action/delete/submit').then((response) => {
                if (response.data.status) {
                  Event.$emit('reloadSummary');
                }
                else {
                  toastr.info(response.data.message);
                }
              }).catch((errors) => {
                  toastr.error(errors);
              }); 
          },
            clickHere: function () {
              console.log('na click here');
            }
        }
    }

</script>

