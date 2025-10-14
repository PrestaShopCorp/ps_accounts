import {test} from '@playwright/test';
import DbRequest from '~/services/db/dbRequest';

// Var
const dbRequest = new DbRequest();

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
