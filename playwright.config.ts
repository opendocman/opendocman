import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30000,
  retries: 0,
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
      testMatch: '**/smoke-uat.spec.ts',
    },
  ],
});