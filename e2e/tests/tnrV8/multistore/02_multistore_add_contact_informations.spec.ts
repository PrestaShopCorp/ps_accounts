//Import
import {test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';

gotToModuleManagerPage('Check Multistore Verifications', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  await test.step('check alert Block is Displayed when you choose all store', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    await pm.fromModuleManagePage().goToAccountConfigurePage();
  });
  await test.step('Sign in to add contact information First Shop', async () => {
    await pm.fromConfigureAccountPage().displayDefaultStoreInformations();
    await pm.fromConfigureAccountPage().checkSignInisVisible();
    const popup = await pm.fromConfigureAccountPage().clickSignInAndOpenPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
  });
  await test.step('Check if Sign In First Shop', async () => {
    await pm.fromConfigureAccountPage().checkIsSigned();
  });
    await test.step('Sign in to add contact information Seconde Shop', async () => {
      await pm.fromConfigureAccountPage().displaySecondeStoreInformations();
      await pm.fromConfigureAccountPage().checkSignInisVisible();
      const popup = await pm.fromConfigureAccountPage().clickSignInAndOpenPopup();
      await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
      await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
    });
    await test.step('Check if Sign In Seconde Shop', async () => {
      await pm.fromConfigureAccountPage().checkIsSigned();
    });
});
