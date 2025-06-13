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
    await newPage.waitForTimeout(5000);
    expect(newPage.url()).toContain('authv2-preprod');
    return newPage;
  }
  /**
   * @param newPage {Page} The account popup
   * Verifies that the page title is visible, indicating the Cloudflare challenge has been passed.
   */
  async accountPopupTiteleIsVisible(newPage: Page) {
    const pageTitle = await newPage.getByRole('img', {name: 'Prestashop logo'});
    expect(pageTitle).toBeVisible();
  }
  /**
   * Connected to account
   * @param newPage {Page} The account popup
   * @param email string
   * @param password string
   */
  async connectToAccountWithMail(newPage: Page) {
    await newPage.locator('#email').fill(Globals.account_email);
    await newPage.locator('#password').fill(Globals.account_password);
    const logginBtn = await newPage.getByRole('button', {name: 'Log in'});
    await logginBtn.isEnabled({timeout: 5000});
    await this.waitForTimeout(500000);
  }
  /**
   * Connected to account
   * @param newPage {Page} The account popup
   * @param email string
   * @param password string
   */
  async connectToAccountWithGoogle(newPage: Page) {
    const linkBtnVisible = await newPage.locator('[data-test="link-shop-button"]');
    if (await linkBtnVisible.isVisible()) {
      await linkBtnVisible.click();
    } else {
      await newPage.locator('.puik-button.puik-button--secondary').click();
      await newPage.locator('#identifierId').fill('qa-autom+onboarding@prestashop.com');
      await newPage.locator('#identifierNext').click();
      await newPage.locator('[name="Passwd"]').fill('zWEufREWJ5FrpY3');
      await newPage.locator('.VfPpkd-vQzf8d').nth(1).click()
      
    }
  }
}
