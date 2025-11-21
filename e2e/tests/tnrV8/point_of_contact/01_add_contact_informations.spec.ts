//Import
import {expect, test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';

gotToModuleManagerPage('Check if you are verified @1', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  await test.step('check you verified', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
    const status = await pm.fromConfigureAccountPage().getStoreInformation();
    if (status) {
      expect(status).toBeTruthy();
    } else {
      await pm.fromConfigureAccountPage().verifyManualy();
    }
  });
  await test.step('Sign in to add contact information', async () => {
    await pm.fromConfigureAccountPage().checkSignInisVisible();
    const popup = await pm.fromConfigureAccountPage().clickSignInAndOpenPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
  });
  await test.step('Check if Sign In', async () => {
    await pm.fromConfigureAccountPage().checkIsSigned();
  });
});
