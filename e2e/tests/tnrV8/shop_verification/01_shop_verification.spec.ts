//Import
import {expect, test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';

gotToModuleManagerPage('Check if you are verified', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  await test.step('check module module version in db', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    expect(status).toBeTruthy();
    const apiStatus = await pm.fromConfigureAccountPage().getStoreInformationFromApi();
    expect(apiStatus).toBeTruthy()
  });
});
