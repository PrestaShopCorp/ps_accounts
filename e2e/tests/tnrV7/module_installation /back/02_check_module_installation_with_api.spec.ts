import {test, expect} from '@playwright/test';
import HealthCheckApi from '~/services/api/healthCheckApi';

// Var
const healthCheckApi = new HealthCheckApi();

test('Check that the Shop Health Check is returning information in json', async () => {
  await healthCheckApi.getShopHealthStatus();
});
test('Check the Oauth2Client Status', async () => {
  const checkOauth2ClientStatus = await healthCheckApi.getOauth2ClientStatus();
  expect(checkOauth2ClientStatus).toBeFalsy();
});
test('Check the shop linked status', async () => {
  const checkShopLinkedStatus = await healthCheckApi.getShopLinkedStatus();
  expect(checkShopLinkedStatus).toBeFalsy();
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
