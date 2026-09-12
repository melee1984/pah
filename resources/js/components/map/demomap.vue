<template>
  <div>
    <h1>Geolocation Example</h1>

    <!-- Display error -->
    <p v-if="errorMessage" style="color:red">{{ errorMessage }}</p>

    <!-- Show latitude and longitude -->
    <p v-if="latitude && longitude">
      Latitude: {{ latitude }}, Longitude: {{ longitude }}
    </p>

    <!-- Loading -->
    <p v-if="isLoading">Loading location...</p>

    <!-- Map container -->
    <div id="mapdemo" style="width: 100%; height: 400px; background-color: red;"></div>
  </div>
</template>

<script>
import { setOptions, importLibrary } from "@googlemaps/js-api-loader";

export default {
  data() {
    return {
      latitude: null,
      longitude: null,
      isLoading: false,
      errorMessage: null,
      map: null,
      infoWindow: null,
    };
  },
  mounted() {
    this.getGeolocation();
  },
  methods: {
    getGeolocation() {
      if (!navigator.geolocation) {
        this.errorMessage = "Geolocation is not supported by your browser.";
        return;
      }

      this.isLoading = true;

      this.latitude = 7.113258729803093;
      this.longitude = 125.49273844487138;

      navigator.geolocation.getCurrentPosition(
        async (position) => {
          this.latitude = position.coords.latitude;
          this.longitude = position.coords.longitude;
          this.isLoading = false;

          console.log("latitude:", this.latitude, "Longitude:", this.longitude);

          // Initialize the map after getting coordinates
          await this.initMap();
        },
        (err) => {
          this.isLoading = false;
          this.errorMessage = `Error: ${err.message}`;
        }
      );
    },
async initMap() {
  // Set API key, libraries, and version
  setOptions({
    apiKey: "AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk", // replace with your key
    version: "weekly",
    libraries: ["places"],
  });

  // Import the Maps library and get constructors
  const { Map, InfoWindow, Marker } = await importLibrary("maps");

  console.log("latitude:", this.latitude, "Longitude:", this.longitude);

  // Create the map
  this.map = new Map(document.getElementById("mapdemo"), {
    center: { lat: this.latitude, lng: this.longitude },
    zoom: 15,
  });

  console.log("Map initialized:", this.map);

  // InfoWindow
  this.infoWindow = new InfoWindow({
    content: "You are here",
    position: { lat: this.latitude, lng: this.longitude },
  });
  this.infoWindow.open(this.map);

  // Optional: Add a marker
  new Marker({
    position: { lat: this.latitude, lng: this.longitude },
    map: this.map,
  });
},


handleLocationError(browserHasGeolocation) {
      this.infoWindow.setPosition(this.map.getCenter());
      this.infoWindow.setContent(
        browserHasGeolocation
          ? "Error: The Geolocation service failed."
          : "Error: Your browser doesn't support geolocation."
      );
      this.infoWindow.open(this.map);
    },
  },
};
</script>

<style scoped>
h1 {
  font-size: 24px;
  margin-bottom: 10px;
}

p {
  font-size: 18px;
}

.custom-map-control-button {
  background-color: #fff;
  border: 0;
  padding: 8px;
  margin: 10px;
  font-size: 14px;
  cursor: pointer;
  border-radius: 4px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.custom-map-control-button:hover {
  background-color: #e6e6e6;
}
</style>
