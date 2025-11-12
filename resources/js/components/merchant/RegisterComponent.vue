<template>
<div class="card">
    <div class="card-body register-card-body">
      <p class="login-box-msg">Register a new Merchant Partner</p>
      <div class="alert alert-success" v-if="actionSuccess">
          {{ display.message }}
      </div>
      <form method="post" v-on:submit.prevent="submitRecord" v-if="!actionSuccess">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Restaurant Name/Virtual Kitchen Name" required v-model="field.restaurant_name">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-telephone"></span>
            </div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Facebook URL" required v-model="field.facebook">
        </div>

         <div class="input-group mb-3">
          <select name="accountType" id="accountType" class="form-control" v-model="field.accountType">
              <option value="">Select account type</option>
              <option v-for="type in accounttype" :value="type.id" >{{ type.title }}</option>
          </select>
        </div>

        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Firstname" required v-model="field.firstname">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-telephone"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Lastname" required v-model="field.lastname">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-telephone"></span>
            </div>
          </div>
        </div>
        <hr>
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Telephone" required v-model="field.telephone">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-telephone"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Mobile" required v-model="field.mobile">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-mobile"></span>
            </div>
          </div>
        </div>

        <div class="input-group mb-3">
          <textarea class="form-control" placeholder="Address" required v-model="field.address"></textarea>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-address"></span>
            </div>
          </div>
        </div>
         <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="City" required v-model="field.city">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-city"></span>
            </div>
          </div>
        </div>
        <hr>
        <div class="input-group mb-3">
          <input type="email" class="form-control" placeholder="Email" required v-model="field.email">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Password" required v-model="field.password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="agreeTerms" name="terms" value="agree">
              <label for="agreeTerms">
               I agree to the <a href="javascript:void(0)" v-on:click="IAgreeTerms()">Terms and Conditions</a>
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-pahatud btn-block">Register</button>
          </div>
          <!-- /.col -->
        </div>
      </form>	

      <br/><br/>
      <a href="/merchant/login" class="text-center">I already have a Merchant Account</a>
    </div>
      <modal-display></modal-display>
  </div><!-- /.card -->

 </template>

<script>
    import DisplayComponents from '../merchant/includes/ModalComponents.vue';

     export default {
        components: {
           'modal-display': DisplayComponents,
        },
       data() {
            return {
                field: {
                  restaurant_name: '',
                  firstname:'',
                  lastname:'',
                  telephone: '',
                  mobile: '',
                  address: '',
                  city: '',
                  email: '',
                  password: '',
                  facebook: '',
                  accountType: '',
                },
                errors: {},
                isSubmit: false,
                display: {},
                actionSuccess: false,
            }
        },
        props: ['accounttype'],
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
          IAgreeTerms: function() {
            $('#modalGeneral').modal();
          },
          submitRecord: function() {

                this.isSubmit = true;

                if (this.validateForm()) {

                     axios.post('/api/merchant/register/submit', {
                        restaurant_name: this.field.restaurant_name,
                        firstname: this.field.firstname,
                        lastname: this.field.lastname,
                        telephone: this.field.telephone,
                        mobile: this.field.mobile,
                        address: this.field.address,
                        city: this.field.city,
                        email: this.field.email,
                        password: this.field.password,
                        facebook: this.field.facebook,
                        accountType: this.field.accountType,

                      }).then((response) => {

                        if (response.data.status) {
                          toastr.success(response.data.message);
                          this.clearForm();
                        }
                        else {
                          toastr.info(response.data.message);
                        }
                      }).catch((errors) => {
                          toastr.error(errors);
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
                $('#firstname').removeClass('border-danger ding');
                $('#lastname').removeClass('border-danger ding');
                $('#mobile').removeClass('border-danger ding');
                $('#telephone').removeClass('border-danger ding');
                $('#address').removeClass('border-danger ding');
                $('#city').removeClass('border-danger ding');
                $('#email').removeClass('border-danger ding');
                $('#password').removeClass('border-danger ding');
                $('#password').removeClass('border-danger ding');
                $('#accountType').removeClass('border-danger ding');
                  
                if (!this.field.restaurant_name) {
                  $('#firstname').addClass('border-danger ding');
                  this.errors.push("Firstname is required.");
                }
                if (this.field.accountType=="") {
                  $('#accountType').addClass('border-danger ding');
                  this.errors.push("Account Type is required.");
                }
                if (!this.field.firstname) {
                  $('#firstname').addClass('border-danger ding');
                  this.errors.push("Firstname is required.");
                }
                if (!this.field.lastname) {
                  $('#lastname').addClass('border-danger ding');
                  this.errors.push("Lastname is required.");
                }
                if (!this.field.mobile) {
                  $('#mobile').addClass('border-danger ding');
                  this.errors.push("Mobile is required.");
                }
                if (!this.field.telephone) {
                  $('#telephone').addClass('border-danger ding');
                  this.errors.push("Telephone is required.");
                }
                if (!this.field.address) {
                  $('#address').addClass('border-danger ding');
                  this.errors.push("Address is required.");
                }
                if (!this.field.city) {
                  $('#address').addClass('border-danger ding');
                  this.errors.push("Address is required.");
                }
                if (!this.field.email) {
                  $('#email').addClass('border-danger ding');
                  this.errors.push("Email Address is required.");
                }
                if (!this.field.password) {
                  $('#password').addClass('border-danger ding');
                  this.errors.push("Password is required.");
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
                this.field.firstname = "";
                this.field.lastname = "";
                this.field.mobile = "";
                this.field.telephone = "";
                this.field.address = "";
                this.field.city = "";
                this.field.email = "";
                this.field.password = "";
                this.field.facebook = "";
                this.field.accountType = "";
                
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>
