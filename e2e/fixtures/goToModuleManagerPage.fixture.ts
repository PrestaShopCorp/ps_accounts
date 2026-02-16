import {MyFixtures} from '~/types/fixture';
import {loginFixture} from '~/fixtures/login.fixture';
import {PageManager} from '~/pages/managerPage';

export const gotToModuleManagerPage = loginFixture.extend<MyFixtures>({
  gotToModuleManagerPage: async ({loginFixture, page}, use) => {
    const pm = new PageManager(loginFixture);
    if (await pm.fromDashboardPage().getShopVersion()) {
      await pm.fromBasePage().goToModulesManagerOldPsVersion();
      await page.waitForLoadState('networkidle', {timeout: 4000});
    } else {
      await pm.fromBasePage().goToSubMenu(pm.fromBasePage().modulesParentLink, pm.fromBasePage().moduleManagerLink);
      await page.waitForLoadState('networkidle', {timeout: 4000});
    }
    await use(page);
  }
});
