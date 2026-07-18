import { test, expect } from '@playwright/test';

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || 'password';
const UNIQUE = Date.now();

type SubmitBtn = { name: string; value: string };

async function login(page: any) {
  await page.context().clearCookies();
  await page.goto('/logout').catch(() => {});
  await page.goto('/index');
  await page.fill('input[name="frmuser"]', ADMIN_USER);
  await page.fill('input[name="frmpass"]', ADMIN_PASS);
  await page.evaluate(() =>
    document.querySelector<HTMLInputElement>('input[type="submit"][name="login"]')?.click()
  );
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
    await page.goto('/user?submit=adduser&state=2');
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
    await page.goto('/user?submit=updatepick&state=2');
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
    await page.goto('/user?submit=deletepick&state=2');
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
    await page.goto('/department?submit=add&state=2');
    await page.waitForSelector('input[name="department"]');

    await submitForm(page, { department: deptName }, { name: 'submit', value: 'Add Department' });
    await waitForAdminWithMessage(page, 'Department successfully added');
  });

  test('update a department', async ({ page }) => {
    await page.goto('/department?submit=updatepick&state=2');
    await page.waitForSelector('select[name="item"]');

    await pickFromSelect(page, 'item', deptName, { name: 'submit', value: 'modify' });

    await page.waitForSelector('input[name="name"]');
    await page.fill('input[name="name"]', deptUpdated);
    await clickButton(page, { name: 'submit', value: 'Update Department' });

    await page.waitForURL(/admin\?last_message=/, { timeout: 5000 });
    await expect(page.locator('#last_message')).toContainText('Department successfully updated');
  });

  test('delete a department', async ({ page }) => {
    await page.goto('/department?submit=deletepick&state=2');
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
    await page.goto('/category?submit=add&state=2');
    await page.waitForSelector('input[name="category"]');

    await submitForm(page, { category: catName }, { name: 'submit', value: 'Add Category' });
    await waitForAdminWithMessage(page, 'Category successfully added');
  });

  test('update a category', async ({ page }) => {
    await page.goto('/category?submit=updatepick&state=2');
    await page.waitForSelector('select[name="item"]');

    await pickFromSelect(page, 'item', catName, { name: 'submit', value: 'Update' });

    await page.waitForSelector('input[name="name"]');
    await page.fill('input[name="name"]', catUpdated);
    await clickButton(page, { name: 'updatecategory', value: 'Modify Category' });

    await waitForAdminWithMessage(page, 'Category successfully updated');
  });

  test('delete a category', async ({ page }) => {
    await page.goto('/category?submit=deletepick&state=2');
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
