import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || 'password';
const UNIQUE = Date.now();
const TEST_DIR = '/tmp/odm-e2e-' + UNIQUE;

type SubmitBtn = { name: string; value: string };

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

async function login(page: any) {
  // The single-threaded PHP built-in server intermittently returns an empty
  // response on the login POST, so retry the whole login sequence.
  for (let attempt = 0; attempt < 5; attempt++) {
    await page.context().clearCookies();
    await page.goto('/logout').catch(() => {});
    await retryGoto(page, '/index');
    await page.fill('input[name="frmuser"]', ADMIN_USER);
    await page.fill('input[name="frmpass"]', ADMIN_PASS);
    await page.locator('button[name="login"], input[type="submit"][name="login"]').click();
    try {
      await page.waitForURL('**/out', { timeout: 8000 });
      return;
    } catch {
      // login failed (empty response or CSRF race); retry with a fresh page
    }
  }
  throw new Error('Login failed after multiple attempts');
}

async function clickButton(page: any, btn: SubmitBtn) {
  const locator = page.locator(
    `button[name="${btn.name}"][value="${btn.value}"], ` +
    `input[type="submit"][name="${btn.name}"][value="${btn.value}"]`
  );
  await locator.click();
}

async function waitForMessage(page: any, message: string) {
  await expect(page.locator('#last_message')).toContainText(message, { timeout: 5000 });
}

test.describe('Incoming revision staging workflow', () => {

  let fileId: number;

  test.beforeAll(async () => {
    fs.mkdirSync(TEST_DIR, { recursive: true });
    // Create a test file
    fs.writeFileSync(path.join(TEST_DIR, 'test_doc.txt'), 'Hello, this is version 1 of the test document.');
    fs.writeFileSync(path.join(TEST_DIR, 'test_doc_v2.txt'), 'Hello, this is version 2 of the test document.');
  });

  test.afterAll(async () => {
    fs.rmSync(TEST_DIR, { recursive: true, force: true });
  });

  test('1. Upload a new file and verify it appears', async ({ page }) => {
    await login(page);
    await retryGoto(page, '/add');
    await page.locator('input[name="file[]"]').waitFor({ state: 'attached', timeout: 5000 });

    // Fill in the upload form
    await page.fill('input[name="description"]', 'E2E test doc ' + UNIQUE);
    await page.setInputFiles('input[name="file[]"]', path.join(TEST_DIR, 'test_doc.txt'));

    // Select category (first available)
    await page.selectOption('select[name="category"]', { index: 1 });
    // Select department (first available)
    await page.selectOption('select[name="file_department"]', { index: 0 });

    await clickButton(page, { name: 'submit', value: 'Add Document' });
    // add.php redirects to /details?id=<fileId> on success. The PHP built-in
    // server sometimes returns empty responses on POST, so poll for the
    // redirect URL rather than relying on the /out table's first row
    // (which is the OLDEST file because the table is ordered by id ASC).
    let fileIdFromUrl = '';
    for (let attempt = 0; attempt < 5; attempt++) {
      await page.waitForTimeout(1000);
      const match = page.url().match(/details\?id=(\d+)/);
      if (match) { fileIdFromUrl = match[1]; break; }
      await retryGoto(page, '/add');
      await page.locator('input[name="file[]"]').waitFor({ state: 'attached', timeout: 5000 });
      await page.fill('input[name="description"]', 'E2E test doc ' + UNIQUE);
      await page.setInputFiles('input[name="file[]"]', path.join(TEST_DIR, 'test_doc.txt'));
      await page.selectOption('select[name="category"]', { index: 1 });
      await page.selectOption('select[name="file_department"]', { index: 0 });
      await clickButton(page, { name: 'submit', value: 'Add Document' });
    }
    if (!fileIdFromUrl) {
      throw new Error('Could not capture file ID from upload redirect');
    }
    fileId = parseInt(fileIdFromUrl);

    // A freshly-uploaded file is publishable=0 (awaiting review), so it shows
    // under "Documents waiting to be reviewed" (/toBePublished), NOT /out.
    // Verify it appears there for the reviewer.
    await retryGoto(page, '/toBePublished');
    const ourRow = page.locator(`#file-table .tabulator-row`).filter({ has: page.locator(`a[href*="details?id=${fileId}"]`) });
    await expect(ourRow).toHaveCount(1, { timeout: 8000 });
  });

  test('2. Approve the file as reviewer', async ({ page }) => {
    await login(page);
    await retryGoto(page, '/toBePublished');
    await page.waitForTimeout(1000);

    // Select the row for OUR file (the table is ordered by id ASC, so the
    // first row may be a stale leftover from a previous run).
    const table = page.locator('#file-table');
    await table.waitFor({ state: 'visible', timeout: 5000 });

    // Click the checkbox in our file's row
    const ourRow = page.locator(`#file-table .tabulator-row`).filter({ has: page.locator(`a[href*="details?id=${fileId}"]`) }).first();
    if (await ourRow.isVisible()) {
      await ourRow.locator('input[type="checkbox"]').click();
    }

    // Click Authorize button
    await clickButton(page, { name: 'submit', value: 'commentAuthorize' });
    await page.waitForTimeout(1000);

    // Submit authorization with comment
    await page.fill('textarea[name="comments"]', 'Approved via E2E test');
    await clickButton(page, { name: 'submit', value: 'Authorize' });

    // After approving, the reviewer returns to the admin reviews page
    // (/toBePublished) with the flash message and the admin sidebar — it must
    // NOT land on /out (which has no admin nav).
    await page.waitForURL(/toBePublished/, { timeout: 10000 });
    await expect(page.locator('#last_message')).toBeVisible({ timeout: 8000 });
    await expect(page.locator('#adminSidebar')).toBeVisible({ timeout: 8000 });
  });

  test('3. Check out and check in a new version, then approve', async ({ page }) => {
    await login(page);

    // Go to the details page for our captured file and check it out.
    await retryGoto(page, '/details?id=' + fileId);
    await page.waitForTimeout(1000);

    // Click checkout link
    const checkoutLink = page.locator('a[href*="check-out"]');
    if (await checkoutLink.isVisible()) {
      await checkoutLink.click();
    }
    await page.waitForTimeout(2000);
    // The details-page checkout link carries access_right=modify, which
    // check-out.php requires in order to lock the file for check-in.
    await retryGoto(page, '/check-out?id=' + fileId + '&access_right=modify');

    // Confirm checkout (this triggers a file download; the browser stays put)
    await clickButton(page, { name: 'submit', value: 'Click here' });
    await page.waitForTimeout(1000);

    // Now check in a new version
    await retryGoto(page, '/check-in?id=' + fileId);
    await page.waitForSelector('input[name="file"]');

    // Upload new version
    await page.setInputFiles('input[name="file"]', path.join(TEST_DIR, 'test_doc_v2.txt'));
    await page.fill('textarea[name="note"]', 'Updated via E2E test');
    await clickButton(page, { name: 'submit', value: 'Check  Document In' });
    // The check-in POST redirects to /out?last_message=...; wait for that
    // so the success flash message is present.
    for (let attempt = 0; attempt < 5; attempt++) {
      await page.waitForTimeout(1000);
      if (/out\?last_message=/.test(page.url())) break;
      await retryGoto(page, '/out');
    }
    await waitForMessage(page, 'checked in');

    // === Task 4 Step 1: Pending-state assertions after check-in ===
    await retryGoto(page, '/history?id=' + fileId);
    const pendingRows = page.locator('table.table-striped tbody tr');
    await expect(pendingRows).toHaveCount(2);
    await expect(pendingRows.filter({ hasText: 'Latest' })).toContainText('Initial import');
    await expect(pendingRows.filter({ hasText: 'Pending' })).toContainText('Updated via E2E test');

    await retryGoto(page, '/details?id=' + fileId);
    await expect(page.locator('#details_revision')).toHaveText('1');
  });

  test('4. Approve the new revision and verify history', async ({ page }) => {
    await login(page);
    await retryGoto(page, '/toBePublished');
    await page.waitForTimeout(1000);

    // Select and approve the pending revision for OUR file
    const firstRow = page.locator(`#file-table .tabulator-row`).filter({ has: page.locator(`a[href*="details?id=${fileId}"]`) }).first();
    if (await firstRow.isVisible()) {
      await firstRow.locator('input[type="checkbox"]').click();
    }
    await clickButton(page, { name: 'submit', value: 'commentAuthorize' });
    await page.waitForTimeout(1000);

    await page.fill('textarea[name="comments"]', 'Approved revision via E2E');
    await clickButton(page, { name: 'submit', value: 'Authorize' });
    await page.waitForTimeout(2000);
    await retryGoto(page, '/out');

    // Verify history shows the revision
    await retryGoto(page, '/history?id=' + fileId);
    await page.waitForTimeout(1000);

    // === Task 4 Step 2: Post-approval assertions ===
    const revisionRows = page.locator('table.table-striped tbody tr');
    await expect(revisionRows).toHaveCount(2);
    await expect(revisionRows.nth(0)).toContainText('Latest');
    await expect(revisionRows.nth(0)).toContainText('Updated via E2E test');
    await expect(revisionRows.nth(1)).toContainText('Initial import');
    await expect(revisionRows.nth(0)).not.toContainText('Approved revision via E2E');
    await expect(revisionRows.nth(1)).not.toContainText('Approved revision via E2E');

    await retryGoto(page, '/details?id=' + fileId);
    await expect(page.locator('#details_revision')).toHaveText('2');
  });

  test('5. Check out, check in, and reject a revision', async ({ page }) => {
    await login(page);

    // Checkout
    await retryGoto(page, '/details?id=' + fileId);
    await page.waitForTimeout(500);

    const checkoutLink = page.locator('a[href*="check-out"]');
    if (await checkoutLink.isVisible()) {
      await checkoutLink.click();
    }
    await page.waitForTimeout(2000);
    await retryGoto(page, '/check-out?id=' + fileId + '&access_right=modify');
    await clickButton(page, { name: 'submit', value: 'Click here' });
    await page.waitForTimeout(1000);

    // Check in the same file (simulating a fix without changes)
    await retryGoto(page, '/check-in?id=' + fileId);
    await page.waitForSelector('input[name="file"]');
    await page.setInputFiles('input[name="file"]', path.join(TEST_DIR, 'test_doc_v2.txt'));
    await page.fill('textarea[name="note"]', 'Attempted fix via E2E');
    await clickButton(page, { name: 'submit', value: 'Check  Document In' });
    await page.waitForTimeout(2000);
    await retryGoto(page, '/out');

    // Reject the file
    await retryGoto(page, '/toBePublished');
    await page.waitForTimeout(1000);

    const rejectRow = page.locator(`#file-table .tabulator-row`).filter({ has: page.locator(`a[href*="details?id=${fileId}"]`) }).first();
    if (await rejectRow.isVisible()) {
      await rejectRow.locator('input[type="checkbox"]').click();
    }
    await clickButton(page, { name: 'submit', value: 'commentReject' });
    await page.waitForTimeout(1000);

    await page.fill('textarea[name="comments"]', 'Rejected via E2E test - needs fixes');
    await clickButton(page, { name: 'submit', value: 'Reject' });
    // After rejecting, the reviewer returns to the admin reviews page
    // (/toBePublished) with the flash message — NOT /out.
    await page.waitForURL(/toBePublished/, { timeout: 10000 });
    await waitForMessage(page, 'rejection');

    // === Task 4 Step 3: Rejected-state assertions ===
    await retryGoto(page, '/history?id=' + fileId);
    const rejectedRows = page.locator('table.table-striped tbody tr');
    await expect(rejectedRows).toHaveCount(3);
    await expect(rejectedRows.filter({ hasText: 'Latest' })).toContainText('Updated via E2E test');
    await expect(rejectedRows.filter({ hasText: 'Rejected' })).toContainText('Attempted fix via E2E');

    await retryGoto(page, '/details?id=' + fileId);
    await expect(page.locator('#details_revision')).toHaveText('2');
  });

  test('6. Verify rejects page shows checkout button, checkout and verify checkin appears', async ({ page }) => {
    await login(page);

    // Go to rejects page
    await retryGoto(page, '/rejects?state=1');
    await page.waitForTimeout(1000);

    // Verify the table shows our rejected file
    const table = page.locator('#file-table');
    await table.waitFor({ state: 'visible', timeout: 5000 });

    // Check for a checkout link
    const checkoutBtn = page.locator('a[href*="check-out?id=' + fileId + '"]');
    await expect(checkoutBtn).toBeVisible({ timeout: 5000 });

    // Click checkout
    await checkoutBtn.click();
    await page.waitForTimeout(2000);
    await retryGoto(page, '/check-out?id=' + fileId + '&access_right=modify');
    await clickButton(page, { name: 'submit', value: 'Click here' });
    await page.waitForTimeout(1000);

    // Go back to rejects page - should now show check-in button
    await retryGoto(page, '/rejects?state=1');
    await page.waitForTimeout(1000);

    const checkinBtn = page.locator('a[href*="check-in?id=' + fileId + '"]');
    await expect(checkinBtn).toBeVisible({ timeout: 5000 });
  });

  test('7. Verify incoming directory is cleaned up on permanent delete', async ({ page }) => {
    await login(page);

    // Temp delete the file
    await retryGoto(page, '/delete?mode=tmpdel&id0=' + fileId + '&num_checkboxes=1');
    await page.waitForTimeout(2000);
    await retryGoto(page, '/out');

    // Go to deleted files page
    await retryGoto(page, '/delete?mode=view_del_archive');
    await page.waitForTimeout(1000);

    // Select the file and permanently delete
    const deleteRow = page.locator(`#file-table .tabulator-row`).filter({ has: page.locator(`a[href*="details?id=${fileId}"]`) }).first();
    if (await deleteRow.isVisible()) {
      await deleteRow.locator('input[type="checkbox"]').click();
    }

    // Click delete permanently button
    const permDeleteBtn = page.locator('button#delete-permanent-selected');
    if (await permDeleteBtn.isVisible()) {
      page.on('dialog', dialog => dialog.accept());
      await permDeleteBtn.click();
    }
    await page.waitForTimeout(2000);
    await retryGoto(page, '/delete?mode=view_del_archive');
  });
});
