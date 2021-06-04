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
    <div class="d-flex col-left">
      <b-col sm="4" md="4" lg="4" class="p-16 m-auto text-center d-flex">
        <img src="@/assets/prestashop-logo.png" width="50" height="50" />
        <h1><span class="white-text">Prestashop</span> Account</h1>
      </b-col>

      <b-col sm="8" md="8" lg="8" class="col-right py-5">
        <section>
            <h2>{{ $t("configure.incentivePanel.howTo") }}</h2>
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
  background-color: #011638;
  border-radius: 10px;

  .col-left > .d-flex {
    @apply items-center justify-center;

    h1 {
      font-size: 16px;
      color: #6B868F;
      @apply font-light my-0 mx-2;

      span.white-text {
        @apply text-white font-bold;
      }
    }

    img {
      max-width: 100%;
    }
  }
  div.col-right {
    padding-left: 16%;
    @apply pr-2;

    > section
    h2 {
      font-size: 22px;
      @apply text-white font-bold mb-0;
    }
  }
}
</style>
