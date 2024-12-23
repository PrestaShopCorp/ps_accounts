import {Globals} from '~/utils/globals';
import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {dashboardPagesLocales} from '~/data/local/dashoardPageLocales/dashboardLocales';

//BO Login Page

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
   * Check if Popup is Visible
   * @param page {Page} The browser tab
   * True if Popup Visible
   */
  async isPopupVisible(): Promise<boolean> {
    return await this.page.locator('.onboarding-popup').isVisible();
  }

  /**
   * Close Popup
   * @param page {Page} The browser tab
   */
  async closePopup() {
    await this.page.getByText('close').click();
  }
}
