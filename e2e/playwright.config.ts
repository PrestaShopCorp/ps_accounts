import {defineConfig} from '@playwright/test';

export default defineConfig({
  timeout: 120000,
  expect: {timeout: 10000},
  testDir: './tests',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Retry on CI only */
  retries: process.env.CI ? 0 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [['html', {open: 'never'}], ['list'], ['allure-playwright']],
  projects: [
    {
      // Look for test files in the "campaigns" directory, relative to this configuration file.
      name: 'Account Autom Test',
      testMatch: 'tests/**/*spec.ts'
    }
  ],
  use: {
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    headless: process.env.HEADLESS !== 'false',
    userAgent: process.env.QA_USER_AGENT || 'default-ua-dev-mode'
  }
});
