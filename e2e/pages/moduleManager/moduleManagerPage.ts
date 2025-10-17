import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {modulePsAccount} from '~/data/local/modulesDbData/ps_module_data';
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
      const titleText = await this.pageMainTitle.textContent();
      return titleText?.trim();
    }
  }
  async getPageMainTitleOldPsVersion() {
    if (await this.page.locator('h2.page-title').isVisible()) {
      const titleText = await this.pageMainTitleOldPsVersion.textContent();
      return titleText?.trim();
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
      const isAccountVisible = await this.page
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
      expect(isAccountVisibleOnOldPsVersion).toBeTruthy();
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
      await fileChooser.setFiles(path.join(__dirname, '../../../e2e-env/modules/ps_accounts_preprod-8.0.2.zip'));
      await this.page.waitForSelector('.module-import-success-icon');
      await this.page.locator('#module-modal-import-closing-cross').click();
      await this.page.reload({waitUntil: 'commit'});
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      await this.page.locator('#desc-module-new').click();
      const fileChooserPromise = this.page.waitForEvent('filechooser');
      await this.page.locator('#file-selectbutton').click();
      const fileChooser = await fileChooserPromise;
      await fileChooser.setFiles(path.join(__dirname, '../../../e2e-env/modules/ps_accounts_preprod-8.0.2.zip'));
      await this.page.locator('[name="download"]').click();
      await this.page.waitForSelector('.alert.alert-success');
      await this.page.reload({waitUntil: 'commit'});
    }
  }

  /**
   * Navigate to the "Configure" page of PrestaShop Account module
   * Handles both new and old PS versions
   */
  async goToAccountConfigurePage() {
    const pageTitle = await this.getPageMainTitle();
    const pageTitleOldPsVersion = await this.getPageMainTitleOldPsVersion();

    if (pageTitle === moduleManagerPagesLocales.moduleManager.en_EN.title) {
      const moduleContainer = this.page.locator('#modules-list-container-440');
      const dropdownBtn = moduleContainer.locator('.btn.btn-outline-primary.dropdown-toggle');
      const upgradeBtn = moduleContainer.getByRole('button', {name: 'Upgrade'});

      if (await upgradeBtn.isVisible()) {
        await dropdownBtn.click();
      }
      await moduleContainer.getByRole('link', {name: 'Configure'}).click();
      await this.page.waitForLoadState('load');
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      const moduleContainer = this.page.locator('tr:not([style*="display: none"])');
      const dropDownParent = moduleContainer.locator('.actions');
      // const dropdownBtn = dropDownParent.locator('.caret');
      const dropdownBtn = dropDownParent.getByRole('cell', { name: ' Update it!' }).getByRole('button')
      const upgradeBtn = dropDownParent.getByRole('button', {name: ' Update it! '});

      if (await upgradeBtn.isVisible()) {
        await dropdownBtn.click({force: true});
      }
      await moduleContainer.getByRole('link', {name: 'Configure'}).click();
      await this.page.waitForLoadState('load');
    } else {
      throw new Error('Module Manager page title not recognized.');
    }
  }
}
