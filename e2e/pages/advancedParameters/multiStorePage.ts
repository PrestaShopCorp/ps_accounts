import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {advancedParametersPageLocales} from '~/data/local/advancedParametersPageLocales/advancedParametersPageLocales';
import {text} from 'stream/consumers';

export default class MultiStorePage extends BasePage {
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
    const title = await this.page.locator('h1.page-title');
    expect(title).toContainText(advancedParametersPageLocales.multiStorePage.en_EN.title);
  }

  /**
   * New Store creation
   * @param page {Page} The browser tab
   * Create a new store to check the multistore
   */
  async createNewStore() {
    await this.page.locator('#page-header-desc-shop_group-new_2').click();
    await this.page.locator('#name').fill('Shop2');
    await this.page.locator('#fieldset_0').getByRole('button', {name: 'Save'}).click();
    await this.page.locator('a.multishop_warning').click();
    await this.page.locator('#virtual_uri').fill('shop2');
    await this.page.locator('#shop_url_form_submit_btn_1').click();
  }
}
