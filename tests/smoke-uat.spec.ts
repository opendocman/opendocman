import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || 'password';
const NON_ADMIN_USER = process.env.NON_ADMIN_USER || 'e2euser';
const NON_ADMIN_PASS = process.env.NON_ADMIN_PASSWORD || 'e2euserpass';
// Display name "last_name, first_name" as seeded by scripts/seed_test_user.php.
const NON_ADMIN_DISPLAY = process.env.NON_ADMIN_DISPLAY || 'User, E2E';
const UNIQUE = Date.now();

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
  await loginAs(page, ADMIN_USER, ADMIN_PASS);
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

async function waitForTable(page: any, url: string) {
  await retryGoto(page, url);
  // Wait for Tabulator to render rows in the table
  await page.waitForSelector('#crud-table .tabulator-row', { timeout: 8000 });
}

async function clickAdd(page: any) {
  await page.click('#addBtn');
  await page.waitForSelector('#crudModal.show', { timeout: 3000 });
}

async function fillModalForm(page: any, fields: Record<string, string>) {
  for (const [name, value] of Object.entries(fields)) {
    const el = page.locator(`#crudEntityForm [name="${name}"]`);
    const tag = await el.evaluate((e: Element) => e.tagName);
    if (tag === 'SELECT') {
      await el.selectOption(value);
    } else {
      await el.fill(value);
    }
  }
}

async function saveModal(page: any) {
  await page.click('#crudModalSave');
  // Wait for modal to close (success) or alert to appear (error)
  await page.waitForTimeout(3000);
  // Handle any alert that may have popped up
  await page.waitForTimeout(500);
}

async function confirmDelete(page: any) {
  await page.click('#deleteConfirmBtn');
  await page.waitForTimeout(1000);
}

async function clickEditRow(page: any, text: string) {
  const row = page.locator('#crud-table .tabulator-row').filter({ hasText: text }).first();
  await row.waitFor({ timeout: 5000 });
  await row.locator('.edit-row').click();
}

async function clickDeleteRow(page: any, text: string) {
  const row = page.locator('#crud-table .tabulator-row').filter({ hasText: text }).first();
  await row.waitFor({ timeout: 5000 });
  await row.locator('.delete-row').click();
}

// ────────────────────────────────────────────────────────────
// User CRUD (new Tabulator-based)
// ────────────────────────────────────────────────────────────
test.describe('User management', () => {
  const suffix = `E2E${UNIQUE}`;
  const username = `e2e${suffix}`;
  const origLastName = 'Last' + suffix;
  const origFirstName = 'First' + suffix;
  const updatedLastName = 'Updated' + suffix;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('add a user', async ({ page }) => {
    await waitForTable(page, '/admin_users?state=2');
    await clickAdd(page);

    await fillModalForm(page, {
      username,
      password: 'testpass123',
      first_name: origFirstName,
      last_name: origLastName,
      email: `${username}@test.com`,
      phone: '555-9999',
    });
    await saveModal(page);

    // Verify the modal closed and table refreshed
    await expect(page.locator('#crudModal')).not.toBeVisible();
    // Verify the new user appears in the table
    await expect(page.locator('#crud-table')).toContainText(username);
    // Confirm the success flash is shown
    await expect(page.locator('#crudFlash')).toContainText('saved successfully');
  });

  test('update a user', async ({ page }) => {
    await waitForTable(page, '/admin_users?state=2');

    await clickEditRow(page, username);
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    await page.fill('#crudEntityForm input[name="last_name"]', updatedLastName);
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(updatedLastName);
    // Confirm the success flash is shown
    await expect(page.locator('#crudFlash')).toContainText('saved successfully');
  });

  test('delete a user', async ({ page }) => {
    await waitForTable(page, '/admin_users?state=2');

    await clickDeleteRow(page, username);
    await page.waitForSelector('#deleteModal.show', { timeout: 3000 });

    await confirmDelete(page);
    await expect(page.locator('#deleteModal')).not.toBeVisible();
    // Confirm the success flash is shown
    await expect(page.locator('#crudFlash')).toContainText('deleted successfully');
  });
});

// ────────────────────────────────────────────────────────────
// Department CRUD (new Tabulator-based)
// ────────────────────────────────────────────────────────────
test.describe('Department management', () => {
  const deptName = `E2E Dept ${UNIQUE}`;
  const deptUpdated = `E2E Dept Updated ${UNIQUE}`;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('add a department', async ({ page }) => {
    await waitForTable(page, '/admin_departments?state=2');
    await clickAdd(page);

    await fillModalForm(page, { name: deptName });
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(deptName);
  });

  test('update a department', async ({ page }) => {
    await waitForTable(page, '/admin_departments?state=2');

    await clickEditRow(page, deptName);
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    await page.fill('#crudEntityForm input[name="name"]', deptUpdated);
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(deptUpdated);
  });

  test('delete a department', async ({ page }) => {
    await waitForTable(page, '/admin_departments?state=2');

    await clickDeleteRow(page, deptUpdated);
    await page.waitForSelector('#deleteModal.show', { timeout: 3000 });

    // Select reassign target
    const firstVal = await page.locator('#reassignSelect option').first().getAttribute('value');
    if (firstVal) {
      await page.selectOption('#reassignSelect', firstVal);
    }

    await confirmDelete(page);
    await expect(page.locator('#deleteModal')).not.toBeVisible();
  });
});

// ────────────────────────────────────────────────────────────
// Category CRUD (new Tabulator-based)
// ────────────────────────────────────────────────────────────
test.describe('Category management', () => {
  const catName = `E2E Cat ${UNIQUE}`;
  const catUpdated = `E2E Cat Updated ${UNIQUE}`;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('add a category', async ({ page }) => {
    await waitForTable(page, '/admin_categories?state=2');
    await clickAdd(page);

    await fillModalForm(page, { name: catName });
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(catName);
  });

  test('update a category', async ({ page }) => {
    await waitForTable(page, '/admin_categories?state=2');

    await clickEditRow(page, catName);
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    await page.fill('#crudEntityForm input[name="name"]', catUpdated);
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(catUpdated);
  });

  test('delete a category', async ({ page }) => {
    await waitForTable(page, '/admin_categories?state=2');

    await clickDeleteRow(page, catUpdated);
    await page.waitForSelector('#deleteModal.show', { timeout: 3000 });

    // Select reassign target
    const firstVal = await page.locator('#reassignSelect option').first().getAttribute('value');
    if (firstVal) {
      await page.selectOption('#reassignSelect', firstVal);
    }

    await confirmDelete(page);
    await expect(page.locator('#deleteModal')).not.toBeVisible();
  });
});

// ────────────────────────────────────────────────────────────
// Inline Add Category on Add/Edit File pages
// ────────────────────────────────────────────────────────────
test.describe('Inline Add Category', () => {
  const inlineCat = `E2E Inline ${UNIQUE}`;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('inline add category on Add File page', async ({ page }) => {
    await retryGoto(page, '/add');
    await page.waitForSelector('#showAddCategory');

    await expect(page.locator('#addCategoryForm')).toHaveClass(/d-none/);

    await page.click('#showAddCategory');
    await expect(page.locator('#addCategoryForm')).not.toHaveClass(/d-none/);

    await page.fill('#newCategoryName', inlineCat);
    await page.click('#saveCategory');

    await expect(page.locator('select[name="category"]')).toContainText(inlineCat, { timeout: 5000 });
    await expect(page.locator('#addCategoryForm')).toHaveClass(/d-none/);

    const selected = await page.locator('select[name="category"]').inputValue();
    expect(selected).not.toBe('');
  });

  test('frontend rejects duplicate category name on Add page', async ({ page }) => {
    await retryGoto(page, '/add');
    await page.waitForSelector('#showAddCategory');

    await expect(page.locator('select[name="category"]')).toContainText(inlineCat, { timeout: 5000 });

    await page.click('#showAddCategory');
    await page.fill('#newCategoryName', inlineCat);
    await page.click('#saveCategory');

    await expect(page.locator('#categoryStatus')).toContainText('Category already exists', { timeout: 5000 });
    await expect(page.locator('#addCategoryForm')).not.toHaveClass(/d-none/);
  });
});

// ────────────────────────────────────────────────────────────
// Permission Inheritance
// ────────────────────────────────────────────────────────────
test.describe('Permission inheritance', () => {
  const permCat = `E2E Perm ${Date.now()}`;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('admin can set category permission template and it pre-fills on add file', async ({ page }) => {
    // Create a category via the new CRUD
    await waitForTable(page, '/admin_categories?state=2');
    await clickAdd(page);
    await fillModalForm(page, { name: permCat });
    await saveModal(page);
    await expect(page.locator('#crudModal')).not.toBeVisible();

    // Now go to update that category to see the permissions editor
    await waitForTable(page, '/admin_categories?state=2');
    await clickEditRow(page, permCat);
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    // Check the permissions editor is visible in overview mode
    await expect(page.locator('#crudModal .perm-overview-mode')).toBeVisible();

    // Switch to edit mode to see "Unset" labels
    await page.locator('#crudModal .perm-mode-btn[data-mode="edit"]').click();
    await expect(page.locator('#crudModal .perm-edit-mode')).toBeVisible();
    await expect(page.locator('#crudModal .perm-edit-mode')).toContainText('Unset');

    // Close the modal without saving
    await page.click('#crudModal .btn-close');
    await expect(page.locator('#crudModal')).not.toBeVisible();

    // Navigate to add file page and select the category
    await retryGoto(page, '/add');
    await page.waitForSelector('select[name="category"]');
    await page.selectOption('select[name="category"]', { label: permCat });

    await expect(page.locator('#permissionsEditor')).toBeVisible();
  });

  test('permission inheritance falls back to category perms in file listing', async ({ page }) => {
    await retryGoto(page, '/out');
    await expect(page.locator('body')).toBeVisible();
  });

  test('non-admin owner keeps admin rights after adding a document', async ({ page }) => {
    // Log in as a non-admin user and add a document WITHOUT setting any
    // explicit permissions for themselves. The server-side guard in add.php
    // must grant the owner admin so they are not locked out of their own file.
    const testFile = path.join('/tmp', `odm-nonadmin-${UNIQUE}.txt`);
    fs.writeFileSync(testFile, 'Non-admin owner regression test');
    try {
      await loginAs(page, NON_ADMIN_USER, NON_ADMIN_PASS);
      await retryGoto(page, '/add');
      await page.locator('input[name="file[]"]').waitFor({ state: 'attached', timeout: 5000 });
      await page.fill('input[name="description"]', 'Non-admin owner test ' + UNIQUE);
      await page.setInputFiles('input[name="file[]"]', testFile);
      await page.selectOption('select[name="category"]', { index: 1 });
      await page.click('#submitBtn, button[type="submit"], input[type="submit"]');

      // The redirect should land on the details page WITHOUT the lockout error,
      // and show the non-admin user as the owner of the file.
      await page.waitForURL(/details\?id=\d+/, { timeout: 10000 });
      await expect(page.locator('body')).not.toContainText(msgUnableToFindFile(), { timeout: 5000 });
      await expect(page.locator('body')).toContainText(NON_ADMIN_DISPLAY, { timeout: 5000 });
    } finally {
      fs.unlinkSync(testFile);
    }
  });

  test('admin switching owner on add moves the admin grant in the permissions matrix', async ({ page }) => {
    const testFile = path.join('/tmp', `odm-owner-switch-${UNIQUE}.txt`);
    fs.writeFileSync(testFile, 'Owner switch regression test');
    try {
      await login(page);
      await retryGoto(page, '/add');

      // Default owner should be the admin (User, Admin) shown with admin rights.
      await page.locator('input[name="file[]"]').waitFor({ state: 'attached', timeout: 5000 });
      await page.selectOption('select[name="file_owner"]', { index: 0 }); // User, Admin
      const ownerRow = page.locator('#permissionsEditor .perm-overview-mode tbody tr').filter({ hasText: 'User, Admin' });
      await expect(ownerRow).toContainText('\u2713', { timeout: 5000 });

      // Switch owner to the non-admin user; admin grant must move to them.
      await page.selectOption('select[name="file_owner"]', { label: NON_ADMIN_DISPLAY });
      const newOwnerRow = page.locator('#permissionsEditor .perm-overview-mode tbody tr').filter({ hasText: NON_ADMIN_DISPLAY });
      await expect(newOwnerRow).toContainText('\u2713', { timeout: 5000 });
      // The old owner should no longer have admin.
      await expect(ownerRow).not.toContainText('\u2713');

      // Submit and confirm the new owner is granted admin in the DB-backed detail view.
      await page.fill('input[name="description"]', 'Owner switch test ' + UNIQUE);
      await page.setInputFiles('input[name="file[]"]', testFile);
      await page.selectOption('select[name="category"]', { index: 1 });
      await page.click('#submitBtn, button[type="submit"], input[type="submit"]');
      await page.waitForURL(/details\?id=\d+/, { timeout: 10000 });
      await expect(page.locator('body')).not.toContainText(msgUnableToFindFile(), { timeout: 5000 });
      await expect(page.locator('body')).toContainText(NON_ADMIN_DISPLAY, { timeout: 5000 });
    } finally {
      fs.unlinkSync(testFile);
    }
  });
});

function msgUnableToFindFile(): string {
  return 'Unable to find the requested file';
}
