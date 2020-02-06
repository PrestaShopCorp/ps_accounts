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
}
</script>
