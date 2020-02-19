import {forEach} from 'lodash';

/**
 * 2007-2019 PrestaShop and Contributors
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
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
// import ajax from '@/requests/ajax.js';

export default {
  setSvcUiUrl: (store, data) => {
    let svcUiUrl = `${data.svcUiDomainName}/link-shop/${store.getters.getProtocolDomainToValidateQueryParams}/${store.getters.getDomainNameDomainToValidate}/${data.protocolBo}/${data.domainNameBo}/PSXEmoji.Deluxe.Fake.Service`;


    const queryParams = {};
    queryParams.bo = typeof store.getters.getBoUrl === 'string' ? encodeURIComponent(store.getters.getBoUrl) : null;
    queryParams.pubKey = typeof store.getters.getPubKey === 'string' ? encodeURIComponent(store.getters.getPubKey) : null;
    queryParams.name = typeof store.getters.getShopName === 'string' ? encodeURIComponent(store.getters.getShopName) : null;
    queryParams.next = typeof store.getters.getNextStep === 'string' ? encodeURIComponent(store.getters.getNextStep) : null;

    const countInitQueryParams = Object.keys(queryParams).length;
    let counterValideParams = 0;
    svcUiUrl += '?';
    forEach(queryParams, (value, key) => {
      if (value !== null) {
        // eslint-disable-next-line no-plusplus
        counterValideParams++;
        svcUiUrl += `${key}=${value}&`;
      }
    });

    store.commit('UPDATE_QUERY_PARAMS', queryParams);

    if (countInitQueryParams === counterValideParams) {
      svcUiUrl = svcUiUrl.slice(0, -1);
      store.commit('UPDATE_SVC_UI_URL', svcUiUrl);
    }
  },
};
