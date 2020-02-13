<template>
  <div class="app-container">
    test
    <div class="app-header">
      <h2>ps account</h2>
    </div>
    <div class="app-body">
      <PsAccount button-text="valider" />
    </div>
  </div>
</template>

<script>
/* eslint-disable no-console */

  import PsAccount from './pages/PsAccount';

  export default {
    name: 'App',
    components: {
      // eslint-disable-next-line vue/no-unused-components
      PsAccount,
    },
  };
</script>
