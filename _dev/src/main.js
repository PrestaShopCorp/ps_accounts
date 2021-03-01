/**
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
 */
import "core-js/stable";
import "regenerator-runtime/runtime";
import Vue from "vue";
import Router from "vue-router";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import VueCollapse from "vue2-collapse";
import i18n from "@/lib/i18n";
import store from "@/core/app/store";
import router from "@/core/app/router";
import StoreSaga from "@/lib/store-saga";
import AppContainer from "@/core/app/pages/AppContainer";
import psAccountsVueComponents from "prestashop_accounts_vue_components";
import VueCompositionAPI from "@vue/composition-api";
import "@/lib/error";
import "@/core/app/assets/_global.scss";
import "@/core/app/assets/index.css";

Vue.use(VueCompositionAPI);
Vue.use(Router);
Vue.use(BootstrapVue, BootstrapVueIcons);
Vue.use(VueCollapse);
Vue.use(psAccountsVueComponents, { locale: i18n.locale });
Vue.use(StoreSaga, { store });
// Vue.use(VueSegment, {
//   id: "Vxk9VEvePTRlBmjkjzbUG6saW5yAmgb2",
//   router,
//   debug: process.env.NODE_ENV !== "production",
//   pageCategory: "ps_accounts",
// });

Vue.config.productionTip = process.env.NODE_ENV === "production";
Vue.config.debug = process.env.NODE_ENV !== "production";
Vue.config.devtools = process.env.NODE_ENV !== "production";

window.onload = () => {
  new Vue({
    router,
    store,
    i18n,
    render: (h) => h(AppContainer),
  }).$mount("#app");
};
