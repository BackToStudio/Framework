import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for BackTo Framework Security E2E tests.
 *
 * Prerequisites:
 *   npm run env:start          # Start wp-env (WordPress + Docker)
 *   npx playwright install     # Install browsers
 *
 * Run tests with video:
 *   npx playwright test --project=chromium
 *
 * Videos are saved to: tests/e2e/videos/
 */
export default defineConfig({
  testDir: './tests/e2e',
  outputDir: './tests/e2e/test-results',

  /* Fail the build on CI if you accidentally left test.only in the source code */
  forbidOnly: !!process.env.CI,

  retries: process.env.CI ? 1 : 0,

  /* Reporter: HTML report + terminal output */
  reporter: [
    ['html', { outputFolder: 'tests/e2e/report', open: 'never' }],
    ['list'],
  ],

  use: {
    /* wp-env default URL */
    baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',

    /* Video recording for documentation */
    video: {
      mode: 'on',
      size: { width: 1280, height: 720 },
    },

    /* Screenshots on failure */
    screenshot: 'on',

    /* Trace on first retry */
    trace: 'on-first-retry',

    /* Slow down actions for visible video demonstration */
    actionTimeout: 10000,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  /* Global setup: ensure wp-env is running */
  globalSetup: './tests/e2e/global-setup.ts',
});
