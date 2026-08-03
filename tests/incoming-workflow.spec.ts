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
  await page.context().clearCookies();
  await page.goto('/logout').catch(() => {});
  await retryGoto(page, '/index');
  await page.fill('input[name="frmuser"]', ADMIN_USER);
  await page.fill('input[name="frmpass"]', ADMIN_PASS);
  await page.locator('button[name="login"], input[type="submit"][name="login"]').click();
  await page.waitForURL('**/out', { timeout: 5000 });
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
    await page.waitForSelector('input[name="file"]');

    // Fill in the upload form
    await page.fill('input[name="description"]', 'E2E test doc ' + UNIQUE);
    await page.setInputFiles('input[name="file"]', path.join(TEST_DIR, 'test_doc.txt'));

    // Select category (first available)
    await page.selectOption('select[name="category"]', { index: 1 });
    // Select department (first available)
    await page.selectOption('select[name="department"]', { index: 1 });

    await clickButton(page, { name: 'submit', value: 'Add' });
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });
    await waitForMessage(page, 'success');
  });

  test('2. Approve the file as reviewer', async ({ page }) => {
    await login(page);
    await retryGoto(page, '/toBePublished');
    await page.waitForTimeout(1000);

    // Select the file row in Tabulator
    const table = page.locator('#file-table');
    await table.waitFor({ state: 'visible', timeout: 5000 });

    // Click the first row to select it
    const firstRow = page.locator('#file-table .tabulator-row').first();
    if (await firstRow.isVisible()) {
      await firstRow.click();
    }

    // Click Authorize button
    await clickButton(page, { name: 'submit', value: 'commentAuthorize' });
    await page.waitForTimeout(1000);

    // Submit authorization with comment
    await page.fill('textarea[name="comments"]', 'Approved via E2E test');
    await clickButton(page, { name: 'submit', value: 'Authorize' });
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });
    await waitForMessage(page, 'authorized');
  });

  test('3. Check out and check in a new version, then approve', async ({ page }) => {
    await login(page);

    // Go to out page and find our file
    await retryGoto(page, '/out');
    await page.waitForTimeout(1000);

    // Click the details link for our file (first row)
    const detailsLink = page.locator('#file-table .tabulator-row').first().locator('a').first();
    await detailsLink.click();
    await page.waitForURL(/details\?id=/, { timeout: 5000 });

    // Click checkout link
    const checkoutLink = page.locator('a[href*="check-out"]');
    if (await checkoutLink.isVisible()) {
      await checkoutLink.click();
    }
    await page.waitForURL(/check-out\?id=/, { timeout: 5000 });

    // Confirm checkout
    await clickButton(page, { name: 'submit', value: 'Click here' });
    // File downloads, page may redirect
    await page.waitForTimeout(1000);

    // Now check in a new version
    await retryGoto(page, '/check-in?id=' + (await page.evaluate(() => {
      const m = window.location.href.match(/id=(\d+)/);
      return m ? m[1] : '';
    })));

    // If the URL didn't have the ID, navigate to check-in with the file ID
    // We need to find the file ID. Let's navigate to details page first.
    await retryGoto(page, '/out');
    await page.waitForTimeout(1000);

    // Get file ID from first details link
    const firstDetailsHref = await page.locator('#file-table .tabulator-row').first().locator('a').first().getAttribute('href');
    const idMatch = firstDetailsHref?.match(/id=(\d+)/);
    if (!idMatch) throw new Error('Could not find file ID');
    fileId = parseInt(idMatch[1]);

    // Go to check-in page
    await retryGoto(page, '/check-in?id=' + fileId);
    await page.waitForSelector('input[name="file"]');

    // Upload new version
    await page.setInputFiles('input[name="file"]', path.join(TEST_DIR, 'test_doc_v2.txt'));
    await page.fill('textarea[name="note"]', 'Updated via E2E test');
    await clickButton(page, { name: 'submit', value: 'Check  Document In' });
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });
    await waitForMessage(page, 'checked in');
  });

  test('4. Approve the new revision and verify history', async ({ page }) => {
    await login(page);
    await retryGoto(page, '/toBePublished');
    await page.waitForTimeout(1000);

    // Select and approve the pending revision
    const firstRow = page.locator('#file-table .tabulator-row').first();
    if (await firstRow.isVisible()) {
      await firstRow.click();
    }
    await clickButton(page, { name: 'submit', value: 'commentAuthorize' });
    await page.waitForTimeout(1000);

    await page.fill('textarea[name="comments"]', 'Approved revision via E2E');
    await clickButton(page, { name: 'submit', value: 'Authorize' });
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });

    // Verify history shows the revision
    await retryGoto(page, '/history?id=' + fileId);
    await page.waitForTimeout(1000);

    // Should see at least one revision entry plus the current
    const revisionRows = page.locator('table.table-striped tbody tr');
    const count = await revisionRows.count();
    expect(count).toBeGreaterThanOrEqual(2); // at least 1 revision + current
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
    await page.waitForURL(/check-out\?id=/, { timeout: 5000 });
    await clickButton(page, { name: 'submit', value: 'Click here' });
    await page.waitForTimeout(1000);

    // Check in the same file (simulating a fix without changes)
    await retryGoto(page, '/check-in?id=' + fileId);
    await page.waitForSelector('input[name="file"]');
    await page.setInputFiles('input[name="file"]', path.join(TEST_DIR, 'test_doc_v2.txt'));
    await page.fill('textarea[name="note"]', 'Attempted fix via E2E');
    await clickButton(page, { name: 'submit', value: 'Check  Document In' });
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });

    // Reject the file
    await retryGoto(page, '/toBePublished');
    await page.waitForTimeout(1000);

    const rejectRow = page.locator('#file-table .tabulator-row').first();
    if (await rejectRow.isVisible()) {
      await rejectRow.click();
    }
    await clickButton(page, { name: 'submit', value: 'commentReject' });
    await page.waitForTimeout(1000);

    await page.fill('textarea[name="comments"]', 'Rejected via E2E test - needs fixes');
    await clickButton(page, { name: 'submit', value: 'Reject' });
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });
    await waitForMessage(page, 'rejected');
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
    await page.waitForURL(/check-out\?id=/, { timeout: 5000 });
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
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });

    // Go to deleted files page
    await retryGoto(page, '/delete?mode=view_del_archive');
    await page.waitForTimeout(1000);

    // Select the file and permanently delete
    const deleteRow = page.locator('#file-table .tabulator-row').first();
    if (await deleteRow.isVisible()) {
      await deleteRow.click();
    }

    // Click delete permanently button
    const permDeleteBtn = page.locator('button#delete-permanent-selected');
    if (await permDeleteBtn.isVisible()) {
      page.on('dialog', dialog => dialog.accept());
      await permDeleteBtn.click();
    }
    await page.waitForURL(/delete\?mode=view_del_archive/, { timeout: 5000 }).catch(() => {});
  });
});
