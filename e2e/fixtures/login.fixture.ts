import {test as base, expect} from '@playwright/test';
import {ShippingFixtures} from 'types/fixture';
import {PageManager} from '~/pages/managerPage';
import {Globals} from '~/utils/globals';

export const gotToModuleManagerPage = base.extend<ShippingFixtures>({
  page: async ({page}, use) => {
    const pm = new PageManager(page);
    await page.goto(Globals.base_url);
    if (await pm.frombasePage().isAnotherMethodLinkVisible()) {
      await pm.frombasePage().connectWithAnotherMethod();
    }else if (await pm.fromDashboardPage().isSecureModeMethodLinkVisible()) {
      await pm.fromDashboardPage().connectSecureModeMethodLink();
    }
    await pm.frombasePage().login(Globals.admin_email, Globals.admin_password);
    await page.waitForTimeout(5000);
    if (await pm.fromDashboardPage().isPopupVisible()) {
      await pm.fromDashboardPage().closePopup();
    }
    await pm.fromDashboardPage().getPageMainTitle();
    await pm.frombasePage().goToSubMenu(pm.frombasePage().modulesParentLink, pm.frombasePage().moduleManagerLink);
    await use(page);
  }
});
