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
import { call, put } from "@/lib/store-saga";
import {
  gaLogOut,
  installModuleGA,
  getListProperty,
} from "@/core/app/connectors/app.api";

export default {
  /* eslint-disable-next-line no-unused-vars */
  *installModuleGA(store, payload) {
    yield put("setLoadingInstallModuleGA", true);
    const response = yield call(installModuleGA, payload);
    yield put("setResponseInstallGA", response);
    yield put("setLoadingInstallModuleGA", false);
  },
  /* eslint-disable-next-line no-unused-vars */
  *getLogOut(store, payload) {
    yield put("setLoadingLogOut", true);
    const response = yield call(
      gaLogOut,
      store.rootState.app.controllersLinks.settings
    );
    yield put("setLogOut", response);
    yield put("setLoadingLogOut", false);
    return true;
  },
  /* eslint-disable-next-line no-unused-vars */
  *getListProperty(store) {
    yield put("setLoadingListProperty", true);
    const response = yield call(
      getListProperty,
      store.rootState.app.controllersLinks.settings
    );
    if (response.success) {
      yield put("setListPropertySuccess", response.listProperty);
      yield put("setListPropertyError", "");
    } else {
      yield put("setListPropertyError", response.error);
    }
    yield put("setLoadingListProperty", false);
    return Object.keys(response.listProperty).length;
  },
};
