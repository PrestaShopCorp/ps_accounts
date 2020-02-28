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
import generateSvcUiUrl from '@/services/generateSvcUiUrl';

export default {
  setSvcUiUrl: (store, data) => {
    const generator = generateSvcUiUrl.generate(
      data.svcUiDomainName,
      store.getters.getProtocolDomainToValidate,
      store.getters.getDomainNameDomainToValidate,
      data.protocolBo,
      data.domainNameBo,
      {
        boUrl: store.getters.getBoUrl,
        pubKey: store.getters.getPubKey,
        shopName: store.getters.getShopName,
        next: store.getters.getNextStep,
      },
    );

    store.commit('UPDATE_QUERY_PARAMS', generator.queryParams);
    if (generator.SvcUiUrlIsGenerated) {
      store.commit('UPDATE_SVC_UI_URL', generator.svcUiUrl);
    }
  },
};
