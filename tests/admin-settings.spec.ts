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

    // The panels' prior state is also restored: Appearance (auto-expanded by
    // the search) collapses back, and General stays open.
    await page.waitForFunction(() => {
      const app = document.querySelector('#group-appearance') as HTMLElement | null;
      const gen = document.querySelector('#group-general') as HTMLElement | null;
      return app !== null && gen !== null && !app.classList.contains('show') && gen.classList.contains('show');
    }, { timeout: 5000 });
  });

  test('saving settings shows a flash confirmation message', async ({ page }) => {
    await retryGoto(page, '/settings?submit=update');
    await page.waitForSelector('#settingsForm', { timeout: 8000 });

    // Capture the current title so we can restore it afterward.
    const title = page.locator('input[name="title"]');
    const original = (await title.inputValue()) || '';

    const testValue = `E2E Flash ${Date.now()}`;
    await title.fill(testValue);
    await page.click('button[type="submit"][name="submit"][value="Save"]');

    // The flash alert (#last_message) must appear after saving, with non-empty text.
    await expect(page.locator('#last_message')).toBeVisible({ timeout: 8000 });
    await expect(page.locator('#last_message')).not.toHaveText('');

    // Restore the original title to leave the DB as we found it.
    const titleAfter = page.locator('input[name="title"]');
    await titleAfter.fill(original);
    await page.click('button[type="submit"][name="submit"][value="Save"]');
    await expect(page.locator('#last_message')).toBeVisible({ timeout: 8000 });
  });
});

test.describe('Admin sidebar gating for non-admin reviewers', () => {
  // A non-admin reviewer (e2euser) must NOT see the admin sidebar on the
  // reviews page (toBePublished) even though it renders through
  // _admin_content.tpl. The e2euser is seeded by global-setup.
  const NON_ADMIN_USER = process.env.NON_ADMIN_USER || 'e2euser';
  const NON_ADMIN_PASS = process.env.NON_ADMIN_PASSWORD || 'e2euserpass';

  test('reviews page shows no admin sidebar to a non-admin reviewer', async ({ page }) => {
    // Grant e2euser reviewer rights on department 1 via docker mysql so they can
    // reach toBePublished, then verify they see it WITHOUT the admin sidebar.
    // Matches the docker-exec pattern used in public-sharing.spec.ts.
    const { execSync } = require('child_process');
    const MYSQL = `docker exec opendocman-db-1 mysql -u opendocman -pcWzzQzOySoBvoO84gJykRedP opendocman -e`;
    try {
      // Delete any stale grants first, then add exactly one (avoid duplicates).
      execSync(`${MYSQL} "DELETE FROM odm_dept_reviewer WHERE user_id=(SELECT id FROM odm_user WHERE username='${NON_ADMIN_USER}')"`, { timeout: 10000 });
      execSync(`${MYSQL} "INSERT INTO odm_dept_reviewer (dept_id, user_id) SELECT 1, id FROM odm_user WHERE username='${NON_ADMIN_USER}'"`, { timeout: 10000 });

      // Log in as the non-admin reviewer.
      await loginAs(page, NON_ADMIN_USER, NON_ADMIN_PASS);
      await retryGoto(page, '/toBePublished');

      // The reviews page renders, but the admin sidebar must be absent.
      await expect(page.locator('#file-table')).toBeVisible({ timeout: 8000 });
      await expect(page.locator('#adminSidebar')).toHaveCount(0);
      await expect(page.locator('#adminSidebarNav')).toHaveCount(0);
    } finally {
      // Remove the grant so the DB is left as we found it.
      execSync(`${MYSQL} "DELETE FROM odm_dept_reviewer WHERE user_id=(SELECT id FROM odm_user WHERE username='${NON_ADMIN_USER}')"`, { timeout: 10000 });
    }
  });
});