<template>
  <div class="modal fade" id="modalAddVariant" role="dialog">
    <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4><span class="glyphicon glyphicon-lock"></span> Add Variant Title</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" style="padding:40px 50px;">
        <div class="alert alert-success" v-if="actionSuccess">
            {{ display.message }}
        </div>
        <form method="post" v-on:submit.prevent="submitRecord" v-if="!actionSuccess">
           <div class="form-group" >
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" :id="'active_'+field.active" v-model="field.active">
                <label class="custom-control-label" :for="'active_'+field.active"> Active</label>
              </div>
            </div>
            <div class="form-group" >
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" :id="'required_'+field.required" v-model="field.required">
                <label class="custom-control-label" :for="'required_'+field.required"> Required</label>
              </div>
            </div>
            <div class="form-group" >
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" :id="'required_'+field.is_multiple" v-model="field.is_multiple">
                <label class="custom-control-label" :for="'required_'+field.is_multiple"> Multiple Selection</label>
              </div>
            </div>
            <div class="form-group">
              <input type="text" class="form-control" id="variantTitle" name="variantTitle" placeholder="Enter Title *" required v-model="field.title">
            </div>
            <div class="form-group">
              <input type="number" class="form-control" id="sorting" name="sorting" placeholder="Enter sorting eg. 1" required v-model="field.sorting">
            </div>
            <button type="submit" class="btn btn-pahatud btn-block">
                <span v-if="!isSubmit">Submit</span> <span v-if="isSubmit">Please wait...</span>
            </button> 
          <br/>
        </form>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-default" data-dismiss="modal" v-if="!actionSuccess">Cancel</button>
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
                  title:'',
                  active: true,
                  required: false,
                  sorting: '',
                },
                errors: {},
                isSubmit: false,
                display: {},
                actionSuccess: false,
            }
        },
      
        props: ['productId'],
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
          submitRecord: function() {
                this.isSubmit = true;
                if (this.validateForm()) {
                     axios.post('/api/merchant/product/variant/submit', {
                        product_id: this.productId,
                        title: this.field.title,
                        active: this.field.active,
                        required: this.field.required,
                        sorting: this.field.sorting,
                        is_multiple: this.field.is_multiple,
                      })
                      .then((response) => {
                        if (response.data.status) {
                          toastr.success(response.data.message);
                          this.clearForm();
                          $('#modalAddVariant').modal('toggle');
                          this.$emit('populatedVariant');
                        }
                        else {
                          toastr.info(response.data.message);
                        }

                      }).catch((errors) => {
                          toastr.error(response.data.message);
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
                $('#variantTitle').removeClass('border-danger ding');
               
                if (!this.field.title) {
                  $('#variantTitle').addClass('is-invalid ding');
                  this.errors.push("Title is required.");
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
                this.field.title = "";
                
                this.errors = {};
                this.isSubmit = false;
              },
        }
    }
</script>


