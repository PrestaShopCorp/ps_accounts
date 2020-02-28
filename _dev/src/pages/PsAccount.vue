<template>
  <div>
    <div
      v-if="isParamsLoaded"
      class="ps_account text-center"
    >
      <button
        @click="connectSvcUi()"
        class="btn btn-primary"
      >
        {{ $t('general.startOnboarding') }}
      </button>
    </div>
    <div
      v-else
      class="forbidden text-center"
    >
      {{ $t('general.restartOnboarding') }}
    </div>
  </div>
</template>

<script>
  import Vuex from 'vuex';
  import store from '../store/index';

  export default {
    store,
    name: 'PsAccount',
    computed: {
      ...Vuex.mapGetters([
        'getSvcUiUrl',
        'isParamsLoaded',
      ]),
    },
    mounted() {
      this.tata();
    },
    methods: {
      ...Vuex.mapActions([
        'setSvcUiUrl',
      ]),
      connectSvcUi() {
        window.location.replace(this.getSvcUiUrl);
      },
      tata() {
        this.setSvcUiUrl({
          svcUiDomainName: process.env.VUE_APP_UI_SVC_URL,
          protocolBo: window.location.protocol.slice(0, -1),
          domainNameBo: window.location.host,
        });
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
