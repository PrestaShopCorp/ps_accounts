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
  <PuikTabNavigation :name="args.name" :default-position="args.defaultPosition">
    <PuikTabNavigationGroupTitles :ariaLabel="args.ariaLabel">
      <PuikTabNavigationTitle :position="1">
        Configuration
      </PuikTabNavigationTitle>
      <PuikTabNavigationTitle :position="2"> Help </PuikTabNavigationTitle>
    </PuikTabNavigationGroupTitles>
    <PuikTabNavigationGroupPanels>
      <PuikTabNavigationPanel :position="1">
        <div class="panelContent">
          <prestashop-accounts></prestashop-accounts>
        </div>
      </PuikTabNavigationPanel>
      <PuikTabNavigationPanel :position="2">
        <div class="panelContent">
          Content for Profile tab goes here.
          <p>Profile content goes here.</p>
          <p>More profile content can be added here.</p>
        </div>
      </PuikTabNavigationPanel>
    </PuikTabNavigationGroupPanels>
  </PuikTabNavigation>
</template>

<script setup lang="ts">
import ConfigInformation from "@/configuration/components/ConfigInformation.vue";
import {
  PuikTabNavigation,
  PuikTabNavigationGroupTitles,
  PuikTabNavigationTitle,
  PuikTabNavigationGroupPanels,
  PuikTabNavigationPanel,
} from "@prestashopcorp/puik-components";
import { onMounted, ref } from "vue";
import { init } from "prestashop_accounts_vue_components";

const args = {
  name: "PS-Accounts",
  defaultPosition: 1,
  ariaLabel: "PS-Accounts tabs",
};

onMounted(async () => {
  if (window?.psaccountsVue) {
    return window?.psaccountsVue?.init(
      window.contextPsAccounts.component_params_init,
      "Settings",
    );
  }
  init();
});
</script>
<style lang="scss">
.nobootstrap {
  background-color: #ffffff;
  padding-top: 100px;
  padding-right: 10px;
  padding-left: 10px;
  min-width: unset !important;
  min-height: calc(100vh - 100px) !important;
}

#main {
  background-color: #ffffff;
}
.page-sidebar.mobile #content.nobootstrap {
  @apply psacc-ml-0;
}

/* .puik-tab-navigation__group-titles {
  border-bottom: rgb(221 221 221) 1px solid;
}
 */
.panelContent {
  @apply psacc-pt-24 psacc-max-w-screen-lg psacc-mx-auto;
  @screen md {
    @apply psacc-pt-4;
  }
}
</style>
