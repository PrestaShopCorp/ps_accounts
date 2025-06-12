//Import
import {test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';

gotToModuleManagerPage('Check if module is installed', async ({gotToModuleManagerPage}) => {
  let pm = new PageManager(gotToModuleManagerPage);
  await test.step('check if module is installed and module version', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    const popup = await pm.fromPopupAccountPage().openAccountPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    // await popup.pause()
    await pm.fromPopupAccountPage().connectToAccount(popup);
  });
});
