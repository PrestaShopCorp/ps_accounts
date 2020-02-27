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

export default {
  generate(
    svcUiDomainName,
    protocolDomainToValidate,
    domainNameDomainToValidate,
    protocolBo,
    domainNameBo,
    queryParamsInput = {
      boUrl: null,
      pubKey: null,
      shopName: null,
      next: null,
    },
  ) {
    const output = {
      SvcUiUrlIsGenerated: false,
      svcUiUrl: null,
      queryParams: {},
    };

    output.queryParams.bo = typeof queryParamsInput.boUrl === 'string' ? encodeURIComponent(queryParamsInput.boUrl) : null;
    output.queryParams.pubKey = typeof queryParamsInput.pubKey === 'string' ? encodeURIComponent(queryParamsInput.pubKey) : null;
    output.queryParams.name = typeof queryParamsInput.shopName === 'string' ? encodeURIComponent(queryParamsInput.shopName) : null;
    output.queryParams.next = typeof queryParamsInput.next === 'string' ? encodeURIComponent(queryParamsInput.next) : null;

    const countInitQueryParams = Object.keys(output.queryParams).length;
    let counterValideParams = 0;
    output.svcUiUrl = `${svcUiDomainName}/link-shop/${protocolDomainToValidate}/${domainNameDomainToValidate}/${protocolBo}/${domainNameBo}/PSXEmoji.Deluxe.Fake.Service?`;

    Object.entries(output.queryParams).forEach(([key, value]) => {
      if (value !== null) {
        // eslint-disable-next-line no-plusplus
        counterValideParams++;
        output.svcUiUrl += `${key}=${value}&`;
      }
    });

    if (countInitQueryParams === counterValideParams) {
      output.svcUiUrl = output.svcUiUrl.slice(0, -1);
      output.SvcUiUrlIsGenerated = true;
    }

    return output;
  },
};
