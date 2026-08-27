import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  globalSetup: './tests/global-setup.ts',
  timeout: 30000,
  retries: 0,
  workers: 1,
  use: {
    baseURL: 'http://localhost:8080',
    headless: true,
    screenshot: 'only-on-failure',
  },
  webServer: {
    command: 'sleep infinity',
    url: 'http://localhost:8080',
    reuseExistingServer: true,
    timeout: 120000,
  },
  projects: [
    {
      name: 'smoke',
      testMatch: ['**/smoke-uat.spec.ts', '**/incoming-workflow.spec.ts', '**/public-sharing.spec.ts', '**/admin-settings.spec.ts', '**/profile-edit.spec.ts'],
    },
  ],
});