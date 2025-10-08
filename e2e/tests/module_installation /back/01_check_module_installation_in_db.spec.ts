import {test, expect} from '@playwright/test';
import DbRequest from '~/services/db/dbRequest';

// Var
const dbRequest = new DbRequest();
let shouldRun = false;

test.beforeAll(async () => {
  const version = await dbRequest.checkModuleVersion();
  shouldRun = parseFloat(version) >= 8;
});

test('Check that the ps_accounts module is returning information from the database', async () => {
  await dbRequest.getModuleDetails();
});
test('Check the Module Name', async () => {
  await dbRequest.checkModuleName();
});
test('Check the Module Version', async () => {
  await dbRequest.checkModuleVersion();
});
test('Check if the Module isActive ', async () => {
  await dbRequest.checkModuleIsActive();
});

test('Ps account ouath2 client ID is defined', async () => {
  test.skip(!shouldRun, 'Version < 8');
  const checkValue = await dbRequest.getPsConfigurationData('PS_ACCOUNTS_OAUTH2_CLIENT_ID');
  expect(checkValue.value).not.toBeNull();
});
test('Ps account ouath2 client Secret is defined', async () => {
  test.skip(!shouldRun, 'Version < 8');
  const checkValue = await dbRequest.getPsConfigurationData('PS_ACCOUNTS_OAUTH2_CLIENT_SECRET');
  expect(checkValue.value).not.toBeNull();
});
test('Ps account Shop Status is defined', async () => {
  test.skip(!shouldRun, 'Version < 8');
  const checkValue = await dbRequest.getPsConfigurationData('PS_ACCOUNTS_SHOP_STATUS');
  expect(checkValue.value).not.toBeNull();
});
test('Ps account Access Token is defined', async () => {
  test.skip(!shouldRun, 'Version < 8');
  const checkValue = await dbRequest.getPsConfigurationData('PS_ACCOUNTS_ACCESS_TOKEN');
  expect(checkValue.value).not.toBeNull();
});
test('Ps account Shop Proof is defined', async () => {
  test.skip(!shouldRun, 'Version < 8');
  const checkValue = await dbRequest.getPsConfigurationData('PS_ACCOUNTS_SHOP_PROOF');
  expect(checkValue.value).not.toBeNull();
});
