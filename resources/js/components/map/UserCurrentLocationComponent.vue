<template>
  <section>
    <!-- Modal HTML -->
    <div id="pinLocation" class="modal fade" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-confirm modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Let us know your Location</h5>
          </div>
          <div class="modal-body">
            <div class="input-group mb-3">
              <input
                id="searchBoxFilter"
                type="text"
                class="form-control"
                v-model="address"
                placeholder="Search your location..."
              />
              <span class="input-group-text">
                <a href="javascript:void(0)" @click="setCurrentLocation">
                  <i class="icofont-location-pin"></i>
                </a>
              </span>
            </div>
            <div>{{ latitude }}, {{ longitude }} , {{ address }}</div>
            <!-- MAP -->
            <gmp-map
              id="myMapDisplayUserLocation"
              center="0,0"
              zoom="16"
              map-id="DEMO_MAP_ID"
            ></gmp-map>
            <!-- PIN BUTTON -->
            <button
              class="btn btn-pahatud btn-block mt-4"
              v-on:click="usePlace"
              v-if="PinEnabled"
            >
              <span>Pin Location</span>
            </button>
            <button class="btn btn-block mt-4" v-if="!PinEnabled" disabled>
              <span>Pin Location</span>
            </button>
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
      position: null,
      map: null,
      zoom: 10,
      autocomplete: null,
      center: {
        lat: 10.0,
        lng: 10.0,
      },
      coordinates: {
        lat: 7.0816953475528255,
        lng: 125.5778156310179,
      },
      fields: {
        title: "",
        address: "",
        notes: "",
        coordinates: {
          lat: 0,
          lng: 0,
        },
      },
      value: "demo",
      markers: [
        {
          position: { lat: 11.0, lng: 11.0 },
        },
      ],
      place: null,
      PinEnabled: false,
      modalIsPop: false,
      //
      latitude: null,
      longitude: null,
      address: "",
      marker: null,
      autocomplete: null,
      mapInstance: null,
      geocoder: null,
    };
  },
  created() {
    if (!localStorage.center) {
      this.getCurrentLocation();
    }
    if (localStorage.center) {
      this.coordinates = JSON.parse(localStorage.center);
      this.latitude = this.coordinates.lat;
      this.longitude = this.coordinates.lng;
    }
    if (localStorage.zoom) {
      this.zoom = parseInt(localStorage.zoom);
    }
    if (localStorage.address) {
      this.fields.address = localStorage.address;
    }
    if (localStorage.notes) {
      this.fields.notes = localStorage.notes;
    }
    Event.$on("PinMapUserLocation", () => {
      const modalElement = document.getElementById("pinLocation");
      const modal = new bootstrap.Modal(modalElement);
      modal.show();
      this.initMap();
    });

    Event.$on("CheckUserLocation", () => {
      console.log("Checking User Location Address Popup");
      this.checkUserLocationAddress();
    });

    Event.$on("updateUserCartCoordinate", () => {
      this.updateUserCartLocation();
    });

    // Not sure if I will add this on the line //

    // this.validateFields();
  },
  mounted() {
    this.initMapWithRetry(2, 2000);
  },
  computed: {
    // mapCoordinates() {
    //   if (!this.map) {
    //     return {
    //       lat: 7.0816953475528255,
    //       lng: 125.5778156310179,
    //     };
    //   }
    //   return {
    //     lat: this.map.center.lat(),
    //     lng: this.map.center.lng(),
    //   };
    // },
  },
  methods: {
    getCurrentLocation: function () {},
    // getAddressData: function (addressData, placeResultData, id) {
    //   console.log("Samke --  " + addressData);
    // },
    // mapDisplay: function () {},
    // validate: function () {},
    // openMap: function () {
    //   console.log("open Map");
    // },
    // handleDrag: function () {
    //   let center = {
    //     lat: this.map.center.lat(),
    //     lng: this.map.center.lng(),
    //   };
    //   let zoom = this.map.getZoom();

    // localStorage.center = JSON.stringify(center);
    // localStorage.zoom = zoom;
    // localStorage.address = this.fields.address;
    // localStorage.notes = this.fields.notes;
    //   this.validateFields();
    //   this.updateUserCartLocation();
    // },
    setPin: function () {
      localStorage.address = this.address;
      Event.$emit("updateLocationAddress");

      this.updateUserCartLocation();
      const modalElement = document.getElementById("pinLocation");
      const modal = new bootstrap.Modal(modalElement);
      modal.hide();
    },
    // setPlace(place) {.
    //   this.place = place;
    //   this.coordinates.lat = this.place.geometry.location.lat();
    //   this.coordinates.lng = this.place.geometry.location.lng();

    //   Event.$emit("updateLocationAddress");
    //   this.updateUserCartLocation();
    // },
    updateUserCartLocation: function () {
      if (!localStorage.center) {
        return;
      }
      this.coordinates = JSON.parse(localStorage.center);
      if (this.coordinates.lat != "" && this.coordinates.lng != "") {
        let formData = new FormData();
        formData.append("lat", this.latitude);
        formData.append("long", this.longitude);
        axios
          .post("/api/location/submit?api_token=" + api_token, formData)
          .then((response) => {
            Event.$emit("reloadSummary");
            Event.$emit("reloadRestaurants");
          })
          .catch((errors) => {
            toastr.error(errors);
          });
      }
    },
    validateFields: function () {
      let isValidated = true;

      if (this.coordinates.lat == "") {
        isValidated = false;
      } else if (this.coordinates.lng == "") {
        isValidated = false;
      }
      if (isValidated) {
        this.PinEnabled = true;
      }
    },
    usePlace(place) {
      if (this.place) {
        this.markers.push({
          position: {
            lat: this.place.geometry.location.lat(),
            lng: this.place.geometry.location.lng(),
          },
        });
        let center = {
          lat: this.place.geometry.location.lat(),
          lng: this.place.geometry.location.lng(),
        };
        let zoom = this.map.getZoom();
        localStorage.center = JSON.stringify(center);
        localStorage.zoom = zoom;
        this.place = null;
      }
      this.setPin();
    },
    // this should only run once but when click the map should load without issue//

    async initMapWithRetry(retries = 3, delay = 2000) {
        // --- CASE 1: No coordinates → load map with retry
        if (!this.latitude || !this.longitude) {

          const attempt = async (remaining) => {
            try {
              await this.initMap();
              console.log("Map loaded successfully");
            } catch (err) {
              console.error("initMap failed:", err);
              if (remaining === 0) return;
              console.warn(`Retrying map initialization… (${remaining} left)`);
              setTimeout(() => attempt(remaining - 1), delay);
            }
          };

          attempt(retries);
          return;
        }
        // --- CASE 2: Coordinates exist → verify session on backend
        console.log("Coordinates found → checking existing session...");
        try {
          const response = await axios.post("/api/cart/validate-session", {
            latitude: this.latitude,
            longtitude: this.longitude,
          });

          if (response.data.status === "exists") {
            console.log("Existing cart session found");
          } else if (response.data.status === "created") {
            console.log("New cart session created");
          }

        } catch (err) {
          console.error("Error checking cart session:", err);
        }
    },
    async initMap() {
      const [{ Map }, { AdvancedMarkerElement }] = await Promise.all([
        google.maps.importLibrary("maps"),
        google.maps.importLibrary("marker"),
      ]);

      const { PlaceAutocompleteElement } = await google.maps.importLibrary("places");

      const mapEl = document.getElementById("myMapDisplayUserLocation");
      const map = mapEl.innerMap;
      this.mapInstance = map;
      map.setOptions({ mapTypeControl: false });

      // Initialize geocoder
      this.geocoder = new google.maps.Geocoder();

      // Try getting user location initially
      this.setCurrentLocation();

      // Initialize PlaceAutocompleteElement
      const input = document.getElementById("searchBoxFilter");
      this.autocompleteElement = new PlaceAutocompleteElement({
        inputElement: input,
        fields: ["geometry", "name", "formatted_address"],
      });

      // Listen for place selection
      this.autocompleteElement.addEventListener("place_changed", () => {
        const place = this.autocompleteElement.getPlace();
        if (!place.geometry) return;

        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();

        map.setCenter({ lat, lng });
        map.setZoom(17);

        if (this.marker) this.marker.position = { lat, lng };
        else this.createMarker(lat, lng);

        this.latitude = lat;
        this.longitude = lng;

        // Update database
        this.updateUserCartLocation();
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

    async setCurrentLocation() {
      try {
        const pos = await this.getUserLocationWithRetry(3, 2000);

        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        this.latitude = lat;
        this.longitude = lng;

        this.mapInstance.setCenter({ lat, lng });
        this.mapInstance.setZoom(16);

        if (this.marker) this.marker.position = { lat, lng };
        else this.createMarker(lat, lng);

        this.updateAddress(lat, lng);
      } catch (err) {
        // Location is optional. If it cannot be detected, leave the page usable
        // and let the customer choose a location manually when they need to.
        console.warn("Unable to detect the user's location:", err);
      }
    },

    goToMyLocation() {
      this.setCurrentLocation();
    },
    async updateAddress(lat, lng) {
      try {
        const result = await this.reverseGeocodeWithRetry(lat, lng, 3, 1500);

        this.address = result.formatted_address;

        localStorage.setItem("address", this.address);
        localStorage.setItem("center", JSON.stringify({ lat, lng }));

        this.updateUserCartLocation();

        Event.$emit("updateLocationAddress");
      } catch (err) {
        console.error("Reverse geocode failed after retries:", err);
        this.address = "Unable to fetch address";
      }
    },

    closeThisWindow: function () {
      this.modalIsPop = false;
      const modalElement = document.getElementById("pinLocation");
      const modal = new bootstrap.Modal(modalElement);
      modal.hide();
    },
    checkUserLocationAddress: function () {
      let popupUserLocation = false;
      if (localStorage.center || localStorage.address) {
        popupUserLocation = true;
      }
      this.validateFields();
      if (!popupUserLocation) {
        const modalElement = document.getElementById("pinLocation");
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
      }
    },
    // 1️⃣ Retry getting geolocation
    getUserLocationWithRetry(retries = 3, delay = 2000) {
      return new Promise((resolve, reject) => {
        const attempt = (remaining) => {
          navigator.geolocation.getCurrentPosition(
            (pos) => resolve(pos),
            (err) => {
              if (remaining === 0) return reject(err);
              console.warn("Retrying geolocation…");
              setTimeout(() => attempt(remaining - 1), delay);
            }
          );
        };
        attempt(retries);
      });
    },

    // 2️⃣ Retry reverse geocoding
    reverseGeocodeWithRetry(lat, lng, retries = 3, delay = 1500) {
      return new Promise((resolve, reject) => {
        const attempt = (remaining) => {
          this.geocoder.geocode({ location: { lat, lng } }, (res, status) => {
            if (status === "OK" && res[0]) return resolve(res[0]);
            if (remaining === 0) return reject(status);

            console.warn("Retrying geocoder…");
            setTimeout(() => attempt(remaining - 1), delay);
          });
        };
        attempt(retries);
      });
    },
  },
};
</script>
