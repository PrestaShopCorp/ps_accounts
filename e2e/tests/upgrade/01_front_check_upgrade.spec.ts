//Import
import {expect, test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';
import HealthCheckApi from '~/services/api/healthCheckApi';
import { modulePsAccount } from '~/data/local/modules/modulePsAccount';

gotToModuleManagerPage('Check module is upgrade', async ({gotToModuleManagerPage}) => {
  let pm = new PageManager(gotToModuleManagerPage);
  let dbRequest = new DbRequest();
  let healthCheckApi = new HealthCheckApi()
  await test.step('check module module version in db', async () => {
    const moduleVersion = await dbRequest.returnModuleVersion();
    expect(moduleVersion).toBe('7.1.2');
  });
  await test.step('check if module is installed and module version', async () => {
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
  });
  await test.step('upload new version and check if installed and version ', async () => {
    await pm.fromModuleManagePage().uploadZip();
    await pm.fromModuleManagePage().getPageMainTitle();
    await pm.fromModuleManagePage().isAccountVisible();
  });
  await test.step('check module version in db and if is linked', async () => {
    const moduleVersion = await dbRequest.returnModuleVersion();
    expect(moduleVersion).toBe(modulePsAccount.version);
    const isLinked = await healthCheckApi.isShopLinked()
    console.log(isLinked);
  });
});
