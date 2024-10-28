import {APIResponse, request, expect} from '@playwright/test';
import {Globals} from 'utils/globals';

export default class CurlRequest {
  async getShopHealthStatus() {
    const context = await request.newContext();
    const response = await context.get(
      `${Globals.base_url_fo}//index.php?fc=module&module=ps_accounts&controller=apiV2ShopHealthCheck`
    );
    expect(response.status()).toBe(200);

    const data = await response.json();
    return data;
  }

  async isShopLinked() {
    const data = await this.getShopHealthStatus();
    const isShopLinked = data.shopLinked;

    expect(isShopLinked).toBeFalsy();
  }

  async checkOauth2Url() {
    const data = await this.getShopHealthStatus();
    const oauth2Url = data.env.oauth2Url;

    expect(oauth2Url).toContain(Globals.curl.oauth2Url);
  }
}
