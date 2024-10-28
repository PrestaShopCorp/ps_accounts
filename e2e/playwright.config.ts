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
  reporter: [
    ['line'],
    ['allure-playwright', {outputFolder: process.env.ALLURE_RESULTS_DIR || 'allure-results'}]
    // ['json', {outputFile: './test-results/report.json'}],
    // [
    //   'playwright-ctrf-json-reporter',
    //   {
    //     outputFile: 'report-summary.json',
    //     outputDir: 'report-summary'
    //   }
    // ]
  ],
  projects: [
    {
      // Look for test files in the "campaigns" directory, relative to this configuration file.
      name: 'account',
      testMatch: 'tests/**/*spec.ts'
    }
  ],
  use: {trace: 'on-first-retry'}
});
