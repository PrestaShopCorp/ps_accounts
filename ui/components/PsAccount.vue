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
    console.log(this.svc_ui_url)
    console.log(window.location.href.split('://'))


      this.svc_ui_url = this.$parent.sso_url + '/link-shop/'+ window.protocolDomainToValidate + '/' + window.domainNamDomainToValidate +  '/'+ window.location.protocol + '/' + window.location.host +  encodeURIComponent('/to/PSXEmoji.Deluxe.Fake.Service')
    this.queryParams.pubKey =
      'string' === typeof window.pubKey
        ? encodeURIComponent(window.pubKey)
        : null
    this.queryParams.name =
      'string' === typeof window.shopName
        ? encodeURIComponent(window.shopName)
        : null
    this.queryParams.bo =
      'string' === typeof window.location.pathname || 'string' === typeof window.location.search
        ? encodeURIComponent(window.location.pathname + window.location.search)
        : null
    this.queryParams.next =
      'string' === typeof window.nextStep
        ? encodeURIComponent(window.nextStep)
        : null
    this.queryParamsLoaded()
  },

  methods: {
    connectSvcUi() {
      window.location.assign(this.svc_ui_url)
    },

    getSvcUiUrl() {
      return this.svc_ui_url
    },

    getLoaded() {
      return this.params_loaded
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
