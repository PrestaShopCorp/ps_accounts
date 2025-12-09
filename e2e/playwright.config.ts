import {defineConfig} from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';
dotenv.config({path: path.resolve(__dirname, '.env')});

export default defineConfig({
  timeout: 120000,
  expect: {timeout: 40000},
  testDir: './tests',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Retry on CI only */
  retries: process.env.CI ? 0 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', {open: 'never'}],
    ['list'],
    ['allure-playwright', {resultsDir: path.join(__dirname, 'allure-results')}]
  ],
  projects: [
    {
      name: 'Account TNR V7',
      testMatch: ['**/tnrV7/**/*.spec.ts'],
      use: {browserName: 'chromium'}
    },
    {
      name: 'Account TNR V8',
      testMatch: ['**/tnrV8/**/*.spec.ts'],
      use: {browserName: 'chromium'}
    },
    {
      name: 'Upgrade TNR',
      testMatch: ['**/upgrade/*.spec.ts'],
      use: {browserName: 'chromium'}
    }
  ],
  use: {
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    headless: process.env.CI ? true : true,
    userAgent: process.env.QA_USER_AGENT || 'default-ua-dev-mode',
    launchOptions: {
      slowMo: process.env.CI ? 1000 : 0
    }
  }
});
