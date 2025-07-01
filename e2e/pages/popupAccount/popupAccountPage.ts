import {Page, Locator, expect} from '@playwright/test';
import {Globals} from '~/utils/globals';
import ModuleManagerPage from '~/pages/moduleManager/moduleManagerPage';
import {moduleManagerPagesLocales} from '~/data/local/moduleManagerPageLocales/moduleManagerPageLocales';

export default class PopupAccountPage extends ModuleManagerPage {
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
    const pageTitle = await this.getPageMainTitle();
    const pageTitleOldPsVersion = await this.getPageMainTitleOldPsVersion();
    if (pageTitle === moduleManagerPagesLocales.moduleManager.en_EN.title) {
      const moduleContainer = await this.page.locator('#modules-list-container-440');
      const dropdownBtn = moduleContainer.locator('.btn.btn-outline-primary');
      const upgradeBtn = moduleContainer.getByRole('button', {name: 'Upgrade'});
      if (await upgradeBtn.isVisible()) {
        await dropdownBtn.click();
      }
      await moduleContainer.getByRole('link', {name: 'Configure'}).click();
      const [newPage] = await Promise.all([
        this.page.context().waitForEvent('page'),
        this.page.getByRole('button', {name: 'Link'}).click()
      ]);
      await newPage.waitForTimeout(5000);
      expect(newPage.url()).toContain('authv2-preprod');
      return newPage;
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      const moduleContainer = await this.page.locator('#anchorPs_accounts');
      const dropdownBtn = moduleContainer.locator('.btn.btn-default.dropdown-toggle');
      const upgradeBtn = moduleContainer.getByRole('button', {name: ' Update it!'});
      if (await upgradeBtn.isVisible()) {
        await dropdownBtn.click();
      }
      await moduleContainer.getByRole('link', {name: 'Configure'}).click();
      const [newPage] = await Promise.all([
        this.page.context().waitForEvent('page'),
        this.page.getByRole('button', {name: 'Link'}).click()
      ]);
      await newPage.waitForTimeout(5000);
      expect(newPage.url()).toContain('authv2-preprod');
      return newPage;
    }
    throw new Error('Popup Account can not be open');
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
    const logginBtn = await newPage.locator('.puik-button.puik-button--primary');
    await logginBtn.isEnabled();
    await logginBtn.click();
    const associateBtn = await newPage.locator('.puik-button.puik-button--primary');
    await associateBtn.isVisible();
    await associateBtn.click();
    const boBtn = await newPage.locator('.puik-button.puik-button--primary');
    await boBtn.isVisible({timeout: 5000});
    await boBtn.click();
  }

  /**
   * Chek if the green Icon after association is visible
   */
  async checkIsLinked() {
    await this.page.waitForSelector('[data-testid="account-shop-link-message-single-shop-linked"]');
    const isLinked = await this.page.locator('[data-testid="account-shop-link-message-single-shop-linked"]');
    expect(isLinked).toBeVisible();
  }
  /**
   * Connected to account
   * @param newPage {Page} The account popupÒ
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
      await newPage.locator('.VfPpkd-vQzf8d').nth(1).click();
    }
  }
  /**
   * Click back btn and return to module Manager page
   */
  async returnToModuleManager() {
    const locators = [
      this.page.locator('.process-icon-back'),
      this.page.locator('#desc-module-back'),
      this.page.locator('#page-header-desc-configuration-module-back')
    ];
    for (const locator of locators)
      if (await locator.isVisible()) {
        await locator.click();
        await this.page.reload();
        return;
      }
  }
}
