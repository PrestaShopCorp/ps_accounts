<!--**
 * 2007-2022 PrestaShop and Contributors
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
 * @copyright 2007-2022 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *-->
<template>
  <div id="settingsApp">
    <div class="onboarding">
      <section class="onboarding-header">
        <ConfigInformation />
      </section>

      <section class="onboarding-content">
        <prestashop-accounts></prestashop-accounts>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import ConfigInformation from "@/components/ConfigInformation.vue";
import { onMounted } from "vue";

onMounted(async () => {
  if (window?.psaccountsVue) {
    return window?.psaccountsVue?.init();
  }
  const accountFallback = await import("prestashop_accounts_vue_components");
  accountFallback.init();
});
</script>
<style lang="scss">
#settingsApp {
  font-family: Open Sans, Helvetica, Arial, sans-serif;
}
.nobootstrap {
  background-color: unset !important;
  padding: 100px 10px 100px;
  min-width: unset !important;
}
.page-sidebar.mobile #content.nobootstrap {
  @apply ml-0;
}
.onboarding {
  @apply pt-24 max-w-screen-lg mx-auto;
  &-header {
    @apply mb-2;
  }
  @screen md {
    @apply pt-4;
    &-header {
      @apply mb-4;
    }
  }
}
</style>
