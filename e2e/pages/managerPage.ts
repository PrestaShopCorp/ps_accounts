import {Page} from '@playwright/test';
import DashboardPage from '~/pages/dashboard/dashboardPage';
import ModuleManagerPage from '~/pages/moduleManager/moduleManagerPage';
import BasePage from '~/pages/basePage';
import GeneralPage from '~/pages/shopParameters/generalPage';
import MultiStorePage from '~/pages/advancedParameters/multiStorePage';
import PopupAccountPage from '~/pages/popupAccount/popupAccountPage';

export class PageManager {
  private readonly page: Page;
  private readonly dashboardPage: DashboardPage;
  private readonly moduleManagePage: ModuleManagerPage;
  private readonly basePage: BasePage;
  private readonly generalPage: GeneralPage;
  private readonly multiStorePage: MultiStorePage;
  private readonly popupAccountPage: PopupAccountPage;

  constructor(page: Page) {
    this.page = page;
    this.dashboardPage = new DashboardPage(this.page);
    this.moduleManagePage = new ModuleManagerPage(this.page);
    this.basePage = new BasePage(this.page);
    this.generalPage = new GeneralPage(this.page);
    this.multiStorePage = new MultiStorePage(this.page);
    this.popupAccountPage = new PopupAccountPage(this.page)
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

  /* <<<<<<<<<<<<<<< Popup Account Page  >>>>>>>>>>>>>>>>>>>>>> */

  fromPopupAccountPage() {
    return this.popupAccountPage;
  }
}
