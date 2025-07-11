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
   * @return {Promise<Page>}
   */
  async openAccountPopup(): Promise<Page> {
    const pageTitle = await this.getPageMainTitle();
    const pageTitleOldPsVersion = await this.getPageMainTitleOldPsVersion();
    if (pageTitle === moduleManagerPagesLocales.moduleManager.en_EN.title) {
      const moduleContainer = await this.page.locator('#modules-list-container-440');
      const dropdownBtn = moduleContainer.locator('.btn.btn-outline-primary.dropdown-toggle');
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
      const moduleContainer = await this.page.locator('tr:not([style*="display: none"])');
      const dropDownParent = moduleContainer.locator('.actions');
      const dropdownBtn = dropDownParent.locator('.caret');
      const upgradeBtn = dropDownParent.locator('.btn.btn-warning', {hasText: ' Update it! '});
      if (await upgradeBtn.isVisible()) {
        await dropdownBtn.click({force: true});
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
  }
  // /**
  //  * Connected to account
  //  * @param newPage {Page} The account popupÒ
  //  * @param email string
  //  * @param password string
  //  */
  // async connectToAccountWithGoogle(newPage: Page) {
  //   const linkBtnVisible = await newPage.locator('[data-test="link-shop-button"]');
  //   if (await linkBtnVisible.isVisible()) {
  //     await linkBtnVisible.click();
  //   } else {
  //     await newPage.locator('.puik-button.puik-button--secondary').click();
  //     await newPage.locator('#identifierId').fill('');
  //     await newPage.locator('#identifierNext').click();
  //     await newPage.locator('[name="Passwd"]').fill('');
  //     await newPage.locator('.VfPpkd-vQzf8d').nth(1).click();
  //   }
  // }
  /**
   * Associate the shop and click btn to go to back to BO after association
   */
  async associateAndClickBoBtn(newPage: Page) {
    const associateBtn = await newPage.locator('.puik-button.puik-button--primary');
    await associateBtn.isVisible();
    await associateBtn.click();
    const boBtn = await newPage.locator('.puik-button.puik-button--primary');
    await boBtn.isVisible({timeout: 5000});
    await boBtn.click();
  }

  /**
   * Multistore Associate the shop and click btn to go to back to BO after association
   */
  async multisotreAssociateAndClickBoBtn(newPage: Page) {
    await newPage.waitForSelector('[data-test="shop-card"]', {state: 'visible'});
    const card = newPage.locator('[data-test="shop-card"]');
    const countCard = await card.count();
    expect(countCard).toBeGreaterThan(1);
    const associateBtn = await newPage.locator('.puik-button.puik-button--primary');
    await associateBtn.isVisible();
    await associateBtn.click();
    const boBtn = await newPage.locator('.puik-button.puik-button--primary');
    await boBtn.isVisible({timeout: 5000});
    await boBtn.click();
  }

  /**
   * Get the locator for the green icon after association
   * @returns {Locator} The locator to check linked shop success message
   */
  async checkIsLinked() {
    const accountTitle = this.page.locator('.title', {hasText: ' PRESTASHOP '});
    await accountTitle.isVisible();
    await this.waitForTimeout(4000);
    return await this.page.locator('[data-testid="account-panel-linked-icon"]');
  }

  /**
   * Check if Link bouton and manage shop bouton are visible
   */
  async multiStoreCheckIsLinkedAllShopAssociate() {
    const accountTitle = this.page.locator('.title', {hasText: ' PRESTASHOP '});
    await accountTitle.isVisible();
    await this.waitForTimeout(4000);
    return this.page.locator('[data-testid="account-link-to-ui-manage-shops-button"]');
  }

  /**
   * Check if Link bouton and manage shop bouton are visible
   */
  async multiStoreCheckIsLinkedOneShopAssociate() {
    const accountTitle = this.page.locator('.title', {hasText: ' PRESTASHOP '});
    await accountTitle.isVisible();
    const btnLink = this.page.locator('[data-testid="account-link-to-ui-link-shop-button"]');
    await btnLink.isVisible();
    const manageShopBtn = this.page.locator('[data-testid="account-link-to-ui-manage-shops-button"]');
    await manageShopBtn.isVisible();
    await this.page.waitForTimeout(5000)
  }

  /**
   * Click back btn and return to module Manager page
   */
  async returnToModuleManager() {
    const locators = [
      this.page.getByRole('link', {name: ' Back '}),
      this.page.locator('#page-header-desc-configuration-module-back')
    ];
    for (const locator of locators)
      if (await locator.isVisible()) {
        await locator.click();
        await this.page.waitForTimeout(5000);
        await this.page.reload();
        return;
      }
  }
  /**
   *
   * Opens the PrestaShop Account configuration popup and verifies the redirection
   * Clicks the 'Configure' link, triggers a popup by clicking the 'Configure' button, waits for the new page to load
   * Expect url and title
   * @return {Promise<Page>}
   */
  async openLinkedAccountPopup(): Promise<Page> {
    const pageTitle = await this.getPageMainTitle();
    const pageTitleOldPsVersion = await this.getPageMainTitleOldPsVersion();
    if (pageTitle === moduleManagerPagesLocales.moduleManager.en_EN.title) {
      const moduleContainer = await this.page.locator('#modules-list-container-440');
      const dropdownBtn = moduleContainer.locator('.btn.btn-outline-primary.dropdown-toggle');
      const upgradeBtn = moduleContainer.getByRole('button', {name: 'Upgrade'});
      if (await upgradeBtn.isVisible()) {
        await dropdownBtn.click();
      }
      await moduleContainer.getByRole('link', {name: 'Configure'}).click();
      const [newPage] = await Promise.all([
        this.page.context().waitForEvent('page'),
        this.page.locator('[data-testid="account-link-to-ui-manage-shops-button"]').click()
      ]);
      await newPage.waitForTimeout(5000);
      expect(newPage.url()).toContain('authv2-preprod');
      return newPage;
    } else if (pageTitleOldPsVersion === moduleManagerPagesLocales.moduleManager.en_EN.titleOldPsVersion) {
      const moduleContainer = await this.page.locator('tr:not([style*="display: none"])');
      const dropDownParent = moduleContainer.locator('.actions');
      const dropdownBtn = dropDownParent.locator('.caret');
      const upgradeBtn = dropDownParent.locator('.btn.btn-warning', {hasText: ' Update it! '});
      if (await upgradeBtn.isVisible()) {
        await dropdownBtn.click({force: true});
      }
      await moduleContainer.getByRole('link', {name: 'Configure'}).click();
      const [newPage] = await Promise.all([
        this.page.context().waitForEvent('page'),
        this.page.locator('[data-testid="account-link-to-ui-manage-shops-button"]').click()
      ]);
      await newPage.waitForTimeout(5000);
      expect(newPage.url()).toContain('authv2-preprod');
      return newPage;
    }
    throw new Error('Popup Account can not be open');
  }

  /**
   * Select de FO url and click Diassociate
   */
  async selectUrlAndDiassociate(newPage: Page) {
    const card = newPage.getByRole('checkbox', {name: `PrestaShop language icon ${Globals.base_url_fo}`});
    await card.locator('[data-test="shoplist-shop-unlink"]').click();
    await newPage.locator('[data-test="confirm-unlink-shop"]').click({timeout: 5000});
    await newPage.waitForLoadState('load', {timeout: 2000});
  }

  /**
   *
   * Opens the PrestaShop Account after dissociation
   * Clicks the 'manage linked store' link waits for the new page to load
   * Expect url and title
   * @return {Promise<Page>}
   */
  async multistoreOpenAccountPopupAfterDissociation(): Promise<Page> {
    const [newPage] = await Promise.all([
      this.page.context().waitForEvent('page'),
      this.page.locator('[data-testid="account-link-to-ui-manage-shops-button"]').click()
    ]);
    await newPage.waitForTimeout(5000);
    expect(newPage.url()).toContain('accounts.distribution-preprod');
    return newPage;
  }

  /**
   * Select de FO/Shop2 url and click Diassociate
   */
  async multistoreSelectUrlAndDiassociate(newPage: Page) {
    const card = newPage.getByRole('checkbox', {name: `Shop2 language icon ${Globals.base_url_fo}/shop2`});
    await card.isVisible();
    await card.locator('[data-test="shoplist-shop-unlink"]').click();
    await newPage.locator('[data-test="confirm-unlink-shop"]').click({timeout: 5000});
    await newPage.waitForLoadState('load', {timeout: 2000});
  }

  /**
   * Select de FO url and click Diassociate
   */
  async diassociateFirstCard(newPage: Page) {
    let page = newPage;

    while (true) {
      const card = page.locator('[data-test="shop-card"]');
      await page.waitForTimeout(4000);
      const countCard = await card.count();
      console.log(`Number of Stores Linked: ${countCard}`);
      if (countCard === 0) break;
      await card.locator('[data-test="shoplist-shop-unlink"]').first().click();
      await page.locator('[data-test="confirm-unlink-shop"]').click();
      await page.waitForSelector('[data-test="shoplist-shop-unlink"]', {state: 'detached'});
      await page.waitForEvent('close');
      const [popup] = await Promise.all([
        page.context().waitForEvent('page'),
        page.locator('[data-testid="account-link-to-ui-manage-shops-button"]').click()
      ]);
      await popup.waitForLoadState('domcontentloaded');
      await popup.waitForTimeout(1000);

      page = popup;
    }

    return page;
  }
}
