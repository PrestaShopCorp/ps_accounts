import {Page, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {dashboardPagesLocales} from '~/data/local/dashoardPageLocales/dashboardLocales';

export default class DashboardPage extends BasePage {
  /* <<<<<<<<<<<<<<< Selectors Types >>>>>>>>>>>>>>>>>>>>>> */
  constructor(page: Page) {
    super(page);
  }

  /* <<<<<<<<<<<<<<< Main Methods >>>>>>>>>>>>>>>>>>>>>> */
  /**
   * Get the page title
   * @param page {Page} The browser tab
   * @expect The page title
   */
  async getPageMainTitle() {
    const title = await this.page.locator('.page-title');
    expect(title).toContainText(dashboardPagesLocales.dashboard.en_EN.title);
  }

  /**
   * Get Shop version
   * @param page {Page} The browser tab
   * Return Shop Version
   */

  async getShopVersion(): Promise<boolean> {
      const versionLocator = this.page.locator('#shop_version');
      const versionText = await versionLocator.first().textContent();
      return versionText?.includes('1.6') ?? false;
  }

  /**
   * Check if Popup is Visible
   * @param page {Page} The browser tab
   * True if Popup Visible
   */
  async isPopupVisible(): Promise<boolean> {
    return await this.page.locator('.onboarding-popup').isVisible({timeout: 5000});
  }

  /**
   * Close Popup
   * @param page {Page} The browser tab
   */
  async closePopup() {
    const popup = this.page.locator('.onboarding-popup');
    const closePopupBtn = this.page.locator('.material-icons.onboarding-button-shut-down');
    await popup.waitFor({state: 'visible', timeout: 5000});
    await closePopupBtn.waitFor({state: 'visible', timeout: 5000});
    await closePopupBtn.click();
    await popup.waitFor({state: 'hidden', timeout: 5000});
  }
}
