import {test} from '@playwright/test';
import CurlRequest from 'services/curl/curl';

// Var
let curlRequest = new CurlRequest();

test('Check that the Shop Health Check is returning information in json', async () => {
  await curlRequest.getShopHealthStatus();
});
test('Check the shop is not linked', async () => {
  await curlRequest.isShopLinked();
});
test('Check oauth2Url Url', async () => {
  await curlRequest.checkOauth2Url();
});
