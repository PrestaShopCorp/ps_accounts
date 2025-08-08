import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {modulePsAccount} from 'data/local/modules/modulePsAccount';
import {moduleManagerPagesLocales} from '~/data/local/moduleManagerPageLocales/moduleManagerPageLocales';
import path from 'path';

export default class ModuleManagerPage extends BasePage {
  /* <<<<<<<<<<<<<<< Selectors Types >>>>>>>>>>>>>>>>>>>>>> */
  public readonly pageMainTitle: Locator;
  public readonly pageMainTitleOldPsVersion: Locator;

  constructor(page: Page) {
    super(page);

    /* <<<<<<<<<<<<<<< Selectors >>>>>>>>>>>>>>>>>>>>>> */
    this.pageMainTitle = page.locator('.title-row .title');
    this.pageMainTitleOldPsVersion = page.locator('h2.page-title');
  }

  /* <<<<<<<<<<<<<<< Main Methods >>>>>>>>>>>>>>>>>>>>>> */
  /**
   * Get the page title
   * @param page {Page} The browser tab
   * @return {Promise<string>}
   * The page title
   */
  async getPageMainTitle() {
    if (await this.page.locator('.title-row .title').isVisible()) {
      return this.getTextContent(this.pageMainTitle);
    }
  }
  async getPageMainTitleOldPsVersion() {
    if (await this.page.locator('h2.page-title').isVisible()) {
      return this.getTextContent(this.pageMainTitleOldPsVersion);
    }
  }

  /**
   * Searches for 'Account' in the search bar and checks if the 'PrestaShop Account' module is visible.
   * @expect Account to be visible
   */
  async isAccountVisible() {
    const pageTitle = await this.getPageMainTitle();
    const pageTitleOldPsVersion = await this.getPageMainTitleOldPsVersion();
    if (pageTitle === moduleManagerPagesLocales.moduleManager.en_EN.title) {
      await this.page.locator('#search-input-group').getByRole('textbox').fill('ps_account');
      await this.page.locator('#module-search-button').click();
      const isAccountVisible = this.page
        .locator('.module-item-wrapper-list')
        .filter({hasText: 'PrestaShop Account'})
        .isVisible();
      expect(isAccountVisible).toBeTruthy();
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      await this.page.waitForSelector('.icon-list-ul');
      await this.page.locator('#moduleQuicksearch').fill('ps_account');
      await this.page.locator('#moduleQuicksearch').press('Enter');
      const isAccountVisibleOnOldPsVersion = await this.page
        .locator('.module_name')
        .filter({hasText: 'PrestaShop Account'})
        .isVisible();
      expect(isAccountVisibleOnOldPsVersion).toBeTruthy;
    }
  }

  /**
   * Verifies that the displayed PrestaShop Account version matches the expected version.
   * @expect The displayed version contains the expected version.
   */
  async verifyAccountVersion() {
    const pageTitle = await this.getPageMainTitle();
    const pageTitleOldPsVersion = await this.getPageMainTitleOldPsVersion();
    if (pageTitle === moduleManagerPagesLocales.moduleManager.en_EN.title) {
      const accountVersion = await this.page
        .locator('.module-item-wrapper-list')
        .filter({hasText: 'PrestaShop Account'})
        .locator('.small-text');
      await expect(accountVersion).toContainText(modulePsAccount.version);
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      const accountVersionOnOldPsVersion = await this.page
        .locator('.module_name')
        .filter({hasText: 'PrestaShop Account'})
        .locator('.text-muted');
      await expect(accountVersionOnOldPsVersion).toContainText(modulePsAccount.version);
    }
  }

  /**
   *
   * The page title check if the title All Store is visible
   */
  async isMultistoreVisible() {
    await this.page.locator('.header-multishop-button').click();
    const isMultiStoreVisible = this.page.locator('.multishop-modal-all-name');
    await isMultiStoreVisible.click();
    expect(isMultiStoreVisible).toBeVisible({timeout: 3000});
  }
  async isMultistoreVisibleOldVersion() {
    await this.page.locator('#header_shop').click();
    const isMultiStoreVisible = this.page.getByRole('link', {name: 'All shops'});
    await isMultiStoreVisible.click();
    expect(isMultiStoreVisible).toBeVisible({timeout: 3000});
  }

  /**
   *
   * Upload a zip
   */
  async uploadZip() {
    const pageTitle = await this.getPageMainTitle();
    const pageTitleOldPsVersion = await this.getPageMainTitleOldPsVersion();
    if (pageTitle === moduleManagerPagesLocales.moduleManager.en_EN.title) {
      await this.page.locator('[data-target="#module-modal-import"]').click();
      const fileChooserPromise = this.page.waitForEvent('filechooser');
      await this.page.locator('.module-import-start-select-manual').click();
      const fileChooser = await fileChooserPromise;
      await fileChooser.setFiles(path.join(__dirname, '../../../e2e-env/modules/ps_accounts_preprod-7.2.0.zip'));
      await this.page.waitForSelector('.module-import-success-icon');
      await this.page.locator('#module-modal-import-closing-cross').click();
      await this.page.reload({waitUntil: 'commit'});
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      await this.page.locator('#desc-module-new').click();
      const fileChooserPromise = this.page.waitForEvent('filechooser');
      await this.page.locator('#file-selectbutton').click();
      const fileChooser = await fileChooserPromise;
      await fileChooser.setFiles(path.join(__dirname, '../../../e2e-env/modules/ps_accounts_preprod-7.2.0.zip'));
      await this.page.locator('[name="download"]').click();
      await this.page.waitForSelector('.alert.alert-success');
      await this.page.reload({waitUntil: 'commit'});
    }
  }
}
