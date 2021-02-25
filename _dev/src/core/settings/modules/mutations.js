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

export default {
  setLogOut(state, payload) {
    state.googleLinked = payload.googleLinked;
  },
  setLoadingLogOut(store, payload) {
    store.state.loadingLogout = payload;
  },
  setLoadingInstallModuleGA(store, payload) {
    store.state.loadingInstallModuleGA = payload;
  },
  setResponseInstallGA(store, payload) {
    store.state.responseInstallGA = payload;
    if (payload.ps_googleanalytics.status) {
      store.gaModule.isInstalled = true;
      store.gaModule.isEnabled = true;
    }
  },
  setLoadingListProperty(store, payload) {
    store.state.loadingListProperty = payload;
  },
  setListPropertySuccess(store, payload) {
    store.state.listPropertySuccess = payload;
  },
  setListPropertyError(store, payload) {
    store.state.listPropertyError = payload;
  },
};
