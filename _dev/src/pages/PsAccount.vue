<template>
  <div class="ps_account text-center">
    <button @click="launchSvcUiUrl()" class="btn btn-primary">
      {{ $t('general.startOnboarding') }}
    </button>
  </div>
</template>

<script>
import Vuex from 'vuex'
import store from '../store/index'

export default {
  store,
  name: 'PsAccount',
  created() {
    this.$store.watch(
      (state, getters) => getters.svcUiUrl,
      (newValue, oldValue) => {
        if (null !== newValue) {
          this.connectSvcUi(newValue)
        }
      }
    )
  },

  methods: {
    connectSvcUi(url) {
      window.location.replace(url)
    },
    launchSvcUiUrl() {
      this.$store.dispatch({
        type: 'setSvcUiUrl',
        svcUiDomainName: process.env.VUE_APP_UI_SVC_URL,
        protocolBo: window.location.protocol.slice(0, -1),
        domainNameBo: window.location.host,
      })
    },
  },
}
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
