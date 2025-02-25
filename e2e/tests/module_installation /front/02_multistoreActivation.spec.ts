//Import
import {test} from '@playwright/test';
import {activeMultistoreAndCreateShop} from '~/fixtures/activeMultiStoreAndCreateShop.fixture';
import {PageManager} from '~/pages/managerPage';

activeMultistoreAndCreateShop('MultiStore', async ({activeMultistoreAndCreateShop}) => {
  const pm = new PageManager(activeMultistoreAndCreateShop);
  await test.step('check if multiStore is Created', async () => {
    if (await pm.fromDashboardPage().getShopVersion()) {
      await pm.fromModuleManagePage().isMultistoreVisibleOldVersion();
    } else {
      await pm.fromModuleManagePage().isMultistoreVisible();
    }
  });
});
