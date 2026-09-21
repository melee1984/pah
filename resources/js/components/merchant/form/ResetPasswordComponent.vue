<template>
   <div>
     <form method="post" v-on:submit.prevent="submitRecord" v-if="!actionSuccess">
     <div class="input-group mb-3">
        <input type="email" class="form-control" placeholder="Enter email address" name="email" required v-model="field.email">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-envelope"></span>
          </div>
        </div>
      </div>
      <turnstile-widget ref="turnstile" v-model="turnstileToken" action="merchant_password_reset" />
      <p class="text-danger" v-if="turnstileError" role="alert">{{ turnstileError }}</p>
       <div class="row">
         <!-- /.col -->
        <div class="col-12">
          <button type="submit" class="btn btn-pahatud btn-block">Reset Password</button>
        </div>
        <!-- /.col -->
      </div>

      </form>
   </div>
</template>

<script>
     import TurnstileWidget from '../../TurnstileWidget.vue';

     export default {
       components: {
         TurnstileWidget,
       },
        
       data() {
            return {
                field: {
                  email: '',
                },
                errors: {},
                isSubmit: false,
                display: {},
                actionSuccess: false,
                turnstileToken: '',
                turnstileError: '',
            }
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
          IAgreeTerms: function() {
            $('#modalGeneral').modal();
          },
          submitRecord: function() {

                this.turnstileError = '';

                if (document.querySelector('meta[name="turnstile-site-key"]') && !this.turnstileToken) {
                  this.turnstileError = 'Please complete the security verification.';
                  return false;
                }

                this.isSubmit = true;

                if (this.validateForm()) {
                     axios.post('/api/merchant/forgot/submit', {
                        
                        email: this.field.email,
                        'cf-turnstile-response': this.turnstileToken,
                       
                      }).then((response) => {
                        if (response.data.status) {
                          toastr.success(response.data.message);
                          this.clearForm();
                          this.$refs.turnstile && this.$refs.turnstile.reset();
                        }
                        else {
                          toastr.info(response.data.message);
                          this.$refs.turnstile && this.$refs.turnstile.reset();
                        }
                      }).catch((errors) => {
                          toastr.error(errors);
                          this.isSubmit = false;
                          this.$refs.turnstile && this.$refs.turnstile.reset();
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
                $('#email').removeClass('border-danger ding');
               
                if (!this.field.email) {
                  $('#email').addClass('border-danger ding');
                  this.errors.push("Email Address is required.");
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
              
                this.field.email = "";
              
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>
