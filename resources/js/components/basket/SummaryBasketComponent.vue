<template>
  <section>
      <div class="footer-summary-basket d-block d-sm-none d-none d-sm-block d-md-none" v-if="qty>0">
        <div class="footer-sticky">
           <div class="container">
              <div class="row">
                <div class="col-3 text-left f-cart"><a v-on:click="displayCart"><span class="  material-icons">shopping_basket</span> <span class="item-count">{{ qty }}</span></a> </div>
                <div class="col-4 text-right p-0">MY ORDER</div>
                <div class="col-5 text-right f-amount">PHP {{ total }}</div>
              </div>
            </div>
        </div>
      </div>
  </section>
</template>

<script>

    export default {
        data() {
          return {
              total: 0,
              sub_total: 0,
              qty: 0,
          }
        },
        created() {
          Event.$on('app-summary-cart', (summary) => {
            this.total = summary.total;
            this.sub_total = summary.sub_total;
            this.qty = summary.qty;
          });
        },
        mounted() {
            console.log('Basket Summary when mobile display')
        },
        methods: {

          displayCart: function() {

            $('#cart-summary').modal('toggle');
            // Event.$emit('proceed-to-cart');  
          }
          
        }
        
    }

</script>
