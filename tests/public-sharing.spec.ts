import { test, expect } from '@playwright/test';

async function retryGoto(page: any, url: string, opts = {}) {
  for (let attempt = 0; attempt < 3; attempt++) {
    await page.goto(url, { waitUntil: 'load', ...opts });
    const hasMainContent = await page.evaluate(() => {
      const m = document.querySelector('main');
      return m ? m.children.length > 0 : false;
    });
    if (hasMainContent) return;
    await page.waitForTimeout(500);
  }
}

test.describe('Public File Sharing', () => {
  test('public page shows disabled message when feature is off', async ({ page }) => {
    await retryGoto(page, '/public');
    await expect(page.locator('text=Public file sharing is disabled')).toBeVisible();
  });
});