<!--**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *-->
<template>
  <b-container class="m-auto p-0" id="config-information">
    <div class="d-flex">
      <b-col sm="4" md="4" lg="4" class="p-16 m-auto text-center">
        <img src="@/assets/prestashop-logo.png" width="300" />
      </b-col>

      <b-col sm="8" md="8" lg="8" class="p-4">
        <h1>{{ $t("configure.incentivePanel.title") }}</h1>
        <section>
          <div v-if="'settings' === app">
            <h2>{{ $t("configure.incentivePanel.howTo") }}</h2>
            <p>
              <span class="material-icons">check_circle</span>
              {{ $t("configure.incentivePanel.connectToPs") }}
            </p>
          </div>
          <!-- For Dashboard App-->
          <div class="dashboard-app" v-if="'dashboard' === app">
            <a @click.prevent="startSetup()" class="btn btn-primary">{{
              $("configure.incentivePanel.link")
            }}</a>
          </div>
        </section>
      </b-col>
    </div>
  </b-container>
</template>

<script>
export default {
  name: "ConfigInformation",
  props: ["app", "configurationPage"],
  methods: {
    startSetup() {
      // this.$segment.track("start setup", { module: "ps_accounts" });
      window.open(this.configurationPage);
    },
  },
};
</script>

<style lang="scss">
#config-information {
  background-color: #fff;

  h1 {
    font-size: 18px;
  }
  h2 {
    font-size: 16px;
  }
  img {
    max-width: 100%;
  }
  .material-icons {
    color: #4cbb6c;
  }
  section {
    margin-left: 30px;
    ul {
      padding-left: 15px;
      color: #6c868e;

      li {
        padding-left: 10px;
        line-height: 26px;
      }
    }
    .dashboard-app {
      text-align: center;

      .btn-primary {
        color: #fff !important;
      }
    }
  }
}
</style>
