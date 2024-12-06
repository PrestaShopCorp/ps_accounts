//Import
import {test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/login.fixture';
import {PageManager} from '~/pages/managerPage';

//Var
gotToModuleManagerPage('Check if module is installed', async ({page}) => {
  const pm = new PageManager(page);
  await test.step('check if module is installed and module version', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().verifyAccountVersion();
  });
});
