<template>
    <div class="container-fluid">
            
            <div class="text-center" v-if="loading">
                <!-- <img src="images\ajax-loader.gif" alt="please wait..."> -->
            </div>

            <div class="section-wrapper d-none d-sm-block d-sm-none d-md-block" v-if="!loading">
                <div class="row ">
                    <!-- Restaurant Display -->
                    <div class="col-xl-2 col-md-3 col-sm-3 col-12" v-for="restaurant in data">
                        <div class="p-food-item">
                            <div class="p-food-inner">
                                <div class="p-food-thumb">
                                    <a :href="'/restaurant/'+restaurant.slug">
                                        <img :src="''+restaurant.img"  alt="p-food">
                                    </a>
                                  <!--   <span>$20 - $30</span> -->
                                </div>
                                <div class="p-food-content pt-0 mt-0">
                                    <h6><a :href="'/restaurant/'+restaurant.slug">{{ restaurant.restaurant_name }}</a></h6>
                                    <div class="p-food-group" v-if="restaurant.food_type.length">
                                        <a :href="'/restaurant/'+restaurant.slug" v-for="foodType in restaurant.food_type">{{ foodType.title }}</a>
                                    </div>
                                    <div class="p-food-footer">
                                        <div class="left">
                                            <div class="rating">
                                                <i class="icofont-star"></i>
                                                <i class="icofont-star"></i>
                                                <i class="icofont-star"></i>
                                                <i class="icofont-star"></i>
                                                <i class="icofont-star"></i>
                                            </div>
                                        </div>
                                        <div class="right">
                                            <i class="icofont-home"></i>
                                            {{ restaurant.short_title }}
                                        </div>
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <!-- / Restaurant Display -->
                </div>
            </div>

            <div class="d-block d-sm-none d-none d-sm-block d-md-none" v-if="!loading">
                <div class="row" v-for="restaurant in data" style="margin-bottom: 20px;">
                    <div class="col-4 p-0">
                        <a :href="'/restaurant/'+restaurant.slug">
                            <img :src="''+restaurant.img"  alt="p-food" class="img-responsive" />
                        </a>
                    </div>
                    <div class="col-8 p-l-10 p-b-2">
                        <a :href="'/restaurant/'+restaurant.slug" style="font-size: 18px;"><strong>{{ restaurant.restaurant_name }}</strong></a>
                          <div class="p-food-group" v-if="restaurant.food_type.length" style="font-size:12px;font-weight: 400;margin-top: 10px;">
                                        <a :href="'/restaurant/'+restaurant.slug" v-for="foodType in restaurant.food_type">{{ foodType.title }}</a>
                        </div>

                        <div style="font-size:12px;font-weight: 400;margin-top: 10px;">{{ restaurant.address }}</div>

                        <div class="rating">
                            <i class="icofont-star"></i>
                            <i class="icofont-star"></i>
                            <i class="icofont-star"></i>
                            <i class="icofont-star"></i>
                            <i class="icofont-star"></i>
                        </div>

                    </div>
                </div> 
            </div>


        </div>
</template>

<script>
        

    export default {
        data() {
          return {
              data: {},
              coordinates: {
                long: 0,
                lat: 0,
              },
            timerCount: 3,
            loading: true,
          }
        },
        mounted() {
            this.fetchData();
            Event.$emit('CheckUserLocation');
        },
        created() {
          Event.$on('reloadRestaurants', () => {
              this.fetchData();
            });
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
            fetchData: function() {
                if (page_url!='restaurants') return;
                
                    var self = this;
                    self.loading = true;
                    axios.get('/api/restaurants').then(function (response) {
                        self.data = response.data;
                        self.loading = false;
                    }).catch(function (error) {
                      console.log(error);
                    });

            },
        }
    }
</script>
