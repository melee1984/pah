<template>
  <section>
  	<p><a href="javascript:void(0)" v-on:click="userLocation">DELIVERING TO: <span> {{ delivery_address }} </span></a></p>
  </section>
</template>

<script>
    export default {
      data() {
        return {
        	delivery_address: 'Pin your location'
        }
      },
      created() {
      	Event.$on('updateLocationAddress', () => {

          if (localStorage.address) {
            this.delivery_address = localStorage.address;
          }

          if (localStorage.address) {
            this.delivery_address = localStorage.address;
          }
          else if (localStorage.center) {
              this.delivery_address = "Pinned location";
          }
          
        });
      },
      mounted() {

        if (localStorage.address) {
          this.delivery_address = localStorage.address;
        }
        else if (localStorage.center) {
            this.delivery_address = "Pinned location";
        }
 
      },
      computed: {
      	
      },
      methods: {
       	userLocation: function() {
       		Event.$emit('PinMapUserLocation');
       	}
      }

    }
</script>


