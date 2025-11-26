import {test, expect} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';

gotToModuleManagerPage('Check module still Linked after delete tokens and change the physical uri', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  const dbRequest = new DbRequest();
   await test.step('Delete Token and change physical uri', async () => {
    await dbRequest.deleteTokens();
    await dbRequest.updateUri()
    await dbRequest.getPsShopUrlUri();
  })
  await test.step('Go to Account Configuration page', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
  });
  await test.step('check if linked in Shop', async () => {
    const isLinked = await pm.fromPopupAccountPage().checkIsLinked();
    expect(isLinked).toBeVisible()
    const manageLinkedStores = await pm.fromPopupAccountPage().multiStoreCheckIsLinkedAllShopAssociate();
    expect(manageLinkedStores).toBeVisible();
  });
});
