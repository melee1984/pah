<template>
  <div>
    <h1>Hello from Vue 2!</h1>
  </div>
</template>

<script>
export default {
  name: 'App',
  mounted() {
    // runs when the component is mounted to the DOM
    alert('App component loaded');
  }
}
</script>