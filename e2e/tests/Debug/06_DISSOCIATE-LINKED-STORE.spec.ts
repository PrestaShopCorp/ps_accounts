//Import
import {test, expect} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';

gotToModuleManagerPage('Check module disassociation', async ({gotToModuleManagerPage}) => {
  let pm = new PageManager(gotToModuleManagerPage);
  let dbRequest = new DbRequest();
  await test.step('diassociate to account and check if unlinked', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    const popup = await pm.fromPopupAccountPage().openLinkedAccountPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
     await gotToModuleManagerPage.pause()
    await pm.fromPopupAccountPage().diassociateFirstCard(popup);
  });
});
