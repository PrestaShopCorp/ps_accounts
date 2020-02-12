<template>
  <div class="app-container">
    <div class="app-header">
      <h2>ps account</h2>
    </div>
    <div class="app-body">
      <ps_account buttonText="valider" />
    </div>
  </div>
</template>

<script>
/* eslint-disable no-console */

  import psAccount from './pages/PsAccount';

  export default {
    name: 'App',
    components: {
      // eslint-disable-next-line vue/no-unused-components
      psAccount,
    },
  };
</script>
