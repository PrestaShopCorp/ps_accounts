import {MyFixtures} from '~/types/fixture';
import {loginFixture} from '~/fixtures/login.fixture';
import {PageManager} from '~/pages/managerPage';

export const gotToModuleManagerPage = loginFixture.extend<MyFixtures>({
  gotToModuleManagerPage: async ({loginFixture, page}, use) => {
    const pm = new PageManager(loginFixture);
    if (await pm.fromDashboardPage().isPopupVisible()) {
      await pm.fromDashboardPage().closePopup();
    }
    if (await pm.fromDashboardPage().getShopVersion()) {
      await pm.fromBasePage().goToModulesManagerOldPsVersion();
    } else {
      await pm.fromBasePage().goToModulesManagerNewPsVersion();
    }
    await use(page);
  }
});
