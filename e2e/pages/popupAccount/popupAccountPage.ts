import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';

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
    await newPage.waitForLoadState('networkidle');
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
   * Connected to account with MBE
   * @param email string
   * @param password string
   */
  async connectToAccount(email: string, password: string) {
    await this.page.locator('#email').fill(email);
    await this.page.locator('#password').fill(password);
    await this.page.getByRole('button', {name: 'Log in'}).click();
  }
}
