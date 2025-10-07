import {request, expect} from '@playwright/test';
import { Globals } from '~/utils/globals';

export default class HealthCheckApi {
  /**
   * Method to check healthcheck status and return data
   * @returns The API response containing the data.
   */
  async getShopHealthStatus() {
    const context = await request.newContext();
    const response = await context.get(
      `${Globals.base_url_fo}/index.php?fc=module&module=ps_accounts&controller=apiV2ShopHealthCheck`
    );
    expect(response.status()).toBe(200);

    const data = await response.json();
    return data;
  }

  /**
   * Method to check Oauth2Client status
   * @returns true if the shop is an OAuth2 client, otherwise false
   */
  async getOauth2ClientStatus(): Promise<boolean> {
    const data = await this.getShopHealthStatus();
    const oauth2ClientStatus = data.oauth2Client;

    return oauth2ClientStatus;
  }

  /**
   * Method to check if shop is linked
   * @returns true if the shop is linked, otherwise false
   */
  async getShopLinkedStatus(): Promise<boolean> {
    const data = await this.getShopHealthStatus();
    const shopLinkedStatus = data.shopLinked;

    return shopLinkedStatus;
  }

  // Method to check oauth2 Url
  async checkOauth2Url() {
    const data = await this.getShopHealthStatus();
    const oauth2Url = data.env.oauth2Url;

    expect(oauth2Url).toEqual(Globals.curl.oauth2Url);
  }

  // Method to check accountsApi Url
  async checkAccountsApiUrl() {
    const data = await this.getShopHealthStatus();
    const accountsApiUrl = data.env.accountsApiUrl;

    expect(accountsApiUrl).toEqual(Globals.curl.accountsApiUrl);
  }

  // Method to check accountsUi Url
  async checkAccountsUiUrl() {
    const data = await this.getShopHealthStatus();
    const accountsUiUrl = data.env.accountsUiUrl;

    expect(accountsUiUrl).toEqual(Globals.curl.accountsUiUrl);
  }
}
