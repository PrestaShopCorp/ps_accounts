//Import
import {test, expect} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';

gotToModuleManagerPage('Check module disassociation', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  const dbRequest = new DbRequest();
  await test.step('diassociate first shop to account and check if unlinked', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    const popup = await pm.fromPopupAccountPage().openLinkedAccountPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
    await pm.fromPopupAccountPage().selectUrlAndDiassociate(popup);
    await pm.fromPopupAccountPage().multiStoreCheckIsLinkedOneShopAssociate();
  });
  await test.step('diassociate seconde shop to account and check if linked', async () => {
    const popup = await pm.fromPopupAccountPage().multistoreOpenAccountPopupAfterDissociation();
    await pm.fromPopupAccountPage().multistoreSelectUrlAndDiassociate(popup);
  });
  await test.step('check if unlinked in Shop', async () => {
    const isUnLinked = await pm.fromPopupAccountPage().checkIsLinked();
    expect(isUnLinked).toBeHidden();
  });
  await test.step('check if unlinked in DB', async () => {
    const checkClientUuidValue = await dbRequest.getPsConfigurationData('PS_ACCOUNTS_USER_FIREBASE_UUID');
    expect(checkClientUuidValue.value).toBeNull();
  });
});
