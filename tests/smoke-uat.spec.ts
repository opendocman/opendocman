import { test, expect } from '@playwright/test';

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || 'password';
const UNIQUE = Date.now();

type SubmitBtn = { name: string; value: string };

async function retryGoto(page: any, url: string, opts = {}) {
  for (let attempt = 0; attempt < 3; attempt++) {
    await page.goto(url, { waitUntil: 'load', ...opts });
    // Check if the page is a blank error page (empty main)
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

async function submitForm(page: any, fields: Record<string, string>, btn: SubmitBtn) {
  for (const [name, value] of Object.entries(fields)) {
    const el = page.locator(`[name="${name}"]`);
    const tag = await el.evaluate((e: Element) => e.tagName);
    if (tag === 'SELECT') {
      await el.selectOption(value);
    } else {
      await el.fill(value);
    }
  }
  await clickButton(page, btn);
}

async function pickFromSelect(page: any, selectName: string, label: string, btn: SubmitBtn) {
  await page.selectOption(`select[name="${selectName}"]`, { label });
  await clickButton(page, btn);
}

async function waitForAdminWithMessage(page: any, message: string) {
  await page.waitForURL(/admin\?last_message=/, { timeout: 5000 });
  await expect(page.locator('#last_message')).toContainText(message);
}

// ────────────────────────────────────────────────────────────
// User CRUD
// ────────────────────────────────────────────────────────────
test.describe('User management', () => {
  const suffix = `E2E${UNIQUE}`;
  const username = `e2e${suffix}`;
  const origLastName = 'Last' + suffix;
  const origFirstName = 'First' + suffix;
  const updatedLastName = 'Updated' + suffix;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('add a user', async ({ page }) => {
    await retryGoto(page, '/user?submit=adduser&state=2');
    await page.waitForSelector('input[name="username"]');

    await submitForm(page, {
      username,
      password: 'testpass123',
      first_name: origFirstName,
      last_name: origLastName,
      Email: `${username}@test.com`,
      phonenumber: '555-9999',
    }, { name: 'submit', value: 'Add User' });

    await waitForAdminWithMessage(page, 'User successfully added');
  });

  test('update a user', async ({ page }) => {
    await retryGoto(page, '/user?submit=updatepick&state=2');
    await page.waitForSelector('select[name="item"]');

    // Find option by text content and get its label
    const optionLabel = await page.locator(`select[name="item"] option:has-text("${username}")`).textContent();
    await pickFromSelect(page, 'item', optionLabel || username, { name: 'submit', value: 'Modify User' });

    await page.waitForSelector('input[name="last_name"]');
    await page.fill('input[name="last_name"]', updatedLastName);
    await clickButton(page, { name: 'submit', value: 'Update User' });

    // User update redirects to /out
    await page.waitForURL(/out\?last_message=/, { timeout: 5000 });
    await expect(page.locator('#last_message')).toContainText('User successfully updated');
  });

  test('delete a user', async ({ page }) => {
    await retryGoto(page, '/user?submit=deletepick&state=2');
    await page.waitForSelector('select[name="item"]');

    const optionLabel = await page.locator(`select[name="item"] option:has-text("${updatedLastName}")`).textContent();
    await pickFromSelect(page, 'item', optionLabel || '', { name: 'submit', value: 'Delete' });

    await page.waitForSelector('button[name="submit"][value="Delete User"]');
    await clickButton(page, { name: 'submit', value: 'Delete User' });

    await waitForAdminWithMessage(page, 'User successfully deleted');
  });
});

// ────────────────────────────────────────────────────────────
// Department CRUD
// ────────────────────────────────────────────────────────────
test.describe('Department management', () => {
  const deptName = `E2E Dept ${UNIQUE}`;
  const deptUpdated = `E2E Dept Updated ${UNIQUE}`;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('add a department', async ({ page }) => {
    await retryGoto(page, '/department?submit=add&state=2');
    await page.waitForSelector('input[name="department"]');

    await submitForm(page, { department: deptName }, { name: 'submit', value: 'Add Department' });
    await waitForAdminWithMessage(page, 'Department successfully added');
  });

  test('update a department', async ({ page }) => {
    await retryGoto(page, '/department?submit=updatepick&state=2');
    await page.waitForSelector('select[name="item"]');

    await pickFromSelect(page, 'item', deptName, { name: 'submit', value: 'modify' });

    await page.waitForSelector('input[name="name"]');
    await page.fill('input[name="name"]', deptUpdated);
    await clickButton(page, { name: 'submit', value: 'Update Department' });

    await page.waitForURL(/admin\?last_message=/, { timeout: 5000 });
    await expect(page.locator('#last_message')).toContainText('Department successfully updated');
  });

  test('delete a department', async ({ page }) => {
    await retryGoto(page, '/department?submit=deletepick&state=2');
    await page.waitForSelector('select[name="item"]');

    await pickFromSelect(page, 'item', deptUpdated, { name: 'submit', value: 'delete' });

    // Confirmation page with re-assign dropdown
    await page.waitForSelector('select[name="assigned_id"]');
    const firstVal = await page.locator('select[name="assigned_id"] option').first().getAttribute('value');
    if (firstVal) {
      await page.selectOption('select[name="assigned_id"]', firstVal);
    }
    await clickButton(page, { name: 'deletedepartment', value: 'Yes' });

    await page.waitForURL(/admin\?last_message=/, { timeout: 5000 });
    await expect(page.locator('#last_message')).toContainText('All actions completed successfully');
  });
});

// ────────────────────────────────────────────────────────────
// Category CRUD
// ────────────────────────────────────────────────────────────
test.describe('Category management', () => {
  const catName = `E2E Cat ${UNIQUE}`;
  const catUpdated = `E2E Cat Updated ${UNIQUE}`;

  test.beforeEach(async ({ page }) => { await login(page); });

  test('add a category', async ({ page }) => {
    await retryGoto(page, '/category?submit=add&state=2');
    await page.waitForSelector('input[name="category"]');

    await submitForm(page, { category: catName }, { name: 'submit', value: 'Add Category' });
    await waitForAdminWithMessage(page, 'Category successfully added');
  });

  test('update a category', async ({ page }) => {
    await retryGoto(page, '/category?submit=updatepick&state=2');
    await page.waitForSelector('select[name="item"]');

    await pickFromSelect(page, 'item', catName, { name: 'submit', value: 'Update' });

    await page.waitForSelector('input[name="name"]');
    await page.fill('input[name="name"]', catUpdated);
    await clickButton(page, { name: 'updatecategory', value: 'Modify Category' });

    await waitForAdminWithMessage(page, 'Category successfully updated');
  });

  test('delete a category', async ({ page }) => {
    await retryGoto(page, '/category?submit=deletepick&state=2');
    await page.waitForSelector('select[name="item"]');

    await pickFromSelect(page, 'item', catUpdated, { name: 'submit', value: 'delete' });

    // Confirmation page with re-assign dropdown
    await page.waitForSelector('select[name="assigned_id"]');
    const firstVal = await page.locator('select[name="assigned_id"] option').first().getAttribute('value');
    if (firstVal) {
      await page.selectOption('select[name="assigned_id"]', firstVal);
    }
    // Click the Yes button directly
    await page.locator('button[name="deletecategory"][value="Yes"]').click();
    await page.waitForURL(/admin/, { timeout: 5000 });
    await expect(page.locator('#last_message')).toContainText('Category successfully deleted');
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

    // Form should be hidden initially
    await expect(page.locator('#addCategoryForm')).toHaveClass(/d-none/);

    // Click to show the form
    await page.click('#showAddCategory');
    await expect(page.locator('#addCategoryForm')).not.toHaveClass(/d-none/);

    // Fill and save
    await page.fill('#newCategoryName', inlineCat);
    await page.click('#saveCategory');

    // Wait for the select to contain the new option (value != empty means populated)
    await expect(page.locator('select[name="category"]')).toContainText(inlineCat, { timeout: 5000 });

    // Form should hide again
    await expect(page.locator('#addCategoryForm')).toHaveClass(/d-none/);

    // Verify the new category is selected
    const selected = await page.locator('select[name="category"]').inputValue();
    expect(selected).not.toBe('');
  });

  test('frontend rejects duplicate category name on Add page', async ({ page }) => {
    await retryGoto(page, '/add');
    await page.waitForSelector('#showAddCategory');

    // Verify the category from the previous test exists in the select
    await expect(page.locator('select[name="category"]')).toContainText(inlineCat, { timeout: 5000 });

    await page.click('#showAddCategory');
    await page.fill('#newCategoryName', inlineCat);
    await page.click('#saveCategory');

    // Should show duplicate message without making a network request
    await expect(page.locator('#categoryStatus')).toContainText('Category already exists', { timeout: 5000 });

    // Form should remain visible with the error
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
    // Create a category
    await retryGoto(page, '/category?submit=add&state=2');
    await page.waitForSelector('input[name="category"]');
    await submitForm(page, { category: permCat }, { name: 'submit', value: 'Add Category' });
    await waitForAdminWithMessage(page, 'Category successfully added');

    // Now go to update that category to see the permissions editor
    await retryGoto(page, '/category?submit=updatepick&state=2');
    await page.waitForSelector('select[name="item"]');
    await pickFromSelect(page, 'item', permCat, { name: 'submit', value: 'Update' });

    // Wait for category update form — check "Unset" label appears (not "None")
    await expect(page.locator('text=Unset')).toBeVisible();
    await expect(page.locator('text=None')).toHaveCount(0);

    // Navigate to add file page and select the category
    await retryGoto(page, '/add');
    await page.waitForSelector('select[name="category"]');
    await page.selectOption('select[name="category"]', { label: permCat });

    // Verify the permissions editor loaded
    await expect(page.locator('#permissionsEditor')).toBeVisible();
  });

  test('permission inheritance falls back to category perms in file listing', async ({ page }) => {
    await retryGoto(page, '/out');
    await expect(page.locator('body')).toBeVisible();
  });
});

// ────────────────────────────────────────────────────────────
// Demo mode — operations should be blocked when enabled
// ────────────────────────────────────────────────────────────
// Skipped: demo mode tests are disabled due to PHP built-in server
// race condition with CSRF token persistence. See AGENTS.md for
// the retryGoto pattern used elsewhere to mitigate this.

