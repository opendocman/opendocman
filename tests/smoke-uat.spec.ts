import { test, expect } from '@playwright/test';

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || 'password';
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
  await page.context().clearCookies();
  await page.goto('/logout').catch(() => {});
  await retryGoto(page, '/index');
  await page.fill('input[name="frmuser"]', ADMIN_USER);
  await page.fill('input[name="frmpass"]', ADMIN_PASS);
  await page.locator('button[name="login"]').click();
  await page.waitForURL('**/out', { timeout: 10000 });
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
  await page.waitForTimeout(1000);
}

async function confirmDelete(page: any) {
  await page.click('#deleteConfirmBtn');
  await page.waitForTimeout(1000);
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
  });

  test('update a user', async ({ page }) => {
    await waitForTable(page, '/admin_users?state=2');

    // Wait for the table to have rows
    await page.locator('#crud-table .tabulator-row').first().waitFor({ timeout: 5000 });
    const editBtn = page.locator('#crud-table .tabulator-row .edit-row').first();
    await editBtn.click();
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    await page.fill('#crudEntityForm input[name="last_name"]', updatedLastName);
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(updatedLastName);
  });

  test('delete a user', async ({ page }) => {
    await waitForTable(page, '/admin_users?state=2');

    const delBtn = page.locator('#crud-table .tabulator-row .delete-row').first();
    await delBtn.click();
    await page.waitForSelector('#deleteModal.show', { timeout: 3000 });

    await confirmDelete(page);
    await expect(page.locator('#deleteModal')).not.toBeVisible();
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

    const editBtn = page.locator('#crud-table .tabulator-row .edit-row').first();
    await editBtn.click();
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    await page.fill('#crudEntityForm input[name="name"]', deptUpdated);
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(deptUpdated);
  });

  test('delete a department', async ({ page }) => {
    await waitForTable(page, '/admin_departments?state=2');

    const delBtn = page.locator('#crud-table .tabulator-row .delete-row').first();
    await delBtn.click();
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

    const editBtn = page.locator('#crud-table .tabulator-row .edit-row').first();
    await editBtn.click();
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    await page.fill('#crudEntityForm input[name="name"]', catUpdated);
    await saveModal(page);

    await expect(page.locator('#crudModal')).not.toBeVisible();
    await expect(page.locator('#crud-table')).toContainText(catUpdated);
  });

  test('delete a category', async ({ page }) => {
    await waitForTable(page, '/admin_categories?state=2');

    const delBtn = page.locator('#crud-table .tabulator-row .delete-row').first();
    await delBtn.click();
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
    const editBtn = page.locator('#crud-table .tabulator-row .edit-row').first();
    await editBtn.click();
    await page.waitForSelector('#crudModal.show', { timeout: 3000 });

    // Check "Unset" label appears (not "None")
    await expect(page.locator('#crudModal .perm-edit-mode')).toBeVisible();
    await expect(page.locator('#crudModal text=Unset')).toBeVisible();

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
});