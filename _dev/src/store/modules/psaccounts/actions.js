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
import generateSvcUiUrl from '@/services/generateSvcUiUrl'
import ajax from '@/requests/ajax.js'

export default {
  setSvcUiUrl({ commit, getters }, payload) {
    return ajax({
      url: getters.adminController,
      action: 'GenerateSshKey',
      data: [],
    }).then(response => {
      const generator = generateSvcUiUrl.generate(
        payload.svcUiDomainName,
        getters.protocolDomainToValidate,
        getters.domainNameDomainToValidate,
        payload.protocolBo,
        payload.domainNameBo,
        {
          boUrl: getters.boUrl,
          pubKey: response,
          shopName: getters.shopName,
          next: getters.nextStep,
        }
      )

      commit('UPDATE_QUERY_PARAMS', generator.queryParams)

      if (generator.SvcUiUrlIsGenerated) {
        commit('UPDATE_SVC_UI_URL', generator.svcUiUrl)
      }
      return Promise.resolve(true)
    })
  },
}
