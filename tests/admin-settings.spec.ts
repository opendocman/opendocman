import { test, expect } from '@playwright/test';

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || 'password';

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

async function loginAs(page: any, username: string, password: string) {
  // The single-threaded PHP built-in server intermittently returns an empty
  // response on the login POST, so retry the whole login sequence.
  for (let attempt = 0; attempt < 5; attempt++) {
    await page.context().clearCookies();
    await page.goto('/logout').catch(() => {});
    await retryGoto(page, '/index');
    await page.fill('input[name="frmuser"]', username);
    await page.fill('input[name="frmpass"]', password);
    await page.locator('button[name="login"]').click();
    try {
      await page.waitForURL('**/out', { timeout: 10000 });
      return;
    } catch {
      // login failed (empty response or CSRF race); retry with a fresh page
    }
  }
  throw new Error('Login failed after multiple attempts');
}

test.describe('Grouped settings page', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, ADMIN_USER, ADMIN_PASS);
  });

  test('renders grouped vertical tabs with General active by default', async ({ page }) => {
    await retryGoto(page, '/settings?submit=update');
    await page.waitForSelector('#settingsForm', { timeout: 8000 });

    // Search box and tab rail exist
    await expect(page.locator('#settingsFilter')).toBeVisible();
    await expect(page.locator('#settingsTabs')).toBeVisible();

    // Left rail shows a tab labeled General and it is active by default
    const generalTab = page.locator('#tab-general');
    await expect(generalTab).toBeVisible();
    // The label is localized; assert non-empty rather than a specific language.
    await expect(generalTab).not.toHaveText('');
    await expect(generalTab).toHaveClass(/active/);

    // General pane is the visible one
    const generalPane = page.locator('#group-general');
    await expect(generalPane).toHaveClass(/show/);
    await expect(generalPane).toHaveClass(/active/);
    await expect(generalPane).toBeVisible();

    // The rail lists more than just the one group
    await expect(page.locator('#settingsTabs .nav-link').first()).toBeVisible();
    expect(await page.locator('#settingsTabs .nav-link').count()).toBeGreaterThan(1);
  });

  test('switching tabs activates the Security & Authentication pane', async ({ page }) => {
    await retryGoto(page, '/settings?submit=update');
    await page.waitForSelector('#settingsForm', { timeout: 8000 });

    const securityTab = page.locator('#tab-security');
    await expect(securityTab).toBeVisible();
    await securityTab.click();

    await expect(securityTab).toHaveClass(/active/);
    const securityPane = page.locator('#group-security');
    await expect(securityPane).toHaveClass(/show/);
    await expect(securityPane).toBeVisible();
    await expect(page.locator('#group-general')).not.toBeVisible();
  });

  test('global search filters rows across hidden tab panes', async ({ page }) => {
    await retryGoto(page, '/settings?submit=update');
    await page.waitForSelector('#settingsForm', { timeout: 8000 });

    // General is active; theme lives in the hidden UI & Appearance pane.
    await expect(page.locator('#tab-general')).toHaveClass(/active/);

    // The matching row in a different, inactive pane is shown (inline display '').
    const themeRow = page.locator('#group-appearance .setting-row[data-settings-name="theme"]');
    await expect(themeRow).toHaveCount(1);

    // The active pane's title row does not match and is hidden by the filter.
    const titleRow = page.locator('#group-general .setting-row[data-settings-name="title"]');
    await expect(titleRow).toHaveCount(1);

    await page.fill('#settingsFilter', 'theme');
    await page.waitForFunction(() => {
      const row = document.querySelector('#group-general .setting-row[data-settings-name="title"]') as HTMLElement | null;
      return row !== null && row.style.display === 'none';
    }, { timeout: 5000 });

    expect(await themeRow.evaluate((el: HTMLElement) => el.style.display)).toBe('');
    expect(await titleRow.evaluate((el: HTMLElement) => el.style.display)).toBe('none');

    // Clearing the filter restores every row.
    await page.fill('#settingsFilter', '');
    await page.waitForFunction(() => {
      const row = document.querySelector('#group-general .setting-row[data-settings-name="title"]') as HTMLElement | null;
      return row !== null && row.style.display === '';
    }, { timeout: 5000 });
    expect(await themeRow.evaluate((el: HTMLElement) => el.style.display)).toBe('');
    expect(await titleRow.evaluate((el: HTMLElement) => el.style.display)).toBe('');
  });
});