<template>
  <div class="ps_account text-center">
    <button @click="launchSvcUiUrl()" class="btn btn-primary">
      {{ $t('general.startOnboarding') }}
    </button>
        <b-alert
        variant="warning"
        show
      >
        <h2>{{ $t('general.multiShop.title') }}</h2>
        <p>{{ $t('general.multiShop.subtitle') }}</p>
        <p>{{ $t('general.multiShop.chooseOne') }}</p>
        <b-list-group
          v-for="group in shopsTree"
          :key="group.id"
          class="mt-3 mb-3 col-4"
        >
          <p class="text-muted">
            {{ $t('general.multiShop.group') }} {{ group.name }}
          </p>
          <b-list-group-item
            v-for="shop in group.shops"
            :key="shop.id"
            :href="shop.url"
          >
            {{ $t('general.multiShop.configure') }} <b>{{ shop.name }}</b>
          </b-list-group-item>
        </b-list-group>
        <p>{{ $t('general.multiShop.tips') }}</p>
      </b-alert>
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
