<template>
 
  <section class="product style-2 padding-tb">
    <div class="container">
        <div class="section-header">
            <h3>{{ restaurant.restaurant_name }}</h3>
            <p>{{ restaurant.address }}</p>
            <p>{{ restaurant.food_type.title }}</p>
        </div>
    </div>

    <div class="container-fluid p-0" id="restaurantView">
       <div class="scroll">
          <nav class="text-center">
            <a href="javascript:void(0)" class="p-3 categorymenu" v-for="category in restaurant.category" :id="'cat_'+category.identifier" v-on:click="openMenu(category.identifier)" v-if="category.products.length >0">
              {{ category.name }}
            </a>
          </nav>
        </div>
    </div>
    
    <div class="shop-page single padding-tb">
        <div class="container p-0">
            <div class="section-wrapper">
                <div class="row justify-content-center">

                    <div v-if="restaurant.category <=0">
                      <div class="alert alert-info" role="alert">
                        We are still adding our delicious menu. Please try to visit later. Thanks!
                      </div>
                    </div>

                    <div class="col-xl-12 col-sm-12 col-xs-12"  v-for="category in restaurant.category" v-if="category.products.length >0">
                        <article>
                            <div class="shop-title d-flex flex-wrap justify-content-between">
                              <div class="row">
                                  <div class="col-md-12 col-xs-12 col-sm-12 col-lg-12">
                                        <h5 :id="category.identifier">{{ category.name }}</h5>
                                  </div>
                                  <div class="col-md-12 col-xs-12 col-sm-12 col-lg-12">
                                        <p>{{ category.description }}</p>
                                  </div>
                              </div>
                            </div>
                           <div class="shop-product-wrap list row">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" v-for="product in category.products">
                                    <div class="product-list-item">
                                        <div class="product-thumb" v-if="product.img">
                                            <img :src="product.imgname+'&s=thumb'" :alt="product.title">
                                            <div class="product-action-link">
                                                <a href="javascript:void(0)" v-on:click="selectedVariance(product)"><i class="icofont-cart-alt"></i></a>
                                            </div>
                                        </div>
                                        <div class="product-content p-l-1">
                                            <h6><a href="javascript:void(0)" v-on:click="selectedVariance(product)">{{ product.title }}</a></h6>
                                            <p v-if="product.markdown_price">
                                                {{ product.markdown_price }} 
                                                <span class="discount">{{ product.price }}</span>
                                            </p>
                                            
                                            <p v-if="product.description">{{ product.description }}</p>
                                            <p v-if="!product.markdown_price">{{ product.price }} </p>
                                        </div>
                                    </div>
                                    <div class="addcart-button" v-on:click="selectedVariance(product)">
                                              <span class="material-icons">add_circle_outline</span>
                                    </div>

                                </div>
                            </div>
                            <div class="paginations">
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Shop Page Section Ending Here -->
       <variance-modal-components v-bind:display="display" v-bind:productInfo="selectedProduct" ref="variantModal" v-bind:addingthisformerchant="addingThisForMerchant" ></variance-modal-components>     
  </section>

</template>

<script>
    import varianceComponents from '../restaurant/includes/VarianceModalComponent.vue';

    export default {
       components: {
           'variance-modal-components': varianceComponents,
        },
        data() {
          return {
              data: {},
              display: {},
              selectedProduct: {},
              addingThisForMerchant: false,
              timerCount: 3,
              coordinate: {
                lat: "",
                long: "",
              },
          }
        },
        props: ['restaurant', 'addingthisformerchant'],
        mounted() {

            this.addingThisForMerchant = this.addingthisformerchant;
            
            console.log('Mounted Restaurant Detailed Component')

            // this.coordinate.lat = "";
            // this.coordinate.long = "";
          
            // if (localStorage.address) {
            //   this.delivery_address = localStorage.address;
            // }
            // else if (localStorage.center) {
            //     this.delivery_address = "Pinned location";
            // }
            
            Event.$emit('CheckUserLocation');

        },
        created() {
          Event.$on('updateAddingForMerchant', () => {
              this.addingThisForMerchant = true;
            });
        },
        computed: {
        },
        watch: {
            timerCount: {
                handler(value) {
                    if (value >= 0) {
                        setTimeout(() => {
                            this.timerCount--;
                        }, 1000);
                    }
                    else {
                        this.timerCount = 3;

                        Event.$emit('CheckUserLocation');
                    }
                },
                immediate: true // This ensures the watcher is triggered upon creation
            }
        },
        methods: {
            selectedVariance: function(product) {
                if (product.variants.length > 0) {
                  this.$refs.variantModal.reloadVariantProductInfo(product); 
                  this.selectedProduct = product;
                  $('#varianceModal').modal(); 
                }
                else {
                  this.addCart(product);
                }
            },
            fetchData: function() {
              var self = this;
              axios.get('/api/restaurants').then(function (response) {
                  self.data = response.data;
              })
              .catch(function (error) {
                  toastr.error(error);
              });
            },
            addCart: function (product) {

                if(!this.addingThisForMerchant) {
                    this.$confirm(
                     'You are adding an item with different Merchant/Restaurant. Your current cart will be set to empty. Do you want us to proceed?',
                     '',
                     'warning',
                     3000).then((response) => {
                        if (response) {
                          this.addingThisForMerchant = true;
                          this.emptyAdd(product);
                        }
                    });
                }
                else {
                  // if not empty and not equal just proceed with adding the cart 
                  this.simpleAdd(product);  
                }
            },

            simpleAdd: function (product) {
              axios.post('/api/item/add-cart', { 
                item_id: product.id,
              }).then((response) => {
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
            emptyAdd: function (product) {
              axios.post('/api/item/new/add-cart', { 
                item_id: product.id,
              }).then((response) => {
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

            validate: function() {

            },
            fav: function() {
                toastr.success("Liked");
            },
            openMenu: function(name) {
                $('.selection_cat').removeClass('active');
                $('#cat_'+name).addClass('active');

                $('html, body').animate({
                 scrollTop: $("#"+name).offset().top - 100
                }, 1000);
            },
            
        }
    }

</script>

