import {Page, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {shopParametersPageLocales} from '~/data/local/shopParametersPageLocales/shopParametersPageLocales';

export default class GeneralPage extends BasePage {
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
    const title = await this.page.locator('h1.title');
    expect(title).toContainText(shopParametersPageLocales.generalPage.en_EN.title);
  }
  async activeMultiStoreOldPsVersion() {
    if (await this.page.locator('label[for="PS_MULTISHOP_FEATURE_ACTIVE_off"]').isChecked()) {
      await this.page.locator('label[for="PS_MULTISHOP_FEATURE_ACTIVE_on"]').click();
      await this.page.getByRole('button', {name: 'Save'}).click();
    }
  }

  async activeMultiStore() {
    if (await this.page.isChecked('#form_multishop_feature_active_0')) {
      await this.page.locator('#form_multishop_feature_active_1').click();
      await this.page.getByRole('button', {name: 'Save'}).click();
      const succesMessage = await this.page.locator('.alert-text');
      expect(succesMessage).toContainText('Successful update');
    }
  }
}
