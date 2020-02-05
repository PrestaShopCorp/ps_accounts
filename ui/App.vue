<template>
  <div class="app-container">
    <div class="app-header">
      <h2>ps account</h2>
    </div>
    <div class="app-body">
      <ps_account buttonText="valider"></ps_account>
    </div>
  </div>
</template>

<script>
/* eslint-disable no-console */

import ps_account from './components/PsAccount'

export default {
  name: 'app',
  components: {
    ps_account,
  },
  data() {
    return {
      fact: null,
      sso_url: null,
      loaded: false,
      ready: {
        loading: false,
      },
    }
  },
  created() {
    this.sso_url = process.env.VUE_APP_SSO_URL
  },

}
</script>
