<template>
  <span class="merchant-store-control">
      <button type="button" v-if="store_online" class="merchant-store-toggle is-online" v-on:click="updateStore"><i class="fas fa-circle"></i> Store online</button>
      <button type="button" v-if="!store_online" class="merchant-store-toggle is-offline" v-on:click="updateStore"><i class="fas fa-circle"></i> Store offline</button>
  </span>
</template>
<script>
export default {
     
  data() {
    return {
    }
  },
  props: ['store_online'],
  mounted() {
         
  }, 
  methods: {
    updateStore: function() {
            var self = this;
            axios.post('/api/merchant/update/store/online/submit?api_token='+api_token, {
                open: self.store_online
            }).then((response) => {
                if (response.data.status) {
                    self.store_online = response.data.store_online;
                }
                else {
                  toastr.info(response.data.message);
                }
            }).catch((errors) => {
                 
            }); 
        },
    }
}
</script>
