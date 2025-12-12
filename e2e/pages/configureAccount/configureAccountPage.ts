import {expect, Page} from '@playwright/test';
import ModuleManagerPage from '~/pages/moduleManager/moduleManagerPage';
import {Globals} from '~/utils/globals';

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
  /**
   * Click on verify button and wait for confirmation
   */
  async verifyManualy() {
    await this.page.getByRole('button', {name: 'Verify'}).click();
    await this.page.waitForLoadState('load');
  }
  /**
   * Check if Verification succed
   * @expect element to be true
   */
  async checkVerificationSucced() {
    const isVisible = await this.page.getByRole('img', {name: 'check_circle icon'}).isEnabled();
    expect(isVisible).toBeTruthy();
  }
  /**
   * Check if Verification failed
   * @expect element to be vidible
   */
  async checkVerificationFailed() {
    const isVisible = await this.page.locator('[data-test="description-verification-failed-alert"]');
    expect(isVisible).toBeVisible();
  }

  /**
   * Check if point of contact is available
   * @expect element to be true
   */
  async checkSignInisVisible() {
    const isVisible = await this.page.locator('[data-test="define-poc-button"]').isEnabled();
    expect(isVisible).toBeTruthy();
  }

  /**
   * Opens the Account popup
   * @returns Promise<Page>
   */
  async clickSignInAndOpenPopup(): Promise<Page> {
    const [newPage] = await Promise.all([
      this.page.context().waitForEvent('page'),
      this.page.locator('[data-test="define-poc-button"]').click()
    ]);

    await newPage.waitForTimeout(5000);
    expect(newPage.url()).toContain('authv2-preprod');
    return newPage;
  }

  /**
   * Check Point of contact
   * @expect true ACCOUNT_EMAIL isVisible
   */
  async checkIsSigned() {
    await this.page.waitForTimeout(5000);
    await this.page.locator('.page-title', {hasText: 'Configure'}).isVisible;
    const isVisible = await this.page.getByText(Globals.account_email).isVisible();
    expect(isVisible).toBeTruthy();
  }

  /**
   *
   * In MultiStore Context dispaly all store informations
   */
  async displayAllStoreInformations() {
    await this.page.locator('.shopname').click();
    await this.page
      .locator('a')
      .filter({hasText: /^All stores$/})
      .click();
    await this.page.waitForLoadState('load');
  }

  /**
   *
   * In MultiStore Context when all all store is deplayed, show alert
   */
  async getMultistoreAlert() {
    const alertBlock = await this.page.locator('.puik-alert.puik-alert--info').isVisible();
    expect(alertBlock).toBeTruthy()
  }
}
