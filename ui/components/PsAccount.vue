<template>
  <div class="ps_account text-center">
    <button 
      @click.stop.prevent="connectSSO()" 
      class="btn btn-primary"
    >
      {{ buttonText }}
    </button>
  </div>
</template>

<script>
export default {
  name: 'PsAccount',
  props: {
    buttonText: String,
  },
  methods: {
    connectSSO() {
      window.open(this.$parent.sso_url, '_blank')
    },
  },
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
h3 {
  margin: 40px 0 0;
}
ul {
  list-style-type: none;
  padding: 0;
}
li {
  display: inline-block;
  margin: 0 10px;
}
a {
  color: #42b983;
}
</style>
