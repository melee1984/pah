<template>
  <!-- Modal HTML -->
  <div id="displayModal" class="modal fade">
    <div class="modal-dialog modal-confirm">
      <div class="modal-content">
        <div class="modal-header justify-content-center" v-bind:class="display.type">
          
          <div class="icon-box">
            <i class="material-icons" v-if="display.type==='error'">&#xE85A;</i> <!-- error --> 
            <i class="material-icons" v-if="display.type==='success'">&#xE876;</i> <!-- success -->
          </div>

          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        </div>
        <div class="modal-body text-center">
          <h4>{{ display.title }} </h4> 
          <p>{{ display.message }}</p>
          <button class="btn btn-success" data-dismiss="modal"><span>Close</span> <i class="material-icons">&#xE5C8;</i></button>
        </div>
      </div>
    </div>
  </div>     

</template>

<script>
    export default {
      props : ['display'],
      data() {
        return {
          message : '',
          title : '',
          type : '',
        }
      },
      mounted() {
       
      }
    }
</script>

