//Import
import {test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';

gotToModuleManagerPage('Check if module is installed', async ({gotToModuleManagerPage}) => {
  console.log('test');
  // const pm = new PageManager(gotToModuleManagerPage);
  // await test.step('check if module is installed and module version', async () => {
  //   await pm.fromModuleManagePage().getPageMainTitle();
  //   await pm.fromModuleManagePage().isAccountVisible();
  //   await pm.fromModuleManagePage().verifyAccountVersion();
  // });
  // await test.step('check if cloudflare challange is passed', async () => {
  //   const popup = await pm.fromModuleManagePage().openAccountPopup();
  //   await pm.fromModuleManagePage().accountPopupTiteleIsVisible(popup);
  // });
});
