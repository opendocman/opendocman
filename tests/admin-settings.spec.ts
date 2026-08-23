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

  test('renders grouped accordion with General open by default', async ({ page }) => {
    await retryGoto(page, '/settings?submit=update');
    await page.waitForSelector('#settingsForm', { timeout: 8000 });

    // Search box and accordion exist
    await expect(page.locator('#settingsFilter')).toBeVisible();
    await expect(page.locator('#settingsAccordion')).toBeVisible();

    // Shared admin shell: the sidebar must be present.
    await expect(page.locator('#adminSidebar')).toBeVisible();
    await expect(page.locator('#adminSidebarNav .nav-link').first()).toBeVisible();

    // The General panel is open by default (show), and has a count badge
    const generalPanel = page.locator('#group-general');
    await expect(generalPanel).toHaveClass(/show/);
    await expect(generalPanel).toBeVisible();
    await expect(page.locator('#heading-general .accordion-button .badge')).toHaveText(/\d+/);

    // The accordion lists more than just the one group
    expect(await page.locator('#settingsAccordion .accordion-item').count()).toBeGreaterThan(1);
  });

  test('expanding a group shows its settings and multiple panels can be open', async ({ page }) => {
    await retryGoto(page, '/settings?submit=update');
    await page.waitForSelector('#settingsForm', { timeout: 8000 });

    // General is open by default
    await expect(page.locator('#group-general')).toHaveClass(/show/);

    // Click the Security & Auth header to open its panel
    const securityHeader = page.locator('#heading-security .accordion-button');
    await securityHeader.click();
    await expect(page.locator('#group-security')).toHaveClass(/show/);

    // Click the Storage header too — both can be open at once (multi-open)
    const storageHeader = page.locator('#heading-storage .accordion-button');
    await storageHeader.click();
    await expect(page.locator('#group-storage')).toHaveClass(/show/);

    // Both panels visible simultaneously
    await expect(page.locator('#group-general')).toBeVisible();
    await expect(page.locator('#group-storage')).toBeVisible();
  });

  test('global search filters rows and auto-expands matching groups', async ({ page }) => {
    await retryGoto(page, '/settings?submit=update');
    await page.waitForSelector('#settingsForm', { timeout: 8000 });

    // General is open by default; theme lives in the (initially closed) UI & Appearance panel.
    await expect(page.locator('#group-general')).toHaveClass(/show/);
    await expect(page.locator('#group-appearance')).not.toHaveClass(/show/);

    const themeRow = page.locator('#group-appearance .setting-row[data-settings-name="theme"]');
    await expect(themeRow).toHaveCount(1);
    const titleRow = page.locator('#group-general .setting-row[data-settings-name="title"]');
    await expect(titleRow).toHaveCount(1);

    // Type a query matching a setting in the closed Appearance group.
    await page.fill('#settingsFilter', 'theme');

    // The Appearance panel auto-expands and its matching row is visible.
    await page.waitForFunction(() => {
      const p = document.querySelector('#group-appearance') as HTMLElement | null;
      return p !== null && p.classList.contains('show');
    }, { timeout: 5000 });
    await expect(themeRow).toBeVisible();
    // The General panel's non-matching title row is hidden by the filter.
    await page.waitForFunction(() => {
      const r = document.querySelector('#group-general .setting-row[data-settings-name="title"]') as HTMLElement | null;
      return r !== null && r.style.display === 'none';
    }, { timeout: 5000 });

    // Clearing the filter restores every row and the panels' prior state.
    await page.fill('#settingsFilter', '');
    await page.waitForFunction(() => {
      const r = document.querySelector('#group-general .setting-row[data-settings-name="title"]') as HTMLElement | null;
      return r !== null && r.style.display === '';
    }, { timeout: 5000 });
    expect(await themeRow.evaluate((el: HTMLElement) => el.style.display)).toBe('');
    expect(await titleRow.evaluate((el: HTMLElement) => el.style.display)).toBe('');
  });
});