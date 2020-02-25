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
  export default {
    name: 'PsAccount',
    props: {
      buttonText: {
        type: String,
        default: '',
      },
    },
    computed: {
      getSvcUiUrl() {
        return this.$store.state.psaccounts.svcUiUrl;
      },
      isParamsLoaded() {
        return this.$store.state.psaccounts.paramsLoaded;
      },
    },
    mounted() {
      this.$store.dispatch({
        type: 'setSvcUiUrl',
        svcUiDomainName: process.env.VUE_APP_UI_SVC_URL,
        protocolBo: window.location.protocol.slice(0, -1),
        domainNameBo: window.location.host,
      });
    },
    methods: {
      connectSvcUi() {
        window.location.replace(this.getSvcUiUrl);
      },
    },
  };
</script>

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
