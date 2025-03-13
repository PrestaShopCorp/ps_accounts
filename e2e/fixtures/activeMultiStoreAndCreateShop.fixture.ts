import {MyFixtures} from 'types/fixture';
import {loginFixture} from '~/fixtures/login.fixture';
import {PageManager} from '~/pages/managerPage';

export const activeMultistoreAndCreateShop = loginFixture.extend<MyFixtures>({
  activeMultistoreAndCreateShop: async ({loginFixture, page}, use) => {
    const pm = new PageManager(loginFixture);
    if (await pm.fromDashboardPage().getShopVersion()) {
      await pm.fromBasePage().goToPreferencesOldPsVersion();
      await pm.fromGeneralPage().activeMultiStoreOldPsVersion();
      await pm.fromBasePage().goToMultiStoreOldPsVersion();
      await pm.fromMultiStorePage().createNewStore();
      await pm.fromBasePage().goToModulesManagerOldPsVersion();
    } else {
      await pm
        .fromBasePage()
        .goToSubMenu(pm.fromBasePage().shopParametersGeneralParentLink, pm.fromBasePage().shopParametersGeneralLink);
      await pm.fromGeneralPage().getPageMainTitle();
      await pm.fromGeneralPage().activeMultiStore();
      await pm
        .fromBasePage()
        .goToSubMenu(
          pm.fromBasePage().shopAdvancedParametersParentLink,
          pm.fromBasePage().shopAdvancedParametersMultiStoreLink
        );
      await pm.fromMultiStorePage().getPageMainTitle();
      await pm.fromMultiStorePage().createNewStore();
      await pm.fromBasePage().goToSubMenu(pm.fromBasePage().modulesParentLink, pm.fromBasePage().moduleManagerLink);
    }
    await use(page);
  }
});
