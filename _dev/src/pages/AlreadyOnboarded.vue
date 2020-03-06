<template>
  <div class="ps_account text-center">
    <h1>{{ this.tplName }}</h1>
    <button @click="resetOnboarding()" class="btn btn-primary">
      {{ $t('general.restartOnboarding') }}
    </button>
  </div>
</template>

<script>
import axios from 'axios'
import Vuex from 'vuex'

export default {
  name: 'AlreadyOnboarded',
  computed: {
    ...Vuex.mapGetters(['resetOnboardingUrl', 'tplName']),
  },
  methods: {
    resetOnboarding() {
      const form = new FormData()
      form.append('ajax', true)
      form.append('action', 'ResetOnboarding')
      form.append('controller', 'AdminAjaxPsAccounts')
      Object.entries([]).forEach(([key, value]) => {
        form.append(key, value)
      })

      return axios
        .post(this.resetOnboardingUrl, form)
        .then(res => {
          window.location.reload()

          return res.data
        })
        .catch(error => {
          // eslint-disable-next-line no-console
          console.log(error)
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
