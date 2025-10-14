import {test, expect} from '@playwright/test';
import HealthCheckApi from '~/services/api/healthCheckApi';
import DbRequest from '~/services/db/dbRequest';

// Var
const healthCheckApi = new HealthCheckApi();
const dbRequest = new DbRequest();


test('Check that the Shop Health Check is returning information in json', async () => {
  await healthCheckApi.getShopHealthStatus();
});
test('Check the Oauth2Client Status', async () => {
  const moduleVersion = await dbRequest.checkModuleVersion();
  const checkOauth2ClientStatus = await healthCheckApi.getOauth2ClientStatus();
  if(moduleVersion < '8'){
    expect(checkOauth2ClientStatus).toBeFalsy();
  }else{
    expect(checkOauth2ClientStatus).toBeTruthy();
  }
});
test('Check the shop linked status', async () => {
  const moduleVersion = await dbRequest.checkModuleVersion();
  const checkShopLinkedStatus = await healthCheckApi.getShopLinkedStatus();
  if (moduleVersion < '8') {
    expect(checkShopLinkedStatus).toBeFalsy();
  } else {
    expect(checkShopLinkedStatus).toBeTruthy();
  }
});
test('Check oauth2 Url', async () => {
  await healthCheckApi.checkOauth2Url();
});
test('Check accountsApi Url', async () => {
  await healthCheckApi.checkAccountsApiUrl();
});
test('Check accountsUi Url', async () => {
  await healthCheckApi.checkAccountsUiUrl();
});
