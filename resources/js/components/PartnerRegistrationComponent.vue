<template>
  <div>
    <div class="home-form-success" v-if="actionSuccess" role="status">
      <span class="material-icons" aria-hidden="true">check_circle</span>
      <div>
        <strong>Registration received</strong>
        <p>{{ display.message }}</p>
      </div>
    </div>

    <form method="post" v-on:submit.prevent="submitRecord" v-else>
      <div class="home-form-grid">
        <div class="home-form-field home-form-field-full">
          <label for="partner_restaurant_name">Restaurant or business name</label>
          <input type="text" id="partner_restaurant_name" placeholder="Restaurant or virtual kitchen name" required v-model.trim="field.restaurant_name">
        </div>

        <div class="home-form-field home-form-field-full">
          <label for="partner_account_type">Business type</label>
          <select id="partner_account_type" required v-model="field.accountType">
            <option value="" disabled>Select your business type</option>
            <option v-for="type in accounttype" :key="type.id" :value="type.id">{{ type.title }}</option>
          </select>
        </div>

        <div class="home-form-field">
          <label for="partner_firstname">First name</label>
          <input type="text" id="partner_firstname" placeholder="First name" autocomplete="given-name" required v-model.trim="field.firstname">
        </div>

        <div class="home-form-field">
          <label for="partner_lastname">Last name</label>
          <input type="text" id="partner_lastname" placeholder="Last name" autocomplete="family-name" required v-model.trim="field.lastname">
        </div>

        <div class="home-form-field">
          <label for="partner_mobile">Mobile number</label>
          <input type="tel" id="partner_mobile" placeholder="e.g. 0917 123 4567" autocomplete="tel" required v-model.trim="field.mobile">
        </div>

        <div class="home-form-field">
          <label for="partner_telephone">Telephone number</label>
          <input type="tel" id="partner_telephone" placeholder="Telephone number" v-model.trim="field.telephone">
        </div>

        <div class="home-form-field home-form-field-full">
          <label for="partner_address">Business address</label>
          <textarea id="partner_address" rows="3" placeholder="Street and barangay" autocomplete="street-address" required v-model.trim="field.address"></textarea>
        </div>

        <div class="home-form-field">
          <label for="partner_city">City</label>
          <input type="text" id="partner_city" placeholder="City" autocomplete="address-level2" required v-model.trim="field.city">
        </div>

        <div class="home-form-field">
          <label for="partner_facebook">Facebook page <small>(optional)</small></label>
          <input type="url" id="partner_facebook" placeholder="https://facebook.com/..." v-model.trim="field.facebook">
        </div>

        <div class="home-form-field">
          <label for="partner_email">Email address</label>
          <input type="email" id="partner_email" placeholder="you@restaurant.com" autocomplete="email" required v-model.trim="field.email">
        </div>

        <div class="home-form-field">
          <label for="partner_password">Create a password</label>
          <input type="password" id="partner_password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required v-model="field.password">
        </div>
      </div>

      <label class="home-terms-check" for="partner_terms">
        <input type="checkbox" id="partner_terms" required v-model="field.termsAccepted">
        <span>I agree to the <a href="/terms-of-use" target="_blank" rel="noopener noreferrer">Terms and Conditions</a> and understand that my registration is subject to review.</span>
      </label>

      <p class="home-form-error" v-if="errorMessage" role="alert">{{ errorMessage }}</p>

      <button type="submit" class="food-btn style-2" :disabled="isSubmit">
        <span v-if="!isSubmit">Submit partner registration</span>
        <span v-else>Please wait...</span>
      </button>
    </form>
  </div>
</template>

<script>
export default {
  props: {
    accounttype: {
      type: Array,
      default: function () {
        return [];
      },
    },
  },
  data() {
    return {
      field: this.emptyForm(),
      isSubmit: false,
      display: {},
      actionSuccess: false,
      errorMessage: '',
    };
  },
  methods: {
    emptyForm() {
      return {
        restaurant_name: '',
        accountType: '',
        firstname: '',
        lastname: '',
        mobile: '',
        telephone: '',
        address: '',
        city: '',
        facebook: '',
        email: '',
        password: '',
        termsAccepted: false,
      };
    },
    submitRecord() {
      this.errorMessage = '';

      if (!this.field.termsAccepted) {
        this.errorMessage = 'Please accept the Terms and Conditions to continue.';
        return;
      }

      this.isSubmit = true;

      axios.post('/api/merchant/register/submit', {
        restaurant_name: this.field.restaurant_name,
        accountType: this.field.accountType,
        firstname: this.field.firstname,
        lastname: this.field.lastname,
        mobile: this.field.mobile,
        telephone: this.field.telephone || this.field.mobile,
        address: this.field.address,
        city: this.field.city,
        facebook: this.field.facebook,
        email: this.field.email,
        password: this.field.password,
        terms_accepted: this.field.termsAccepted,
      }).then((response) => {
        if (response.data.status) {
          this.display = response.data;
          this.actionSuccess = true;
          this.field = this.emptyForm();
        } else {
          this.errorMessage = response.data.message || 'We could not complete your registration. Please try again.';
        }
      }).catch((error) => {
        const validationErrors = error.response && error.response.data && error.response.data.errors;
        this.errorMessage = validationErrors
          ? Object.values(validationErrors).flat()[0]
          : 'We could not complete your registration. Please try again.';
      }).finally(() => {
        this.isSubmit = false;
      });
    },
  },
};
</script>
