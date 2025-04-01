import {Page} from '@playwright/test';
import DashboardPage from '~/pages/dashboard/dashboardPage';
import ModuleManagerPage from '~/pages/moduleManager/moduleManagerPage';
import BasePage from 'pages/basePage';
import GeneralPage from '~/pages/shopParameters/generalPage';
import MultiStorePage from '~/pages/advancedParameters/multiStorePage';

export class PageManager {
  private readonly page: Page;
  private readonly dashboardPage: DashboardPage;
  private readonly moduleManagePage: ModuleManagerPage;
  private readonly basePage: BasePage;
  private readonly generalPage: GeneralPage;
  private readonly multiStorePage: MultiStorePage;

  constructor(page: Page) {
    this.page = page;
    this.dashboardPage = new DashboardPage(this.page);
    this.moduleManagePage = new ModuleManagerPage(this.page);
    this.basePage = new BasePage(this.page);
    this.generalPage = new GeneralPage(this.page);
    this.multiStorePage = new MultiStorePage(this.page);
  }
  /* <<<<<<<<<<<<<<< Dashboards Page >>>>>>>>>>>>>>>>>>>>>> */

  fromDashboardPage() {
    return this.dashboardPage;
  }
  /* <<<<<<<<<<<<<<< Module Manager Page >>>>>>>>>>>>>>>>>>>>>> */

  fromModuleManagePage() {
    return this.moduleManagePage;
  }

  /* <<<<<<<<<<<<<<< Base Page >>>>>>>>>>>>>>>>>>>>>> */

  fromBasePage() {
    return this.basePage;
  }

  /* <<<<<<<<<<<<<<< shopParameters/General Page  >>>>>>>>>>>>>>>>>>>>>> */

  fromGeneralPage() {
    return this.generalPage;
  }
  /* <<<<<<<<<<<<<<< advencedParameters/General Page  >>>>>>>>>>>>>>>>>>>>>> */

  fromMultiStorePage() {
    return this.multiStorePage;
  }
}
