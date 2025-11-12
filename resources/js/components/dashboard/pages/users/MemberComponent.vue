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
                    <th>Date Joined</th>
                    <th>Fullname</th>
                    <th>Email</th>
                    <th>Mobile</th>
<!--                     <th></th>
 -->                  </tr> 
                </thead>
                <tbody>
                <tr v-if="members.length <=0">
                    <td colspan="4">No record found...</td>
                  </tr>

                  <tr v-for="member in members.data" > 
                    <td>{{ member.created_at_format }}</td>
                    <td>{{ member.firstname }} {{ member.lastname }}</td>
                    <td>{{ member.mobile }}</td>
                    <td>{{ member.email }}</td>
                  </tr>
                  
                </tbody>

                <tfoot>
                  <tr>
                   
                    <td colspan="4">
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
              axios.get('/api/data/member/search/list?api_token='+api_token+'&page='+page).then(function (response) {
                self.members = response.data.members;
              }).catch(function (error) {
                  console.log(error);
              });
          },
        }
    }

</script>

