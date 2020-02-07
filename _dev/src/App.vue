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

  import psAccount from './components/PsAccount';

  export default {
    name: 'app',
    components: {
      // eslint-disable-next-line vue/no-unused-components
      psAccount,
    },
  };
</script>
