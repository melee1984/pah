<template>
  <section>
    <p>
      <a href="javascript:void(0)" @click="userLocation">
        DELIVERING TO: <span>{{ delivery_address }}</span>
      </a>
    </p>
  </section>
</template>

<script>
export default {
  data() {
    return {
      delivery_address: 'Pin your location'
    };
  },
  created() {
    // Listen for address updates from any child component
    Event.$on('updateLocationAddress', this.updateAddress);
  },
  mounted() {
    this.updateAddress(); // Initialize from localStorage if available
  },
  beforeDestroy() {
    // Clean up event listener
    Event.$off('updateLocationAddress', this.updateAddress);
  },
  methods: {
    userLocation() {
      Event.$emit('PinMapUserLocation');
    },
    updateAddress() {
      if (localStorage.address) {
        this.delivery_address = localStorage.address;
      } else if (localStorage.center) {
        this.delivery_address = 'Pinned location';
      } else {
        this.delivery_address = 'Pin your location';
      }
    }
  }
};
</script>
