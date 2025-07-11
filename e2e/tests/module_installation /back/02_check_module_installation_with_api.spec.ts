import {test, expect} from '@playwright/test';
import HealthCheckApi from '~/services/api/healthCheckApi';

// Var
let healthCheckApi = new HealthCheckApi();

test('Check that the Shop Health Check is returning information in json', async () => {
  await healthCheckApi.getShopHealthStatus();
});
test('Check the shop is not Oauth2Client', async () => {
  await healthCheckApi.isOauth2Client();
});
test('Check the shop is not linked', async () => {
  const isLinked = await healthCheckApi.isShopLinked();
  expect(isLinked).toBeFalsy();
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
