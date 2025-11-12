<template>
  <div>
     <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
          <h3 class="card-title">
            <i class="fas fa-chart-pie mr-1"></i>
            List
          </h3>
        </div> 
         <div class="col-md-6 text-right">
            
         </div>  
        </div> 
      </div><!-- /.card-header -->
      <div class="card-body">
        <div class="tab-content p-0">
          <!-- Morris chart - Sales -->
          <div class="chart tab-pane active" id="revenue-chart">
            <div class="table-responsive">
              <table class="table table-bordered table-hover table-lg">
                <thead>
                  <tr>
                     <th>Active</th>
                    <th>Date Created</th>
                    <th>Restaurant Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Telephone</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Coordinates</th>
                    <th>Store Open</th>
                    <th>Account Verified</th>
                    <th>Pre Order</th>
                    <th>Comm Discount</th>
                    <th>Link</th>
                    <th></th>
                  </tr> 
                </thead>
                <tbody>
                <tr v-if="members.length <=0">
                    <td colspan="11">No record found...</td>
                  </tr>
                  <tr v-for="member in members.data" > 
                    <td>
                       <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" :id="'customSwitch'+member.id" v-model="member.active" v-on:click="updateStatus(member.id, member.active)">
                    <label class="custom-control-label" :for="'customSwitch'+member.id"></label>
                          </div>
                    </td>
                    <td>{{ member.created_at_format }}</td>
                    <td>{{ member.restaurant_name }}</td>
                    <td>{{ member.email }}</td>
                    <td>{{ member.mobile }}</td>
                    <td>{{ member.telephone }}</td>
                    <td>{{ member.address }}</td>
                    <td>{{ member.city }}</td>
                    <td>{{ member.latitude }}, {{ member.longtitude }}</td>
                    <td>
                       <div class="custom-control custom-switch">
                              <input type="checkbox" class="custom-control-input" :id="'customSwitchStoreOpen'+member.id" v-model="member.istoreopen" v-on:click="updateStoreOnline(member.id, member.istoreopen)">
                              <label class="custom-control-label" :for="'customSwitchStoreOpen'+member.id"></label>
                          </div>
                   
                    </td>
                    <td>
                       <div class="custom-control custom-switch">
                              <input type="checkbox" class="custom-control-input" :id="'customSwitchVerified'+member.id" v-model="member.isverified" v-on:click="verify(member.id, member.isverified)">
                              <label class="custom-control-label" :for="'customSwitchVerified'+member.id"></label>
                          </div>
                   
                    </td>
                       <td>
                       <div class="custom-control custom-switch">
                              <input type="checkbox" class="custom-control-input" :id="'customSwitchPreOrder'+member.id" v-model="member.is_pre_order" v-on:click="preorder(member.id, member.is_pre_order)">
                              <label class="custom-control-label" :for="'customSwitchPreOrder'+member.id"></label>
                          </div>
                   
                    </td>

                     <td>
                       <div class="custom-control custom-switch">
                              <input type="checkbox" class="custom-control-input" :id="'customSwitchCoom'+member.id" v-model="member.pahatud_comm_discount" v-on:click="commrate(member.id, member.pahatud_comm_discount)">
                              <label class="custom-control-label" :for="'customSwitchCoom'+member.id"></label>
                          </div>
                   
                    </td>

                     <td>
                      <a :href="member.facebook" target="_new" v-if="member.accout_type">{{ member.accout_type.title }}</a>
                    </td>
                    <td>
                        <a :href="'/merchant/aulogin/'+member.user_id" class="btn btn-xs btn-primary">View</a>
                        <a v-on:click="sendMerchantLogin(member.id)" class="btn btn-xs btn-primary">Send Merchant Login</a>
                    </td>

                  </tr>
                  
                </tbody>

                <tfoot>
                  <tr>
                    <td colspan="15">
                      <pagination-display :data="members" @pagination-change-page="fetchData"></pagination-display>
                    </td>
                  </tr>
                </tfoot>
              </table> 
            </div>
           </div>
        </div>
      </div><!-- /.card-body -->
    </div>


  </div>
</template>
<script>
     export default {
       data() {
            return {
                field: {
                },
                errors: {},
                members: {},
            }
        },
        mounted() {
          this.fetchData();
        },
        methods: {
          fetchData: function(page=1) {
            var self = this;
              axios.get('/api/data/merchant/search/list?api_token='+api_token+'&page='+page).then(function (response) {
                self.members = response.data.members;
              }).catch(function (error) {
                  console.log(error);
              });
          },
          verify: function(memberid, store_open) {

            var self = this;
              axios.post('/api/data/merchant/'+memberid+'/verify/submit?api_token='+api_token).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);

                  }
                  else {
                    toastr.info(response.data.message);
                  }
              }).catch((errors) => {
                   onsole.log(error);
              }); 

          },
          preorder: function(memberid, store_open) {

            var self = this;
              axios.post('/api/data/merchant/'+memberid+'/preorder/submit?api_token='+api_token).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);

                  }
                  else {
                    toastr.info(response.data.message);
                  }
              }).catch((errors) => {
                   onsole.log(error);
              }); 

          },
          updateStatus: function(memberid, store_open) {

            var self = this;
              axios.post('/api/data/merchant/'+memberid+'/status/submit?api_token='+api_token).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);

                  }
                  else {
                    toastr.info(response.data.message);
                  }
              }).catch((errors) => {
                   onsole.log(error);
              }); 

          },
          updateStoreOnline:function(memberid, store_open) {

             var self = this;
              axios.post('/api/data/merchant/'+memberid+'/online/submit?api_token='+api_token).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);
                  }
                  else {
                    toastr.info(response.data.message);
                  }
              }).catch((errors) => {
                   onsole.log(error);
              }); 
          },

          commrate:function(memberid, store_open) {

             var self = this;
              axios.post('/api/data/merchant/'+memberid+'/coomrate/submit?api_token='+api_token).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);
                  }
                  else {
                    toastr.info(response.data.message);
                  }
              }).catch((errors) => {
                   onsole.log(error);
              }); 
          },
          sendMerchantLogin: function (memberid) {

              var self = this;
              axios.post('/api/data/merchant/'+memberid+'/password/submit?api_token='+api_token).then((response) => {
                  if (response.data.status) {
                      toastr.success(response.data.message);
                  }
                  else {
                    toastr.info(response.data.message);
                  }
              }).catch((errors) => {
                   onsole.log(error);
              }); 

          }

          

        }
    }

</script>

