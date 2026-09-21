<template>
  <div>
    <div class="row" id="demoMap">
      <div class="col-md-8">
        <gmp-map id="myMap2" center="0,0" zoom="16" map-id="DEMO_MAP_ID"></gmp-map>
      </div>
      <div class="col-md-4">
        <input
          id="searchBox2"
          type="text"
          placeholder="Search location..."
          class="search-input"
        />

        <!-- My Location Button -->
        <button class="my-location-btn" @click="goToMyLocation">My Location</button>
        <!-- Map -->

        <!-- Coordinates and Address input -->
        <div class="coords">
          <p>Latitude: {{ latitude }}</p>
          <p>Longitude: {{ longitude }}</p>
          <label>
            Address:
            <input type="text" v-model="address" class="address-input" />
          </label>
        </div>
      </div>
    </div>
    <!-- Search Box -->
  </div>
</template>

<script>
export default {
  data() {
    return {
      latitude: null,
      longitude: null,
      address: "",
      marker: null,
      autocomplete: null,
      mapInstance: null,
      geocoder: null,
    };
  },

  mounted() {
    this.initMap();
  },

  methods: {
    async initMap() {
      const [{ Map }, { AdvancedMarkerElement }] = await Promise.all([
        google.maps.importLibrary("maps"),
        google.maps.importLibrary("marker"),
      ]);

      const { Autocomplete } = await google.maps.importLibrary("places");

      const mapEl = document.getElementById("myMap2");
      const map = mapEl.innerMap;
      this.mapInstance = map;

      map.setOptions({ mapTypeControl: false });

      // Initialize geocoder
      this.geocoder = new google.maps.Geocoder();

      // Try getting user location initially
      this.setCurrentLocation();

      // Initialize search box
      const input = document.getElementById("searchBox2");
      this.autocomplete = new Autocomplete(input, {
        fields: ["geometry", "name", "formatted_address"],
      });

      this.autocomplete.addListener("place_changed", () => {
        const place = this.autocomplete.getPlace();
        if (!place.geometry) return;

        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();

        map.setCenter({ lat, lng });
        map.setZoom(16);

        if (this.marker) this.marker.position = { lat, lng };
        else this.createMarker(lat, lng);

        this.latitude = lat;
        this.longitude = lng;

        this.updateAddress(lat, lng);
      });
    },

    createMarker(lat, lng) {
      const { AdvancedMarkerElement } = google.maps.marker;

      this.marker = new AdvancedMarkerElement({
        map: this.mapInstance,
        position: { lat, lng },
        gmpDraggable: true,
        title: "Move me",
      });

      // Update lat/lng and address after drag
      this.marker.addListener("dragend", (event) => {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();
        this.latitude = lat;
        this.longitude = lng;

        this.updateAddress(lat, lng);
      });

      // Bounce animation on click
      this.marker.addListener("click", () => {
        this.marker.element.classList.add("bounce");
        setTimeout(() => this.marker.element.classList.remove("bounce"), 700);
      });
    },

    setCurrentLocation() {
      if (!navigator.geolocation) {
        alert("Geolocation not supported by your browser.");
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;

          this.mapInstance.setCenter({ lat, lng });
          this.mapInstance.setZoom(16);

          if (this.marker) this.marker.position = { lat, lng };
          else this.createMarker(lat, lng);

          this.latitude = lat;
          this.longitude = lng;

          this.updateAddress(lat, lng);
        },
        (err) => {
          console.error("Error getting location:", err);
          alert("Unable to retrieve your location.");
        }
      );
    },

    goToMyLocation() {
      this.setCurrentLocation();
    },

    updateAddress(lat, lng) {
      if (!this.geocoder) return;

      this.geocoder.geocode({ location: { lat, lng } }, (results, status) => {
        if (status === "OK" && results[0]) {
          this.address = results[0].formatted_address;
        } else {
          this.address = "Address not found";
          console.error("Geocoder failed:", status);
        }
      });
    },
  },
};
</script>

<style>
  gmp-map {
    display: block;     /* prevents inline behavior */
    height: 100vh;
    width: 100%;
    min-height: 300px;  /* optional: prevents map from collapsing */
}
</style>