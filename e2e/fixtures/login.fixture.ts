import {test as base, expect} from '@playwright/test';
// import {MyFixtures} from 'types/fixture';
import {PageManager} from '~/pages/managerPage';
import {MyFixtures} from '~/types/fixture';
import {Globals} from '~/utils/globals';

export const loginFixture = base.extend<MyFixtures>({
  loginFixture: async ({page}, use) => {
    const pm = new PageManager(page);
    await page.goto(Globals.base_url);
    await pm.fromBasePage().handleConnectionMode();
    await pm.fromBasePage().login(Globals.admin_email, Globals.admin_password);
    await page.waitForTimeout(5000);
    if (await pm.fromDashboardPage().isPopupVisible()) {
      await pm.fromDashboardPage().closePopup();
    }
    await pm.fromDashboardPage().getPageMainTitle();
    await use(page);
  }
});
