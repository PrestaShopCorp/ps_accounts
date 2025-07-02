//Import
import {expect, test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';
import HealthCheckApi from '~/services/api/healthCheckApi';
import {modulePsAccount} from '~/data/local/modules/modulePsAccount';

gotToModuleManagerPage('Check module is upgrade after association', async ({gotToModuleManagerPage}) => {
  let pm = new PageManager(gotToModuleManagerPage);
  let dbRequest = new DbRequest();
  let moduleVersionBefore: string;
  await test.step('check module module version in db', async () => {
    moduleVersionBefore = await dbRequest.returnModuleVersion();
    console.log(moduleVersionBefore);
    expect(moduleVersionBefore).not.toBeNull();
  });
  await test.step('association to ps_account', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
    const popup = await pm.fromPopupAccountPage().openAccountPopup();
    await pm.fromPopupAccountPage().accountPopupTiteleIsVisible(popup);
    await pm.fromPopupAccountPage().connectToAccountWithMail(popup);
    await pm.fromPopupAccountPage().checkIsLinked();
  });
  await test.step('return to Module Manager Page ', async () => {
    await pm.fromPopupAccountPage().returnToModuleManager();
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
  });
  await test.step('upload new version and check if installed and version ', async () => {
    await pm.fromModuleManagePage().uploadZip();
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
  });
  await test.step('check module version in db and if is linked', async () => {
    const moduleVersionAfter = await dbRequest.returnModuleVersion();
    console.log(moduleVersionAfter);
    expect(moduleVersionAfter).not.toBeNull;
    expect(moduleVersionAfter).not.toBe(moduleVersionBefore);
  });
});
