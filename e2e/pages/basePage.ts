// Import pages
import {CommonPage} from '@prestashopcorp/tests-framework';
import type {FrameLocator, Locator, Page} from '@playwright/test';
import {Globals} from '../utils/globals';

export default class BasePage extends CommonPage {

  /* <<<<<<<<<<<<<<< SHIPPING >>>>>>>>>>>>>>>>>>>>>> */
  public readonly shippingParentLink: Locator;
  public readonly shippingCarriersLink: Locator;
  public readonly shippingPreferencesLink: Locator;

  /* <<<<<<<<<<<<<<< MODULEMANAGER >>>>>>>>>>>>>>>>>>>>>> */
  public readonly modulesParentLink: Locator;
  public readonly moduleManagerLink: Locator;

  public readonly openMenuSelector: (menuSelector: Locator) => Locator;

  constructor(page: Page) {
    super(page);

    /* <<<<<<<<<<<<<<< SHIPPING >>>>>>>>>>>>>>>>>>>>>> */
    this.shippingParentLink = this.page.locator('#subtab-AdminParentShipping');
    this.shippingCarriersLink = this.page.locator('#subtab-AdminCarriers');
    this.shippingPreferencesLink = this.page.locator('#subtab-AdminShipping');

    /* <<<<<<<<<<<<<<< MODULEMANAGER >>>>>>>>>>>>>>>>>>>>>> */
    this.modulesParentLink = this.page.locator('#subtab-AdminParentModulesSf');
    this.moduleManagerLink = this.page.locator('#subtab-AdminModulesSf');

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
    return this.isVisible(this.openMenuSelector(parentLocator), 1000);
  }

  /**
   * Open a Menu if there's submenus
   * @param parentLocator {Locator}
   */
  async openMenu(parentLocator: Locator): Promise<void> {
    if (!(await this.isMenuOpen(parentLocator))) {
      await Promise.all([
        this.waitForSelectorAndClick(parentLocator, 133000),
        this.waitForVisibleSelector(this.openMenuSelector(parentLocator))
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
      await this.clickAndWaitForLoadState(parentLocator, 'domcontentloaded');
    } else {
      await this.openMenu(parentLocator);
      await this.clickAndWaitForLoadState(linkLocator, 'domcontentloaded');
    }
  }
}
