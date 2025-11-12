<template>
  <div>
     <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
          <h3 class="card-title">
            <i class="fas fa-chart-pie mr-1"></i>
            Sales Report
          </h3>
        </div> 
         <div class="col-md-6 text-right">
            
         </div>  
        </div> 
      </div><!-- /.card-header -->
      <div class="card-body">
        <div class="tab-content p-0">
           <!-- Date and time range -->
           <div class="form-group">
           <div class="row">
              <div class="col-md-6">
                <label>Merchant</label>
                <select name="" id="" class="form-control" v-model="merchant"> 
                  <option value="">Select Merchant</option>
                  <option v-for="partner in partners" :value="partner.id">{{ partner.restaurant_name }}</option>
                </select>
              </div>
           </div>
          </div>
          <div class="form-group">
           <div class="row">
              <div class="col-md-12">
                <label>Date and time range:</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                  </div>
                  <input type="text" class="form-control float-right" id="reservationtime">

                  <div class="input-group-prepend">
                    <span class="input-group-text">
                      <a href="javascript:void(0)" v-on:click="searchSubmit">Submit</a>
                    </span>
                  </div>

                   <div class="input-group-prepend">
                    <span class="input-group-text">
                      <a href="javascript:void(0)" v-on:click="searchToday">Today</a>
                    </span>
                  </div>

                </div>
              </div>
           </div>
          </div>
          <!-- Morris chart - Sales -->
          <div class="chart tab-pane active" id="revenue-chart">
            <div class="table-responsive">
              <table class="table table-bordered table-hover table-lg">
                <thead>
                  <tr>
                    <th>Date/Time</th>
                    <th>Merchant</th>
                    <th>Order Info</th>
                    <th nowrap="">Qty</th>
                    <th nowrap="" class="bg-light">Sub Total</th>
                    <th nowrap="">Delivery Fee</th>
                    <th>Discount</th>
                    <th class="bg-light">Total</th>

                    <th class="bg-light">15% comm</th>
                    <th class="bg-light">Net</th>
                    <th>Rider</th>
                    <th>Status</th>
                  </tr> 
                </thead>
                <tbody>
                  <tr v-if="orders.length <=0">
                    <td colspan="12">No record found...</td>
                  </tr>

                  <tr v-for="order in orders" > 
                    <td width="160px;">{{ order.submitted_date }}</td>
                    <td width="160px;">{{ order.partner.restaurant_name }}</td>
                    <td><a href="javascript:void(0)" class="" v-on:click="displayOrderDetails(order)"><strong>Order # {{  order.cart.order_no }}  </strong></a><br>
                      Customer: {{ order.cart.fullname }}
                    </td>
                    
                    <td width="50px;">{{ order.summary.qty }}</td>
                    <td width="100px;" nowrap="" class="bg-light">{{ order.summary.sub_total }}</td>
                    <td width="100px;" nowrap="">{{ order.summary.delivery_fee }}</td>

                    <td width="100px;" nowrap="">
                      <span v-if="order.summary.discount_amount>0">
                        {{ order.summary.discount_amount }}
                      </span>
                       <span v-else>
                        -
                      </span>
                    </td>
                    <td width="100px;" nowrap="" class="bg-light">{{ order.summary.total }}</td>
                    <td width="100px;" nowrap="" class="bg-light">{{ order.summary.total_comm }}</td>
                    <td width="100px;" nowrap="">{{ order.summary.total - order.summary.total_comm }} </td>

                     <td width="10%">
                      <p v-if="order.rider">{{ order.rider.name }}</p>
                      <p v-else>-</p>
                    </td>
                    <td width="10%">
                       <span v-if="order.status">
                          {{order.status.title}}                        
                       </span>
                    </td>
                  </tr>
                </tbody>

                <tfoot>
                  <tr>
                    <td colspan="3"></td>
                    <td>{{ summary.qty }}</td>
                    <td>{{ summary.sub_total }}</td>
                    <td>{{ summary.fee }}</td>
                    <td>{{ summary.discount }}</td>
                    <td class="bg-light">{{ summary.total }}</td>
                    <td class="bg-light"> {{ summary.total_comm }}</td>
                    <td class="bg-light"> {{ summary.total_net }} </td>
                    <td colspan="2"></td>
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
                orders: {},
                riders: {},
                selectedOrder: {},
                statuses: {},
                summary: {
                  qty:0,
                  subTotal: 0,
                  discount: 0,
                  fee: 0,
                  total:0,
                },
                partners: {},
                merchant: "",
            }
        },
        mounted() {
              this.searchToday();
        },
        methods: {
        
          displayOrderDetails: function(order) {
              this.selectedOrder = order;
              $('#orderDetails').modal('toggle');
          },
          searchSubmit: function() {
            var self = this;
              axios.post('/api/data/order/search/list?api_token='+api_token, {
                dateFilter: $('#reservationtime').val(),
                merchant: this.merchant,
              }).then(function (response) {
                self.orders = response.data.orders;
                self.summary = response.data.totalSummary;
                self.partners = response.data.partners;
              }).catch(function (error) {
                  console.log(error);
              });
          },
          searchToday: function() {
            var self = this;
              axios.post('/api/data/order/search/list?api_token='+api_token, {
                merchant: this.merchant
              }).then(function (response) {
                self.orders = response.data.orders;
                self.summary = response.data.totalSummary;
                self.partners = response.data.partners;
              }).catch(function (error) {
                  console.log(error);
              });
          },
        }
    }

</script>

