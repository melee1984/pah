<template>
<div class="card mb-3 checkout-box">
  <div class="card-body">
    <div class="row">
      <div class="col-md-10 col-10">
        <h2>Personal Information </h2>
        <p v-if="profileAction == 'view'">
          <strong>{{ customer.firstname }} {{ customer.lastname }}</strong><br>
          {{ customer.email }}<br>
          <abbr title="mobile">M:</abbr> {{ customer.mobile }}

           <div class="text-danger" style="display: inline-flex;" v-if="!mobileNotEmpty"> <span class="material-icons">priority_high</span> The registered mobile no is missing. </div>

        </p>
      </div>
      <div class="col-md-2 col-2 text-right">
        <a href="javascript:void(0)" v-on:click="actionProfile('edit')" class="text-danger action-link" v-if="profileAction=='view'">Edit</a>
        <a href="javascript:void(0)" v-on:click="actionProfile('view')" class="text-danger action-link" v-if="profileAction=='edit'">Cancel</a>
      </div>
    </div>
    <div class="row" v-if="profileAction =='edit'">

      <div class="col-md-12 col-12">
         <div class="form-group mb-1 pb-1">
          <label for="inputAddress" class="mb-1">Email</label>
          <input type="text" class="form-control" placeholder="Mobile No" v-model="customer.email" disabled>
        </div>

        <div class="form-row mb-1 pb-1">
          <div class="form-group col-md-6  mb-0 pb-0">
            <label for="inputEmail4" class="mb-1">First name</label>
            <input type="text" class="form-control" placeholder="First name" v-model="customer.firstname">
          </div>
          <div class="form-group col-md-6 mb-0 pb-0">
            <label for="inputPassword4" class="mb-1">Last name</label>
            <input type="text" class="form-control" placeholder="Last name" v-model="customer.lastname">
          </div>
        </div>
        <div class="form-group mb-1 pb-1">
          <label for="inputAddress" class="mb-1">Mobile no</label>
          <input type="number" class="form-control" placeholder="Mobile No eg. 09162987456" v-model="customer.mobile" v-bind:class="{'border border-danger': !mobileNotEmpty}">
         
        </div>

        <button type="button" class="btn btn-pahatud btn-block btn-sm" v-on:click="submit" v-bind:class="{disabled: !validForm}">Update</button>
      </div>
    </div>
  </div>
</div>
</template>

<script>

    export default {
        props: ['customer'],
        data() {
          return {
              data: {},
              profileAction: 'view',
              user: this.customer,
              messageString:  '',
          }
        },
        mounted() {
          if (this.customer.mobile) {
              this.profileAction = "edit";
              this.messageString = "The registered mobile no is missing.";
          }
        },
        computed: {
            validForm: function() {
              if (!this.customer.email) {
                return false;
              }
              else if (!this.customer.firstname) {
                return false;
              }
              else if (!this.customer.lastname) {
                return false;
              }
               else if (!this.customer.mobile) {
                return false;
              }
              return true;            
            },
            mobileNotEmpty: function() {
              if (!this.customer.mobile) {
                return false;
              }
              return true;  
            },
        },
        methods: {
            editProfile: function() {
              
            },
            actionProfile: function(action) {
              this.profileAction = action;
            },
            submit: function() {
                
                let formData = new FormData();
                formData.append('firstname', this.customer.firstname)
                formData.append('lastname', this.customer.lastname)
                formData.append('mobile', this.customer.mobile)
                axios.post('/api/checkout/profile/update/submit?api_token='+api_token, formData).then((response) => {
                  if (response.data.status) {
                    toastr.success(response.data.message);
                    this.actionProfile('view');
                  }
                  else {
                    toastr.error(response.data.message);
                  }
                }).catch((errors) => {
                    toastr.error(errors);
                }); 

            },
            validate: function() {

            },
            selectPaymentOption: function(payment_id) {
              $('#payment_option .card').removeClass('border-danger active');
              $('#payment_'+payment_id).addClass('border-danger active');
            },
            
        }
    }

</script>
