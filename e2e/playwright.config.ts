import {defineConfig} from '@playwright/test';

export default defineConfig({
  timeout: 500000,
  testDir: './tests',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Retry on CI only */
  retries: process.env.CI ? 0 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [['list'], ['allure-playwright']],
  projects: [
    {
      // Look for test files in the "campaigns" directory, relative to this configuration file.
      name: 'Account Autom Tests',
      testMatch: 'tests/**/*spec.ts'
    }
  ],
  use: {trace: 'on-first-retry', screenshot: 'on', headless: process.env.HEADLESS !== 'false'}
});
