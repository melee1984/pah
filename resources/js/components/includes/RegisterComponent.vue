<template>
 <div class="modal fade" id="myModalRegister" role="dialog">
      <div class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h4><span class="glyphicon glyphicon-lock"></span> Register</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" style="padding:40px 50px;">

          <div class="alert alert-success" v-if="actionSuccess">
              {{ display.message }}
          </div>

         <form method="post" v-on:submit.prevent="submitRecord" v-if="!actionSuccess">
            <div class="form-group">
              <input type="text" class="form-control" id="firstname" name="firstname" placeholder="Enter firstname *" required v-model="field.firstname">
            </div>
            <div class="form-group">
              <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Enter lastname *" required v-model="field.lastname">
            </div>
            <div class="form-group">
              <input type="number" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile no eg. 09161236547*" required v-model="field.mobile">
            </div>
            <div class="form-group">
              <input type="text" class="form-control" id="email" name="email" placeholder="Enter email address *" required v-model="field.email">
            </div>
            <div class="form-group">
              <input type="text" class="form-control" id="password" name="password" placeholder="Enter your password *" required v-model="field.password"> 
            </div>
          
            <button type="submit" class="btn-block btn-xs food-btn style-2"><span v-if="!isSubmit">Submit</span> <span v-if="isSubmit">Please wait...</span></button> 

            register with
            <a href="/login/facebook?url=request-booking" class="btn btn-xs btn-block fa-facebook">
             Login via Facebook
            </a>
          </form>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger btn-default" data-dismiss="modal" v-if="!actionSuccess">Cancel</button>
        </div>
      </div>
      </div>
    </div>
</template>
<script>
    export default {
      data() {
            return {
                field: {
                  firstname:'',
                  lastname:'',
                  mobile: '',
                  email: '',
                  password: '',
                },
                errors: {},
                isSubmit: false,
                display: {},
                actionSuccess: false,
            }
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
          submitRecord: function() {

                this.isSubmit = true;

                if (this.validateForm()) {
                     axios.post('/api/register/submit', {
                        firstname: this.field.firstname,
                        lastname: this.field.lastname,
                        email: this.field.email,
                        mobile: this.field.mobile,
                        password: this.field.password
                      })
                      .then((response) => {
                        if (response.data.status) {
                          this.display = response.data;
                          this.actionSuccess = true;
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

                $('#firstname').removeClass('border-danger ding');
                $('#lastname').removeClass('border-danger ding');
                $('#mobile').removeClass('border-danger ding');
                $('#email').removeClass('border-danger ding');
                $('#password').removeClass('border-danger ding');
                

                if (!this.field.firstname) {
                  $('#firstname').addClass('border-danger ding');
                  this.errors.push("Firstname is required.");
                }

                if (!this.field.lastname) {
                  $('#lastname').addClass('border-danger ding');
                  this.errors.push("Lastname is required.");
                }

                if (!this.field.email) {
                  $('#email').addClass('border-danger ding');
                  this.errors.push("Email Address is required.");
                }

                if (!this.field.mobile) {
                  $('#mobile').addClass('border-danger ding');
                  this.errors.push("Mobile is required.");
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
                this.field.firstname = "";
                this.field.lastname = "";
                this.field.email = "";
                this.field.mobile = "";
                this.field.password = "";
                
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>


