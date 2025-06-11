import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {Globals} from '~/utils/globals';

export default class PopupAccountPage extends BasePage {
  /* <<<<<<<<<<<<<<< Selectors Types >>>>>>>>>>>>>>>>>>>>>> */

  constructor(page: Page) {
    super(page);
  }

  /* <<<<<<<<<<<<<<< Main Methods >>>>>>>>>>>>>>>>>>>>>> */
  /**
   *
   * Opens the PrestaShop Account configuration popup and verifies the redirection
   * Clicks the 'Configure' link, triggers a popup by clicking the 'Link' button, waits for the new page to load
   * Expect url and title
   * @return {Promise<string>}
   */
  async openAccountPopup(): Promise<Page> {
    await this.page.locator('#modules-list-container-440').getByRole('link', {name: 'Configure'}).click();
    const [newPage] = await Promise.all([
      this.page.context().waitForEvent('page'),
      this.page.getByRole('button', {name: 'Link'}).click()
    ]);
    await newPage.waitForLoadState('networkidle', {timeout: 10000});
    expect(newPage.url()).toContain('authv2-preprod');
    return newPage;
  }
  /**
   * @param newPage {Page} The account popup
   * Verifies that the page title is visible, indicating the Cloudflare challenge has been passed.
   */
  async accountPopupTiteleIsVisible(newPage: Page) {
    const pageTitle = await newPage.getByRole('img', {name: 'Prestashop logo'});
    expect(pageTitle).toBeVisible({visible: true});
  }
  /**
   * Connected to account
   * @param newPage {Page} The account popup
   * @param email string
   * @param password string
   */
  async connectToAccount(newPage: Page) {
    await newPage.getByRole('textbox', {name: 'email'}).fill(Globals.account_email);
    await newPage.getByRole('textbox', {name: 'password'}).fill(Globals.account_password);
    await newPage.getByRole('button', {name: 'Log in'}).click();
  }
}
