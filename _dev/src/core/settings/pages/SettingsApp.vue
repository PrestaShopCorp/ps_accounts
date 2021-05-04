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
  <div id="settingsApp">
    <Tabs :vertical="vertical">
      <Tab :label="$t('general.settings')" to="/settings/onboarding" />
<!--      <Tab :label="$t('general.help')" to="/settings/help" />-->
    </Tabs>
  </div>
</template>

<script>
import Tabs from "@/core/app/components/tabs/Tabs.vue";
import Tab from "@/core/app/components/tabs/Tab.vue";

export default {
  name: "Home",
  components: {
    Tabs,
    Tab,
  },
  props: {
    vertical: {
      type: Boolean,
      default: false,
    },
  },
};
</script>

<style lang="scss">
#settingsApp {
  @import "~bootstrap-vue/dist/bootstrap-vue";
  @import "~prestakit/dist/css/bootstrap-prestashop-ui-kit";
}

#settingsApp {
  margin: 0;
  font-family: Open Sans, Helvetica, Arial, sans-serif;
  font-size: 14px;
  font-size: 0.875rem;
  font-weight: 400;
  line-height: 1.5;
  color: #363a41;
  text-align: left;

  .tab-content {
    background: transparent;
  }
}

#settingsApp .card-header,
.card-header .card-header-title {
  font-weight: 600;
  line-height: 24px;
  line-height: 1.5rem;
}

#settingsApp
  .card-header
  .main-header
  #header-search-container
  .input-group:before,
.card-header .material-icons,
.card-header .ps-tree-items .tree-name button:before,
.main-header #header-search-container .card-header .input-group:before,
.ps-tree-items .tree-name .card-header button:before {
  color: #6c868e;
  margin-right: 5px;
}

#settingsApp .form-group.has-danger:after,
#settingsApp .form-group.has-success:after,
#settingsApp .form-group.has-warning:after {
  right: 10px;
}

.nobootstrap {
  background-color: unset !important;
  padding: 100px 10px 100px;
  min-width: unset !important;
}

.nobootstrap .form-group > div {
  float: unset;
}

.nobootstrap fieldset {
  background-color: unset;
  border: unset;
  color: unset;
  margin: unset;
  padding: unset;
}

.nobootstrap label {
  color: unset;
  float: unset;
  font-weight: unset;
  padding: unset;
  text-align: unset;
  text-shadow: unset;
  width: unset;
}

.nobootstrap .table tr th {
  background-color: unset;
  color: unset;
  font-size: unset;
}

.nobootstrap .table.table-hover tbody tr:hover {
  color: #fff;
}

.nobootstrap .table.table-hover tbody tr:hover a {
  color: #fff !important;
}

.nobootstrap .table tr td {
  border-bottom: unset;
  color: unset;
}

.nobootstrap .table {
  background-color: unset;
  border: unset;
  border-radius: unset;
  padding: unset;
}

.page-sidebar.mobile #content.nobootstrap {
  margin-left: unset;
}

.page-sidebar-closed:not(.mobile) #content.nobootstrap {
  padding-left: 50px;
}

.material-icons.js-mobile-menu {
  display: none !important;
}

@import url("https://fonts.googleapis.com/icon?family=Material+Icons");
</style>
