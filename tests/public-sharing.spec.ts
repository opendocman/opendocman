import { test, expect } from '@playwright/test';
import path from 'path';
import fs from 'fs';
import { execSync } from 'child_process';

const DB_CLEANUP = (fileId: number) => `
  DELETE FROM odm_access_log WHERE file_id = ${fileId};
  DELETE FROM odm_content_index WHERE file_id = ${fileId};
  DELETE FROM odm_dept_perms WHERE fid = ${fileId};
  DELETE FROM odm_user_perms WHERE fid = ${fileId};
  DELETE FROM odm_log WHERE id = ${fileId};
  DELETE FROM odm_data WHERE id = ${fileId};
`;

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

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || 'admin';

async function login(page: any) {
  await retryGoto(page, '/');
  await page.fill('[name="frmuser"]', ADMIN_USER);
  await page.fill('[name="frmpass"]', ADMIN_PASS);
  await page.click('button[name="login"]');
  await page.waitForURL('**/out');
}

async function setPublicSharing(page: any, value: string) {
  await retryGoto(page, '/settings?submit=update&state=2');
  await page.selectOption('select[name="public_sharing"]', value);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/settings');
}

test.describe('Public File Sharing', () => {
  const TEST_PREFIX = `e2e-public-${Date.now()}`;
  const TEST_FILENAME = `${TEST_PREFIX}.txt`;
  let testFileId: number | null = null;

  test.afterEach(async () => {
    if (testFileId !== null) {
      execSync(
        `docker exec opendocman-db-1 mysql -u opendocman -pcWzzQzOySoBvoO84gJykRedP opendocman -e "${DB_CLEANUP(testFileId)}"`,
        { timeout: 10000 }
      );
      testFileId = null;
    }
  });

  test('public page shows disabled message when feature is off', async ({ page }) => {
    await login(page);
    await setPublicSharing(page, 'False');
    await retryGoto(page, '/public');
    await expect(page.locator('text=Public file sharing is disabled')).toBeVisible();
  });

  test('full flow: enable, add public file, approve, verify on public page, download', async ({ page }) => {
    await login(page);

    // Remember original setting to restore later
    await retryGoto(page, '/settings?submit=update&state=2');
    const originalValue = await page.evaluate(() => {
      const select = document.querySelector<HTMLSelectElement>('select[name="public_sharing"]');
      return select ? select.value : 'False';
    });

    // Enable public_sharing
    await setPublicSharing(page, 'True');

    // Go to add page, verify the "Public file" checkbox is visible
    await retryGoto(page, '/add');
    await expect(page.locator('#is_public')).toBeVisible();

    // Upload a test file with "Public file" checked
    const filePath = path.resolve(__dirname, `../${TEST_FILENAME}`);
    fs.writeFileSync(filePath, 'E2E test public document.');
    await page.setInputFiles('input[type="file"]', filePath);
    await page.fill('[name="description"]', 'E2E test public file');
    await page.check('#is_public');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/details*');

    // Get the file ID from the URL
    const url = page.url();
    const fileId = url.match(/id=(\d+)/)?.[1];
    expect(fileId).toBeDefined();
    testFileId = parseInt(fileId!, 10);

    // Approve the file in review queue
    await retryGoto(page, '/toBePublished');
    await page.locator(`[role="row"]:has-text("${TEST_FILENAME}")`).locator('input[type="checkbox"]').last().click();
    await page.click('button:has-text("Authorize")');
    await page.waitForTimeout(500);
    await page.click('button:has-text("Authorize")');
    await page.waitForURL('**/out*');

    // Verify file appears on the public page
    await retryGoto(page, '/public');
    await expect(page.locator(`text=${TEST_FILENAME}`).first()).toBeVisible();
    await expect(page.locator('text=E2E test public file').first()).toBeVisible();

    // Download the file
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 5000 }),
      page.click('a:has-text("Download")'),
    ]);
    expect(download.suggestedFilename()).toContain(TEST_FILENAME);

// Restore original public_sharing setting
    await setPublicSharing(page, originalValue);

    // Cleanup: remove temp file
    fs.unlinkSync(filePath);
  });
});