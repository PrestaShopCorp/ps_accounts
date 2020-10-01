<template>
  <div id="app">
    <PsAccounts :force-show-plans="true" />
  </div>
</template>

<script>
import { PsAccounts } from "prestashop_accounts_vue_components";

export default {
  name: "App",
  components: {
    PsAccounts,
  },
};
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
</style>
