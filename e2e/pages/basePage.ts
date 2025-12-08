// Import pages
import {Locator, Page} from '@playwright/test';
import {Globals} from '../utils/globals';

export default class BasePage {
  /* <<<<<<<<<<<<<<< MODULEMANAGER >>>>>>>>>>>>>>>>>>>>>> */
  public readonly page: Page;
  public readonly modulesParentLink: Locator;
  public readonly moduleManagerLink: Locator;
  public readonly shopParametersGeneralParentLink: Locator;
  public readonly shopParametersGeneralLink: Locator;
  public readonly shopAdvancedParametersParentLink: Locator;
  public readonly shopAdvancedParametersMultiStoreLink: Locator;

  public readonly openMenuSelector: (menuSelector: Locator) => Locator;

  constructor(page: Page) {
    this.page = page;

    /* <<<<<<<<<<<<<<< MODULEMANAGER >>>>>>>>>>>>>>>>>>>>>> */
    this.modulesParentLink = this.page.locator('#subtab-AdminParentModulesSf');
    this.moduleManagerLink = this.page.locator('#subtab-AdminModulesSf');
    this.shopParametersGeneralParentLink = this.page.locator('#subtab-ShopParameters');
    this.shopParametersGeneralLink = this.page.locator('#subtab-AdminParentPreferences');
    this.shopAdvancedParametersParentLink = this.page.locator('#subtab-AdminAdvancedParameters');
    this.shopAdvancedParametersMultiStoreLink = this.page.locator('#subtab-AdminShopGroup');

    this.openMenuSelector = (menuSelector) => menuSelector.and(this.page.locator('.open'));
  }

  /**
   * Click on connect with another method if visible
   */
  async connectWithAnotherMethod(): Promise<void> {
    if (await this.page.getByRole('link', {name: 'Connect with another method'}).isVisible()) {
      await this.page.getByRole('link', {name: 'Connect with another method'}).click();
    }
  }

  /**
   * Click on connect with Secure Mode if visible
   */
  async connectSecureModeMethodLink(): Promise<void> {
    if (await this.page.getByRole('link', {name: 'log in to secure mode (https://)'}).isVisible()) {
      await this.page.getByRole('link', {name: 'log in to secure mode (https://)'}).click();
    }
  }

  /**
   * Handle connection mode if necessary
   */
  async handleConnectionMode(): Promise<void> {
    await this.connectWithAnotherMethod();
    await this.connectSecureModeMethodLink();
  }

  /**
   * Get the page title
   * @param page {Page} The browser tab
   * @return {Promise<string>}
   * The page title
   */
  async login(email: string = Globals.admin_email, password: string = Globals.admin_password) {
    await this.page.locator('#email').fill(email);
    await this.page.locator('#passwd').fill(password);
    await this.page.getByRole('button', {name: 'Log in'}).click();
  }

  /**
   * Is a menu with submenus open
   * @param parentLocator {Locator}
   * @returns
   * True if visible
   */
  isMenuOpen(parentLocator: Locator): Promise<boolean> {
    return this.openMenuSelector(parentLocator).isVisible();
  }

  /**
   * Open a Menu if there's submenus
   * @param parentLocator {Locator}
   */
  async openMenu(parentLocator: Locator): Promise<void> {
    if (!(await this.isMenuOpen(parentLocator))) {
      await Promise.all([
        parentLocator.waitFor({state: 'visible'}),
        parentLocator.click(),
        this.openMenuSelector(parentLocator).waitFor({state: 'visible'})
      ]);
    }
  }

  /**
   * Go to a menu
   * @param parentLocator {Locator}
   * @param linkLocator {Locator}
   */
  async goToSubMenu(parentLocator: Locator, linkLocator: Locator | null = null): Promise<void> {
    if (!linkLocator) {
      await parentLocator.click();
      await this.page.waitForLoadState('domcontentloaded');
    } else {
      await this.openMenu(parentLocator);
      await linkLocator.click();
      await this.page.waitForLoadState('domcontentloaded');
    }
  }

  async goToModulesManagerOldPsVersion() {
    await this.page.locator('.icon-AdminParentModules').hover();
    await this.page.locator('#subtab-AdminModules').filter({hasText: 'Modules and Services'}).click();
    await this.page.waitForLoadState('domcontentloaded');
  }
  async goToPreferencesOldPsVersion() {
    await this.page.locator('.icon-AdminParentPreferences').hover();
    await this.page.locator('#subtab-AdminPreferences').filter({hasText: 'General'}).click();
  }
  async goToMultiStoreOldPsVersion() {
    await this.page.locator('.icon-AdminTools').hover();
    await this.page.locator('#subtab-AdminShopGroup').filter({hasText: 'Multistore'}).click();
  }
}
