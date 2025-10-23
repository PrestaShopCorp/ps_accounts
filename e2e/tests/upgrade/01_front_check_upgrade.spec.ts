//Import
import {expect, test} from '@playwright/test';
import {gotToModuleManagerPage} from '~/fixtures/goToModuleManagerPage.fixture';
import {PageManager} from '~/pages/managerPage';
import DbRequest from '~/services/db/dbRequest';

gotToModuleManagerPage('Check module is upgrade', async ({gotToModuleManagerPage}) => {
  const pm = new PageManager(gotToModuleManagerPage);
  const dbRequest = new DbRequest();
  let moduleVersionBefore: string;
  await test.step('check module module version in db', async () => {
    moduleVersionBefore = await dbRequest.returnModuleVersion();
    // eslint-disable-next-line no-console
    console.log(moduleVersionBefore);
    expect(moduleVersionBefore).not.toBeNull();
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
    const moduleVersionAfter = await dbRequest.returnModuleVersion();
    // eslint-disable-next-line no-console  
    console.log(moduleVersionAfter);
    expect(moduleVersionAfter).not.toBeNull();
    expect(moduleVersionAfter).not.toBe(moduleVersionBefore);
  });
});
