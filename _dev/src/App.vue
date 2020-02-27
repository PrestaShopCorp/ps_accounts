<template>
  <div class="app-container">
    <div class="app-header">
      <h2>ps account</h2>
    </div>
    <div class="app-body">
      <PsAccount button-text="Start onboarding" />
    </div>
  </div>
</template>

<script>
  import PsAccount from './pages/PsAccount';

  export default {
    name: 'App',
    components: {
      PsAccount,
    },
  };
</script>
