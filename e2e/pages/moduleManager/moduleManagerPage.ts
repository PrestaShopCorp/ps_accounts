import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {modulePsAccount} from 'data/local/modules/modulePsAccount'

//BO Login Page

export default class ModuleManagerPage extends BasePage {
  /* <<<<<<<<<<<<<<< Selectors Types >>>>>>>>>>>>>>>>>>>>>> */
  public readonly pageMainTitle: Locator;

  constructor(page: Page) {
    super(page);

    /* <<<<<<<<<<<<<<< Selectors >>>>>>>>>>>>>>>>>>>>>> */
    this.pageMainTitle = page.locator('.title-row .title');
  }

  /* <<<<<<<<<<<<<<< Main Methods >>>>>>>>>>>>>>>>>>>>>> */
  /**
   * Get the page title
   * @param page {Page} The browser tab
   * @return {Promise<string>}
   * The page title
   */
  async getPageMainTitle(): Promise<string | null> {
    return this.getTextContent(this.pageMainTitle);
  }

  /**
   * Searches for 'Account' in the search bar and checks if the 'PrestaShop Account' module is visible.
   * @expect Account to be visible
   */
  async isAccountVisible() {
    await this.page.locator('#search-input-group').getByRole('textbox').fill('Account');
    await this.page.locator('#module-search-button').click();
    const isAccountVisible = this.page.locator('.module-item-wrapper-list').filter({hasText: 'PrestaShop Account'});
    expect(isAccountVisible).toBeTruthy();
  }

  /**
   * Verifies that the displayed PrestaShop Account version matches the expected version.
   * @expect The displayed version contains the expected version.
   */
  async verifyAccountVersion() {
    const accountVersion = await this.page
      .locator('.module-item-wrapper-list')
      .filter({hasText: 'PrestaShop Account'})
      .locator('.small-text');
    await expect(accountVersion).toContainText(modulePsAccount.version);
  }
}
