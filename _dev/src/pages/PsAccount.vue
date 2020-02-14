<template>
  <div>
    <div
      v-if="isParamsLoaded"
      class="ps_account text-center"
    >
      <button
        @click.stop.prevent="connectSvcUi()"
        class="btn btn-primary"
      >
        {{ buttonText }}
      </button>
    </div>
    <div
      v-else
      class="forbidden text-center"
    >
      Restart onboarding
    </div>
  </div>
</template>
<script>
  import Vuex from 'vuex';

  /* eslint-disable no-console */
  export default {
    name: 'PsAccount',
    props: {
      buttonText: {
        type: String,
        default: '',
      },
    },
    computed: {
      ...Vuex.mapGetters([
        'getSvcUiUrl',
        'isParamsLoaded',
      ]),
    },
    mounted() {
      this.setSvcUiUrl({
        svcUiDomainName: process.env.VUE_APP_SSO_URL,
        protocolBo: window.location.protocol.slice(0, -1),
        domainNameBo: window.location.host,
      });
    },
    methods: {
      ...Vuex.mapActions([
        'setSvcUiUrl',
      ]),
      connectSvcUi() {
        window.location.replace(this.getSvcUiUrl);
      },
    },
  };
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
