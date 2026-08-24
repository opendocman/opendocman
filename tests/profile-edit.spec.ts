import { test, expect } from '@playwright/test';

const NON_ADMIN_USER = process.env.NON_ADMIN_USER || 'e2euser';
const NON_ADMIN_PASS = process.env.NON_ADMIN_PASSWORD || 'e2euserpass';
const UNIQUE = Date.now();
const NEW_FIRST_NAME = `E2EProfile${UNIQUE}`;

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

async function openProfileEditForm(page: any) {
  await retryGoto(page, '/profile');
  // Click the "Update your profile" button (link).
  await page.getByRole('link', { name: /Update personal profile|Update your profile/i }).click();
  await page.waitForURL(/user\?submit=Modify\+User/, { timeout: 8000 });
  await page.waitForSelector('input[name="first_name"]', { timeout: 8000 });
}

// ────────────────────────────────────────────────────────────
// Non-admin self profile editing (regression: PR #421 removed it)
// ────────────────────────────────────────────────────────────
test.describe('Non-admin profile self-edit', () => {
  test('non-admin can open and save their own profile', async ({ page }) => {
    await loginAs(page, NON_ADMIN_USER, NON_ADMIN_PASS);
    await openProfileEditForm(page);

    // The regression surfaced as "You are not an administrator" here.
    await expect(page.locator('body')).not.toContainText('You are not an administrator');

    try {
      // Update the first name and save.
      await page.fill('input[name="first_name"]', NEW_FIRST_NAME);
      await page.locator('button[name="submit"][value="Update User"]').click();

      // The Update User POST handler redirects to /out on success.
      await page.waitForURL(/\/out\b/, { timeout: 8000 });

      // Verify the change persisted: reopen the edit form and check the value.
      await openProfileEditForm(page);
      await expect(page.locator('input[name="first_name"]')).toHaveValue(NEW_FIRST_NAME);
    } finally {
      // Restore the canonical first name so other E2E tests (which assert on
      // NON_ADMIN_DISPLAY "User, E2E") are unaffected by this mutation.
      await openProfileEditForm(page);
      await page.fill('input[name="first_name"]', 'E2E');
      await page.locator('button[name="submit"][value="Update User"]').click();
      await page.waitForURL(/\/out\b/, { timeout: 8000 }).catch(() => {});
    }
  });
});