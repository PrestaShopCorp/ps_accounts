<template>
  <div class="app-container">
    <div class="app-header">
      <h2>ps account</h2>
    </div>
    <div class="app-body">
      <AlreadyOnboarded  v-if="lock" />
      <PsAccount v-else />
    </div>
  </div>
</template>

<script>
  import Vuex from 'vuex';
  import store from './store/index';

  import PsAccount from './pages/PsAccount';
  import AlreadyOnboarded from './pages/AlreadyOnboarded';

  export default {
    store,
    name: 'App',
    components: {
      PsAccount,
      AlreadyOnboarded,
    },
    created() {
      if (this.onboardingStarted === true) {
        this.lock = true;
      }
    },
    computed: {
      ...Vuex.mapGetters([
        'onboardingStarted',
      ]),
    },
    data() {
      return {
        lock: false,
      };
    },
  };
</script>
