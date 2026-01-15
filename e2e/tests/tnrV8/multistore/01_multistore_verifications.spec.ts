//Import
import {test, expect} from '@playwright/test';
import {activeMultistoreAndCreateShop} from '~/fixtures/activeMultiStoreAndCreateShop.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';

activeMultistoreAndCreateShop('Check Multistore Verifications', async ({activeMultistoreAndCreateShop}) => {
  const pm = new PageManager(activeMultistoreAndCreateShop);
  const dbRequest = new DbRequest();
  await test.step('check if multiStore is Created', async () => {
    const checkUrl = await dbRequest.getPsShopUrl();
    expect(checkUrl.length).toBeGreaterThan(1);
  });
  await test.step('check alert Block is Displayed when you choose all store', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
    await pm.fromConfigureAccountPage().displayAllStoreInformations();
    await pm.fromConfigureAccountPage().getMultistoreAlert();
  });
  await test.step('check default shop is verified UI and getshopstatus return verified:true ', async () => {
    await pm.fromConfigureAccountPage().displayDefaultStoreInformations();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    expect(status).toBeTruthy();
    const apiStatus = await pm.fromConfigureAccountPage().getStoreInformationFromApi(0);
    expect(apiStatus).toBeTruthy();
  });
  await test.step('check seconde shop is verified UI and getshopstatus return verified:false', async () => {
    await pm.fromConfigureAccountPage().displaySecondeStoreInformations();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    expect(status).toBeFalsy();
    const apiStatus = await pm.fromConfigureAccountPage().getStoreInformationFromApi(1);
    expect(apiStatus).toBeFalsy();
  });
  await test.step('check seconde shop is verified UI and getshopstatus return verified:true', async () => {
    await pm.fromConfigureAccountPage().verifyManualy();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    expect(status).toBeTruthy();
    const apiStatus = await pm.fromConfigureAccountPage().getStoreInformationFromApi(1);
    expect(apiStatus).toBeTruthy();
  });
});
