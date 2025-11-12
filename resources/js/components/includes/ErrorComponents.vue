<p v-if="errors.length" class="alert alert-danger">
<b>Please correct the following error(s):</b>
<ul>
  <li v-for="error in errors">{{ error }}</li>
</ul>
</p>