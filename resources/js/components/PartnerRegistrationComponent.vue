<template>
    <div>
         <form method="post" v-on:submit.prevent="submitRecord">
            <input type="text" id="restaurant_name" name="restaurant_name" placeholder="Restaurant Name*"  required v-model="field.restaurant_name">
            <input type="email" id="email" name="email" placeholder="Your email*"  v-model="field.email" required>
            <input type="text" id="contact" name="contact" placeholder="Contact No.*"  v-model="field.contact" required>
            <textarea name="address" id="address" rows="6" placeholder="Address Location" v-model="field.address" required></textarea>
            <input type="text" name="city" id="city" placeholder="City.*"  v-model="field.city" required>
            <button type="submit" class="food-btn style-2"><span v-if="!isSubmit">SEND</span> <span v-if="isSubmit">Please wait...</span></button> 
        </form>
        <modal-components 
          v-bind:display="display">
        </modal-components>
    </div>
</template>
<script>
    
    import DisplayComponents from './includes/ModalComponents.vue';

    export default {
        components: {
           'modal-components': DisplayComponents,
        },
        data() {
            return {
                field: {
                  restaurant_name: '',
                  email: '',
                  contact: '',
                  address: '',
                  city: '',
                },
                errors: {},
                isSubmit: false,
                display: {},
                
                
            }
        },
        created() {
          console.log('SAMPLE');
        },
        methods: {
            popup: function() {
              $('#displayModal').modal();
            },
            submitRecord: function() {

                this.isSubmit = true;

                if (this.validateForm()) {
                     axios.post('api/partner/submit', {
                        restaurant_name: this.field.restaurant_name,
                        email: this.field.email,
                        contact_no: this.field.contact,
                        address: this.field.address,
                        city: this.field.city
                      })
                      .then((response) => {
                        if (response.data.status) {
                          
                          this.display = response.data;
                          $('#displayModal').modal();

                          this.clearForm();
                        }
                        else {

                        }
                      }).catch((errors) => {
                          console.log('There was an error => ', errors);
                          this.isSubmit = false;
                      }); 
                }
                return false;
            },
            validateForm: function () 
            {
                var self = this;
                var i;

                this.errors = [];
              
                // $('.is-invalid').removeClass('border-danger ding');

                $('#restaurant_name').removeClass('border-danger ding');
                $('#email').removeClass('border-danger ding');
                $('#contact').removeClass('border-danger ding');
                $('#address').removeClass('border-danger ding');
                $('#city').removeClass('border-danger ding');

                if (!this.field.restaurant_name) {
                  $('#restaurant_name').addClass('border-danger ding');
                  // this.errors.push("Restaurant Name is required.");
                }

                if (!this.field.email) {
                  $('#email').addClass('border-danger ding');
                  this.errors.push("Email Address is required.");
                }

                if (!this.field.contact) {
                  $('#contact').addClass('border-danger ding');
                  this.errors.push("Mobile is required.");
                }

                if (!this.field.address) {
                  $('#address').addClass('border-danger ding');
                  this.errors.push("Address is required.");
                }

                if (!this.field.city) {
                  $('#city').addClass('border-danger ding');
                  this.errors.push("City is required.");
                }

                
                if (!this.errors.length) {
                  return true;
                } 
                else {
                  return false;
                }

              },
              clearForm: function() 
              {
                this.field.restaurant_name = "";
                this.field.email = "";
                this.field.contact = "";
                this.field.address = "";
                this.field.city = "";
                
                this.errors = {};
                this.isSubmit = false;
              },

        }
   }
</script>