import {Page} from '@playwright/test';
import DashboardPage from '~/pages/dashboard/dashboardPage';
import ModuleManagerPage from '~/pages/moduleManager/moduleManagerPage';
import BasePage from 'pages/basePage'

export class PageManager {
  private readonly page: Page;
  private readonly dashboardPage: DashboardPage;
  private readonly moduleManagePage: ModuleManagerPage;
  private readonly basePage: BasePage;

  constructor(page: Page) {
    this.page = page;
    this.dashboardPage = new DashboardPage(this.page);
    this.moduleManagePage = new ModuleManagerPage(this.page);
    this.basePage = new BasePage(this.page);
  }
  /* <<<<<<<<<<<<<<< Dashboards Pages >>>>>>>>>>>>>>>>>>>>>> */

  fromDashboardPage() {
    return this.dashboardPage;
  }
  /* <<<<<<<<<<<<<<< Module Manager Pages >>>>>>>>>>>>>>>>>>>>>> */

  fromModuleManagePage() {
    return this.moduleManagePage;
  }

  /* <<<<<<<<<<<<<<< Module Manager Pages >>>>>>>>>>>>>>>>>>>>>> */

  frombasePage() {
    return this.basePage;
  }
}
