import {MyFixtures} from 'types/fixture';
import {loginFixture} from '~/fixtures/login.fixture';
import {PageManager} from '~/pages/managerPage';

export const gotToModuleManagerPage = loginFixture.extend<MyFixtures>({
  gotToModuleManagerPage: async ({loginFixture, page}, use) => {
    const pm = new PageManager(loginFixture);
    console.log(page.url());
    if (await pm.fromDashboardPage().getShopVersion()) {
      await pm.fromBasePage().goToModulesManagerOldPsVersion();
    } else {
      await pm.fromBasePage().goToSubMenu(pm.fromBasePage().modulesParentLink, pm.fromBasePage().moduleManagerLink);
    }
    await use(page);
  }
});
