<template>
  <div class="row">
       <div class="col-lg-2 col-6">
        <!-- small box -->
        <div class="small-box bg-info">
          <div class="inner">
            <h3>{{ record.pendingOrder }}</h3>
            <p>Pending Orders</p>
          </div>
          <div class="icon">
            <i class="ion ion-bag"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
      <div class="col-lg-2 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
          <div class="inner">
            <h3>{{ record.onGoingOrder }}</h3>
            <p>On Going Deliveries</p>
          </div>
          <div class="icon">
            <i class="ion ion-stats-bars"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
      <div class="col-lg-2 col-6">
        <!-- small box -->
        <div class="small-box bg-warning">
          <div class="inner">
            <h3>{{ record.completed }}</h3>
            <p>Completed</p>
          </div>
          <div class="icon">
            <i class="ion ion-person-add"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
      <div class="col-lg-2 col-6">
        <!-- small box -->
        <div class="small-box bg-danger">
          <div class="inner">
            <h3>{{ record.cancelled }}</h3>
            <p>Cancelled</p>
          </div>
          <div class="icon">
            <i class="ion ion-pie-graph"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-4 col-6">
        <!-- small box -->
        <div class="small-box bg-secondary">
          <div class="inner">
            <h3>{{ record.salesToday }}</h3>
            <p>Sales Today</p>
          </div>
          <div class="icon">
            <i class="ion ion-pie-graph"></i>
          </div>
          <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <!-- ./col -->
    </div>
</template>
<script>

    export default {
      data() {
            return {
              record: {
                pendingOrder:0,
                onGoingOrder:0,
                completed:0,
                cancelled:0,
                salesToday:0.00,
              },
            }
        },
        created() {
          Event.$on('reloadMerchantOrderSummary', () => {
             this.fetchData();
          });
        },
        mounted() {
          this.fetchData();
        },
        methods: {
          fetchData: function() {
              var self = this;
              axios.get('/api/merchant/dashboard/order/summary?api_token='+api_token).then(function (response) {
                self.record = response.data.record;
              })
              .catch(function (error) {
                  console.log(error);
              });
          }
        }
    }
</script>


