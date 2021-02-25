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
import Vue from "vue";
import VueI18n from "vue-i18n";

Vue.use(VueI18n);

const { storePsAccounts } = global;
const locale = storePsAccounts.context.i18n.isoCode
  ? storePsAccounts.context.i18n.isoCode
  : "";
// const languageLocale = storePsAccounts.context.i18n.languageLocale
//   ? storePsAccounts.context.i18n.languageLocale
//   : "";
const messages = Object.assign(
  storePsAccounts.context.app === "settings" &&
    storePsAccounts.settings.translations
    ? storePsAccounts.settings.translations
    : {},
  {
    ...(storePsAccounts.context.app === "dashboard" &&
    storePsAccounts.dashboard.translations
      ? storePsAccounts.dashboard.translations
      : {}),
  }
);
const numberFormats = {};
// create standard isoCode with prestashop language locale (1.6.1)

numberFormats[locale] = {
  currency: {
    style: "currency",
    currency: storePsAccounts.context.i18n.currencyIsoCode,
  },
};

export default new VueI18n({
  locale,
  messages,
  numberFormats,
});
