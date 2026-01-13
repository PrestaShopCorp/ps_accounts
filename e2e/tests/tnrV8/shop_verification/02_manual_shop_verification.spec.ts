//Import
import {expect, test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';

gotToModuleManagerPage('Check if you can verified manually', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  const db = new DbRequest();
  await test.step('check if you are unverified', async () => {
    await db.deleteAccountsInfo();
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    expect(status).toBeFalsy();
    const apiStatus = await pm.fromConfigureAccountPage().getStoreInformationFromApi(0);
    expect(apiStatus).toBeFalsy();
  });
  await test.step('verifiy manually and check if verified', async () => {
    await pm.fromConfigureAccountPage().verifyManualy();
    await pm.fromConfigureAccountPage().checkVerificationSucced();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    expect(status).toBeTruthy();
    const apiStatus = await pm.fromConfigureAccountPage().getStoreInformationFromApi(0);
    expect(apiStatus).toBeTruthy();
  });
});
