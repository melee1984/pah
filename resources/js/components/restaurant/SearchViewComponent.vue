<template>
    <div class="container">
           
            <div class="section-wrapper d-none d-sm-block d-sm-none d-md-block">
                <div class="row ">
                    <!-- Restaurant Display -->
                    <div class="col-xl-3 col-md-4 col-sm-3 col-12" v-for="restaurant in restaurants">
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

            <div class="d-block d-sm-none d-none d-sm-block d-md-none">
                <div class="row" v-for="restaurant in restaurants" style="margin-bottom: 20px;">
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
              coordinates: {
                long: 0,
                lat: 0,
              },
              loading: true,
          }
        },
        props: ['restaurants'],
        mounted() {
            Event.$emit('CheckUserLocation');
        },
        created() {
            
        },  
        methods: {
            fetchData: function() {
            },
        }
    }
</script>
