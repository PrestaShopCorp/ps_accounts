import {Page, Locator, expect} from '@playwright/test';
import BasePage from '~/pages/basePage';
import {modulePsAccount} from '~/data/local/modulesDbData/ps_module_data';
import {moduleManagerPagesLocales} from '~/data/local/moduleManagerPageLocales/moduleManagerPageLocales';

export default class ModuleManagerPage extends BasePage {
  /* <<<<<<<<<<<<<<< Selectors Types >>>>>>>>>>>>>>>>>>>>>> */
  public readonly pageMainTitle: Locator;
  public readonly pageMainTitleOldPsVersion: Locator;

  constructor(page: Page) {
    super(page);

    /* <<<<<<<<<<<<<<< Selectors >>>>>>>>>>>>>>>>>>>>>> */
    this.pageMainTitle = page.locator('.title-row .title');
    this.pageMainTitleOldPsVersion = page.locator('.page-title');
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
    if (await this.page.locator('.page-title').isVisible()) {
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
      await this.page.locator('#search-input-group').getByRole('textbox').fill('Account');
      await this.page.locator('#module-search-button').click();
      const isAccountVisible = this.page
        .locator('.module-item-wrapper-list')
        .filter({hasText: 'PrestaShop Account'})
        .isVisible();
      expect(isAccountVisible).toBeTruthy();
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      await this.page.locator('#filter_administration').click();
      await this.page.locator('#moduleQuicksearch').fill('PrestaShop Account');
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
    const isMultiStoreVisible = await this.page.locator('h2.header-multishop-title');
    expect(isMultiStoreVisible).toBeVisible();
  }
  async isMultistoreVisibleOldVersion() {
    await this.page.locator('#header_shop').click();
    const isMultiStoreVisible = await this.page.getByRole('link', {name: 'All shops'});
    expect(isMultiStoreVisible).toBeVisible();
  }

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
}
