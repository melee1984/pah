<template>
  <span>
      <a href="javascript:void(0)" v-if="store_online" class="btn btn-success" v-on:click="updateStore">Store is Online</a>
      <a href="javascript:void(0)" v-if="!store_online" class="btn btn-danger" v-on:click="updateStore">Store is Offline</a>
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

