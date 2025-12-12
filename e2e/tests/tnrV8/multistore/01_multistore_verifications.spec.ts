//Import
import {test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';

gotToModuleManagerPage('Check Multistore Verifications', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  await test.step('check alert Block is Displaayed when you choose all store', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
    await pm.fromConfigureAccountPage().displayAllStoreInformations();
    await pm.fromConfigureAccountPage().getMultistoreAlert();
  });
});
