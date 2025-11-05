import {Page} from '@playwright/test';
import ModuleManagerPage from '~/pages/moduleManager/moduleManagerPage';

export default class ConfigureAccountPage extends ModuleManagerPage {
  /* <<<<<<<<<<<<<<< Selectors Types >>>>>>>>>>>>>>>>>>>>>> */

  constructor(page: Page) {
    super(page);
  }

  /* <<<<<<<<<<<<<<< Main Methods >>>>>>>>>>>>>>>>>>>>>> */
  /**
   * Check if the verified status is visible on the page.
   * @returns A promise that resolves to true if Verified is visible, otherwise false.
   */
  async getStoreInformation(): Promise<boolean> {
    await this.page.getByRole('heading', {name: 'Store information'}).isEnabled();
    const status = await this.page.getByRole('img', {name: 'check_circle icon'});
    return status.isVisible();
  }
  /**
   * Check the account status from api
   * @returns A promise that resolves to true if account Verified, otherwise false.
   */
  async getStoreInformationFromApi(): Promise<boolean> {
    const responsePromise = this.page.waitForResponse(
      (response) =>
        response.url().includes('AdminAjaxV2PsAccounts&ajax=1&action=getContext') && response.status() === 200
    );
    await this.page.reload();
    const response = await responsePromise;
    const data = await response.json();
    const isVerified = data?.groups?.[0]?.shops?.[0]?.shopStatus?.isVerified;

    return isVerified;
  }

  async verifyManualy() {
    await this.page.getByRole('button', {name: 'Verify'}).click();
    await this.page.getByRole('img', {name: 'check_circle icon'}).isEnabled()
  }
}
