
<template>
 <div class="modal fade" id="myModalLogin" role="dialog">
      <div class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h4><span class="glyphicon glyphicon-lock"></span> Login</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" style="padding:40px 50px;">

          <div class="alert alert-danger" v-if="displayMessage">
              <ul>
                <li>{{ display.message }}</li>
              </ul>
          </div>

         <form method="post" v-on:submit.prevent="submitRecord" v-if="!actionSuccess">
            <div class="form-group">
              <input type="text" class="form-control" name="email" placeholder="Your Email Addresss" required v-model="field.email">
            </div>
            <div class="form-group">
              <input type="password" class="form-control" name="password" placeholder="Enter your Password" required v-model="field.password"> 
            </div>
            <button type="submit" class="btn-block btn-xs food-btn style-2"><span v-if="!isSubmit">Submit</span> <span v-if="isSubmit">Please wait...</span></button> 
            <br/>
            <p>Forgot <a href="/password/reset" style="margin-top:5px;">Password?</a></p>
            <br>
           
            <a href="/login/facebook?url=request-booking" class="btn btn-xs btn-block fa-facebook">
             Login via Facebook
            </a>
            
          </form>

        </div>
        <div class="modal-footer">
          <a href="javascript:void(0)"  data-dismiss="modal" v-if="!actionSuccess">Cancel</a>
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
                  email: '',
                  password: '',
                },
                errors: {},
                isSubmit: false,
                display: {},
                actionSuccess: false,
                displayMessage: false,
            }
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
          submitRecord: function() {

                this.isSubmit = true;

                if (this.validateForm()) {

                    this.display.message = "";
                    this.displayMessage = false;

                     axios.post('/api/login/submit', {
                        email: this.field.email,
                        password: this.field.password,
                        page: page_url,
                      })
                      .then((response) => {
                        if (response.data.status) {
                          this.redirect(response.data.redirectURL);
                        }
                        else {
                          this.display.message = response.data.message;
                          this.displayMessage = true;
                          this.isSubmit = false;
                        }
                      }).catch((errors) => {
                          console.log('There was an error => ', errors);
                          this.isSubmit = false;
                      }); 
                }
                return false;
            },
            redirect: function(url) {
              window.location.href = url;
            },
            validateForm: function () 
            {
                var self = this;
                var i;

                this.errors = [];
              
                $('#email').removeClass('border-danger ding');
                $('#password').removeClass('border-danger ding');
                
                if (!this.field.email) {
                  $('#firstname').addClass('border-danger ding');
                  this.errors.push("Email is required.");
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
                this.field.email = "";
                this.field.password = "";
                $('#myModalLogin').modal('toggle');
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>


