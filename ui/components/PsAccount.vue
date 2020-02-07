<template>
  <div>
    <div v-if="params_loaded" class="ps_account text-center">
      <button @click.stop.prevent="connectSvcUi()" class="btn btn-primary">
        {{ buttonText }}
      </button>
    </div>
    <div v-else class="forbidden text-center">
      Restart onboarding
    </div>
  </div>
</template>
<script>
/* eslint-disable no-console */
export default {
  name: 'PsAccount',
  props: {
    buttonText: String,
    vue_app_svc_ui_url: String,
  },
  data() {
    return {
      svc_ui_url: null,
      params_loaded: false,
      queryParams: {
        pubKey: null,
        name: null,
        bo: null,
        next: null,
      },
    }
  },
  mounted() {
    this.getSvcUiUrl(
      process.env.VUE_APP_SSO_URL,
      window.protocolDomainToValidate,
      window.domainNameDomainToValidate,
      window.location.protocol.slice(0, -1),
      window.location.host,
      window.queryParams,
      window.pubKey,
      window.shopName,
      '2'
    )
  },
  methods: {
    connectSvcUi() {
      window.location.replace(this.svc_ui_url)
    },
    getSvcUiUrl(
      sso_url,
      protocolDomainToValidate,
      domainNameDomainToValidate,
      protocolBo,
      domainNameBo,
      queryParams,
      pubKey,
      shopName,
      nextStep
    ) {
      this.svc_ui_url =
        sso_url +
        '/link-shop/' +
        protocolDomainToValidate +
        '/' +
        domainNameDomainToValidate +
        '/' +
        protocolBo +
        '/' +
        domainNameBo +
        '/PSXEmoji.Deluxe.Fake.Service'

      let boPath = ''
      for (let [key, value] of Object.entries(queryParams)) {
        boPath += key + '=' + value + '&'
      }

      this.queryParams.bo =
        'string' === typeof boPath
          ? encodeURIComponent(boPath.slice(0, -1))
          : null
      this.queryParams.pubKey =
        'string' === typeof pubKey ? encodeURIComponent(pubKey) : null
      this.queryParams.name =
        'string' === typeof window.shopName
          ? encodeURIComponent(window.shopName)
          : null
      this.queryParams.next =
        'string' === typeof nextStep ? encodeURIComponent(nextStep) : null
      this.queryParamsLoaded()

      return this.svc_ui_url
    },

    queryParamsLoaded() {
      const countInitQueryParams = Object.keys(this.queryParams).length
      let counterValideParams = 0
      this.svc_ui_url += '?'
      for (let [key, value] of Object.entries(this.queryParams)) {
        if (null !== value) {
          counterValideParams++
          this.svc_ui_url += `${key}=${value}&`
        }
      }

      if (countInitQueryParams === counterValideParams) {
        this.params_loaded = true
        this.svc_ui_url = this.svc_ui_url.slice(0, -1)
      }
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
