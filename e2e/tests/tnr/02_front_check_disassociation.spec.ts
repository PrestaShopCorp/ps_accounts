//Import
import {test, expect} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import HealthCheckApi from '~/services/api/healthCheckApi';

gotToModuleManagerPage('Check module disassociation', async ({gotToModuleManagerPage}) => {
  let pm = new PageManager(gotToModuleManagerPage);
  let healthCheckApi = new HealthCheckApi();
  await test.step('associate to account and check if linked', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    const popup = await pm.fromPopupAccountPage().openLinkedAccountPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    await popup.pause();

    await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
    await popup.pause();

  });
});
