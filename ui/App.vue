<template>
  <div class="app-container">
    <div class="app-header">
      <h2>ps account</h2>
    </div>
    <div class="app-body">
      <div v-if="loaded">
        <p v-html="fact"></p>
      </div>
      <div v-else>
        <p>
          loaded
        </p>
      </div>
    </div>
    <div class="app-footer">
      <ps_account buttonText="valider"></ps_account>
    </div>
  </div>
</template>

<script>
/* eslint-disable no-console */

import ps_account from './components/PsAccount'
import { request } from './api/ajax.js'

export default {
  name: 'app',
  components: {
    ps_account,
  },
  data() {
    return {
      fact: null,
      api_url: null,
      sso_url: null,
      loaded: false,
      ready: {
        loading: false,
      },
    }
  },
  coed: {},
  watch: {
    ready: {
      deep: true,
      handler: 'isFullLoaded',
    },
  },
  methods: {
    showChunk(data) {
      this.fact = data.value
    },

    ajaxProcessStandAliveUserConnexion() {
      request({
        action: 'StandAliveUserConnexion',
        controller: 'AdminAjaxPsAccounts',
      })
        .then(response => {
          console.log(response.data)
          if (response !== false && response.state != 0) {
            this.showChunk(response)
          }
        })
        .catch(error => {
          console.log(error)
        })
    },

    loadingCompleted() {
      this.ready.loading = true
    },

    isFullLoaded() {
      if (this.ready.loading) {
        this.loaded = true
      }
    },
  },
  created() {
    this.sso_url = process.env.VUE_APP_SSO_URL
    this.api_url = process.env.VUE_APP_API_URL
    window.setTimeout(this.loadingCompleted, 2000)
  },
  mounted() {
    this.ajaxProcessStandAliveUserConnexion()
    window.setInterval(() => {
      this.ajaxProcessStandAliveUserConnexion()
    }, 5000)
  },
}
</script>

<style>
body {
  background: #eff1f2 !important;
}
#app {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
.app-container {
  width: 70%;
  margin: 0 auto;
  display: block;
  margin-bottom: 10px;
  margin-bottom: 0.625rem;
  background-color: #fff;
  border: 1px solid #dbe6e9;
  border-radius: 5px;
  box-shadow: 0 0 4px 0 rgba(0, 0, 0, 0.06);
  font-family: 'Open Sans', Helvetica, Arial, sans-serif;
}
.app-header,
.app-footer {
  background-color: #fafbfc;
  font-weight: 600;
  color: #363a41;
  padding: 10px;
}
.app-header {
  border-bottom: 1px solid #dbe6e9;
}
.app-header i {
  vertical-align: text-bottom;
  color: #6c868e;
  margin-right: 5px;
}
.app-body {
  padding: 50px 30px 30px;
}
.app-body label {
  font-weight: normal;
  font-size: 14px;
}
.app-footer {
  border-top: 1px solid #dbe6e9;
  text-align: right;
}
.validate {
  margin-right: 20px;
}
.app-container img {
  max-width: 100%;
}
</style>
