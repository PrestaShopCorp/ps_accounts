//Import
import {test, expect} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';

gotToModuleManagerPage('Check module association', async ({gotToModuleManagerPage}) => {
  let pm = new PageManager(gotToModuleManagerPage);
  let dbRequest = new DbRequest();
  await test.step('associate to account and check if linked', async () => {
    await pm.fromModuleManagePage().isAccountVisible();
    const popup = await pm.fromPopupAccountPage().openAccountPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
    await pm.fromPopupAccountPage().multisotreAssociateAndClickBoBtn(popup);
    const isLinked = await pm.fromPopupAccountPage().checkIsLinked();
    expect(isLinked).toBeVisible();
  });
  await test.step('check if linked in DB', async () => {
    const checkClientUuidValue = await dbRequest.getPsConfigurationData('PS_ACCOUNTS_USER_FIREBASE_UUID');
    expect(checkClientUuidValue.value).not.toBeNull();
  });
});
