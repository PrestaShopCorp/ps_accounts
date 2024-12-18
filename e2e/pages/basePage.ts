// Import pages
import {CommonPage} from '@prestashopcorp/tests-framework';
import type {FrameLocator, Locator, Page} from '@playwright/test';
import {Globals} from '../utils/globals';

export default class BasePage extends CommonPage {
  public readonly emailInput: Locator;
  public readonly passwordInput: Locator;
  public readonly submitLoginButton: Locator;
  private readonly anotherMethodLink: Locator;
  private readonly secureModeMethodLink: Locator;

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

    /* <<<<<<<<<<<<<<< Selectors >>>>>>>>>>>>>>>>>>>>>> */
    this.emailInput = this.page.locator('#email');
    this.passwordInput = this.page.locator('#passwd');
    this.submitLoginButton = this.page.getByRole('button', {name: 'Log in'});
    this.anotherMethodLink = this.page.getByRole('link', {name: 'Connect with another method'});
    this.secureModeMethodLink = this.page.getByRole('link', {name: 'log in to secure mode (https://)'});

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
   * Is Link for Secure Mode method is visible
   * @returns
   * True if another login method visible
   */
  isSecureModeMethodLinkVisible(): Promise<boolean> {
    return this.isVisible(this.secureModeMethodLink, 1000);
  }

  /**
   * Click on connect with Secure Mode
   */
  async connectSecureModeMethodLink(): Promise<void> {
    await this.clickAndWaitForLoadState(this.secureModeMethodLink, 'networkidle');
  }

  /**
   * Is Link for another method is visible
   * @returns
   * True if another login method visible
   */
  isAnotherMethodLinkVisible(): Promise<boolean> {
    return this.isVisible(this.anotherMethodLink, 1000);
  }

  /**
   * Click on connect with another method
   */
  async connectWithAnotherMethod(): Promise<void> {
    await this.clickAndWaitForLoadState(this.anotherMethodLink, 'networkidle');
  }

  /**
   * Get the page title
   * @param page {Page} The browser tab
   * @return {Promise<string>}
   * The page title
   */
  async login(email: string = Globals.admin_email, password: string = Globals.admin_password) {
    await this.setValue(this.emailInput, email);
    await this.setValue(this.passwordInput, password);
    await this.clickAndWaitForLoadState(this.submitLoginButton, 'domcontentloaded');
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
