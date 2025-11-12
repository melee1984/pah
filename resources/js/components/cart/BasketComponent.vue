<template>
  <section>
  	<a href="/checkout">
      <i class="icofont-basket"></i> {{ item_count }} 
    </a>
  </section>
</template>

<script>
    export default {
      data() {
        return {
        	item_count: null,
        }
      },
      mounted() {
        this.fetchData();
      },
      created() {
        Event.$on('CartItemCount', (cartItemCount) => {
           this.setItemCount(cartItemCount);
        });
      },
      methods: {
       	setItemCount: function(cartItemCount) {
          this.item_count = cartItemCount;
       	},
        fetchData: function() {
          var self = this;
          axios.get('/api/cart/summary').then(function (response) {
            self.item_count = response.data.summary.qty;
          })
          .catch(function (error) {
              console.log(error);
          });
        },
      }

    }
</script>


