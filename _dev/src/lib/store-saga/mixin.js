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
import sagaRun from "./sagaRun.js";

const StoreSaga = {};
StoreSaga.install = function (Vue, options) {
  if (!options)
    throw new Error(
      "[Store Saga]: Should pass the store in the plugin installation options"
    );
  const { store } = options;
  Vue.mixin({
    beforeCreate() {
      this.$run = (action, payload) => {
        return store.dispatch(action, payload).then((generator) => {
          if (!generator)
            throw new Error(
              "[Store Saga]: You're running ordinary action. Use Vuex mapActions instead of Vuex Saga mapSagas"
            );
          return sagaRun(generator, store);
        });
      };
    },
  });
};

export default StoreSaga;
