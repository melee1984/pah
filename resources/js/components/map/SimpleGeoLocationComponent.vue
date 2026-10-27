<template>
  <div>
    <!-- Error Message -->
    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }} 
      <span v-if="error.includes('Permission denied')">
        Please enable location access in your browser settings and try again.
      </span>
    </div>

    <!-- Loading State -->
    <p v-else-if="loading">Getting your location...</p>

    <!-- Coordinates Display -->
    <p v-else-if="coordinates">
      Latitude: {{ coordinates.lat }}, Longitude: {{ coordinates.lng }}<br>
      Address: {{ address }}
    </p>

    <!-- Try Again Button -->
    <button @click="getLocation" class="btn btn-pahatud mt-2">
      Try Again
    </button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      coordinates: null,
      address: "",
      error: null,
      loading: false,
      geocoder: null,
    };
  },
  methods: {
    getLocation() {
      this.loading = true;
      this.error = null;
      this.coordinates = null;
      this.address = "";

      if (!navigator.geolocation) {
        this.loading = false;
        this.error = "Geolocation is not supported by your browser.";
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          this.loading = false;
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          this.coordinates = { lat, lng };

          if (!this.geocoder) {
            this.geocoder = new google.maps.Geocoder();
          }

          // Reverse geocode
          this.geocoder.geocode({ location: { lat, lng } }, (results, status) => {
            if (status === "OK" && results[0]) {
              this.address = results[0].formatted_address;

              // Save to localStorage
              localStorage.setItem(
                "center",
                JSON.stringify({
                  lat,
                  lng,
                })
              );
              localStorage.setItem("address", this.address)
              // Redirect to home page (full reload)
              window.location.href = "/";
            } else {
              this.address = "Unable to fetch address";
            }
          });
        },
        (err) => {
          this.loading = false;
          switch (err.code) {
            case 1:
              this.error = "Permission denied.";
              break;
            case 2:
              this.error = "Position unavailable. Please try again.";
              break;
            case 3:
              this.error = "Location request timed out. Please try again.";
              break;
            default:
              this.error = "Unable to get your location.";
          }
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    },
  },
  mounted() {
    this.getLocation(); // Try getting location on page load
  },
};
</script>
