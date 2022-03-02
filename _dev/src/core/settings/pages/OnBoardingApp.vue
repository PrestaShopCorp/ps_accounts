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
  <div class="pt-5 onboarding">
    <section class="onboarding-header">
      <ConfigInformation :app="app" />
    </section>

    <section class="onboarding-content">
      <prestashop-accounts></prestashop-accounts>
    </section>
  </div>
</template>

<script>
/* eslint-disable */
import ConfigInformation from "@/core/app/components/panel/ConfigInformation";
import { mapSagas } from "@/lib/store-saga";

export default {
  components: {
    ConfigInformation,
  },
  methods: {
    ...mapSagas({
      getListProperty: "getListProperty",
    })
  },
  data() {
    return {
      loading: true,
      unwatch: "",
    };
  },
  mounted() {
    if (window?.psaccountsVue) {
      return window?.psaccountsVue?.init();
    }

    require('prestashop_accounts_vue_components').init();
  },
  created() {
    if (this.googleLinked) {
      this.loading = true;
      this.getListProperty();
    }
    this.unwatch = this.$store.watch(
      (state, getters) => {
        return {
          googleLinked: state.settings.googleLinked,
          countProperty: state.settings.countProperty,
          listProperty: state.settings.state.listPropertySuccess,
        };
      },
      (newVal) => {
        if (
          newVal.googleLinked &&
          Object.keys(newVal.listProperty).length < newVal.countProperty
        ) {
          this.getListProperty();
        }
        if (Object.keys(newVal.listProperty).length >= newVal.countProperty) {
          this.loading = false;
        }
      },
      {
        immediate: true,
      }
    );
  },
  beforeDestroy() {
    this.unwatch();
  },
  computed: {
    app() {
      return this.$store.state.app.app;
    },
    connectedAccount() {
      return this.$store.state.settings.connectedAccount;
    },
  },
};
</script>

<style lang="scss" scoped>
.onboarding {
  @apply max-w-screen-lg mx-auto #{!important};

  &-header {
    @apply mb-2 #{!important};
  }

  @screen md {
    &-header {
      @apply mb-4 #{!important};
    }
  }
}
</style>
