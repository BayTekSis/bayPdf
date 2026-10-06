import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: './tests/browser',
  outputDir: '.artifacts/browser-results',
  workers: 1,
  use: {
    baseURL: 'http://127.0.0.1:8127',
    viewport: { width: 1440, height: 1000 },
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'php vendor/bin/testbench serve --host=127.0.0.1 --port=8127 --no-reload',
    url: 'http://127.0.0.1:8127/baypdf',
    reuseExistingServer: !process.env.CI,
    timeout: 60000,
  },
})
