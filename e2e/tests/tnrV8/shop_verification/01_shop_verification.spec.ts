//Import
import {expect, test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';

gotToModuleManagerPage('Check if you are verified', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  await test.step('check you verified and getshopstatus return verified:true', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    expect(status).toBeTruthy();
    const apiStatus = await pm.fromConfigureAccountPage().getStoreInformationFromApi(0);
    expect(apiStatus).toBeTruthy();
  });
});
