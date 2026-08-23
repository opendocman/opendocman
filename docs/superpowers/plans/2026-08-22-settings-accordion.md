# Settings Accordion + Top Search Implementation Plan (ODM #436 follow-up)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Settings page's vertical-tab + in-rail-search layout with a multi-open Bootstrap accordion (one panel per settings group) plus a full-width page-level search box that filters settings live and auto-expands/collapses groups.

**Architecture:** The Settings page is rendered by `Settings::assignSettings()` → `renderContent()` → `settings.tpl` inside `_admin_content.tpl`. The redesign is confined to `settings.tpl` (markup → accordion) and `admin-settings.js` (search + sidebar group-link behavior). The single form, save path, CSRF, per-control rendering, and the shell wrapper are unchanged.

**Tech Stack:** PHP 8.x, Smarty 2.6.28, Bootstrap 5.3 (accordion + collapse via global `bootstrap.bundle.min.js`), vanilla JS, PHPUnit + Mockery, Playwright.

## Global Constraints

- Deployed Smarty is **2.6.28** (NOT Smarty 3): templates must use explicit `{foreach from=... item=...}`, `{$x|default:'f'}`, `{$arr|@count}` (never shorthand foreach, never `|count` on an array).
- The save path must stay byte-for-byte intact: `form#settingsForm` POST to `settings`, `{$csrf_token_field}`, `submit=Save`, per-control rendering identical (bool/theme/language/file_expired_action/authen/root_id/default_signup_department/else text), sticky Save/Cancel footer.
- `admin-settings.js` must keep the sidebar Settings-Groups deep-links working (they now expand the matching accordion panel instead of switching a tab).
- Keep the `.setting-row` markup + `data-settings-name`/`data-settings-desc` attributes exactly (the E2E and the search filter depend on them).
- Keep `#settingsFilter` as the search input id; keep `#settingsEmpty` as the empty-state id; keep group panel ids `group-{$grp.name}`.
- The E2E DB UI is Portuguese — tests must assert DOM ids / setting names, never English labels.
- `tests/admin-settings.spec.ts` must be updated to the accordion and re-registered (already in `playwright.config.ts` smoke `testMatch`).
- No DB schema change; no model change to `Settings`.

---

### Task 1: Rewrite `settings.tpl` to a multi-open accordion

**Files:**
- Modify: `application/views/common/settings.tpl`

**Interfaces:**
- Consumes: `$settings_groups` (shape `[slug => ['label' => string, 'name' => slug, 'settings' => [rows]]]`), `$themes`, `$languages`, `$useridnums`, `$departments` (all assigned by `Settings::assignSettings()`).
- Produces: a single-column layout with `#settingsFilter` (full width), a `.accordion` whose `.accordion-item` per group has header button + `.accordion-collapse` body (id `group-{$grp.name}`), the unchanged per-control renderer, and the sticky Save/Cancel footer. Form keeps `id="settingsForm"`.

- [ ] **Step 1: Replace the template body**

Replace the entire contents of `application/views/common/settings.tpl` with:

```smarty
<form action="settings" method="POST" enctype="multipart/form-data" id="settingsForm" class="mt-3">
    {$csrf_token_field}

    <div class="mb-3">
        <input type="search" id="settingsFilter" class="form-control form-control-sm"
               placeholder="{$g_lang_settings_filter|default:'Search settings…'}" aria-label="{$g_lang_settings_filter|default:'Search settings…'}">
    </div>

    <div class="accordion" id="settingsAccordion">
        {foreach from=$settings_groups item=grp name=grps}
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{$grp.name}">
                <button class="accordion-button {if !$smarty.foreach.grps.first}collapsed{/if}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#group-{$grp.name}" aria-expanded="{if $smarty.foreach.grps.first}true{else}false{/if}"
                        aria-controls="group-{$grp.name}">
                    {$grp.label|escape:'html'}
                    <span class="badge bg-secondary ms-2">{$grp.settings|@count}</span>
                </button>
            </h2>
            <div id="group-{$grp.name}" class="accordion-collapse collapse {if $smarty.foreach.grps.first}show{/if}"
                 aria-labelledby="heading-{$grp.name}">
                <div class="accordion-body">
                    {foreach from=$grp.settings item=i}
                    <div class="mb-3 row setting-row" data-settings-name="{$i.name|escape:'html'}" data-settings-desc="{$i.description|escape:'html'}">
                        <label class="col-sm-3 col-form-label">{$i.name|escape:'html'}</label>
                        <div class="col-sm-4">
                        {if $i.validation eq 'bool'}
                            <select name="{$i.name|escape:'html'}" class="form-select">
                                <option value="True" {if $i.value eq 'True'} selected="selected"{/if}>True</option>
                                <option value="False" {if $i.value eq 'False'} selected="selected"{/if}>False</option>
                            </select>
                        {elseif $i.name eq 'theme'}
                            <select name="theme" class="form-select">
                                {foreach from=$themes item=theme}
                                    <option value="{$theme|escape}" {if $i.value eq $theme}selected="selected"{/if}>{$theme|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'language'}
                            <select name="language" class="form-select">
                                {foreach from=$languages item=language}
                                    <option value="{$language|escape}" {if $i.value eq $language} selected="selected"{/if}>{$language|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'file_expired_action'}
                            <select name="file_expired_action" class="form-select">
                                <option value="1" {if $i.value eq '1'}selected="selected"{/if}>Remove from file list until renewed</option>
                                <option value="2" {if $i.value eq '2'}selected="selected"{/if}>Show in file list but non-checkoutable</option>
                                <option value="3" {if $i.value eq '3'}selected="selected"{/if}>Send email to reviewer only</option>
                                <option value="4" {if $i.value eq '4'}selected="selected"{/if}>Do Nothing</option>
                            </select>
                        {elseif $i.name eq 'authen'}
                            <select name="authen" class="form-select">
                                <option value="mysql" {if $i.value eq 'mysql'}selected="selected"{/if}>MySQL</option>
                            </select>
                        {elseif $i.name eq 'root_id'}
                            <select name="root_id" class="form-select">
                                {foreach from=$useridnums item=useridnum}
                                    <option value="{$useridnum[0]|escape}" {if $i.value eq $useridnum[0]} selected="selected"{/if}>{$useridnum[1]|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'default_signup_department'}
                            <select name="default_signup_department" class="form-select">
                                <option value="" {if $i.value eq ''}selected="selected"{/if}>-- unassigned --</option>
                                {foreach from=$departments item=dept}
                                    <option value="{$dept[0]|escape}" {if $i.value eq $dept[0]}selected="selected"{/if}>{$dept[1]|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {else}
                            <input name="{$i.name|escape}" type="text" value="{$i.value|escape:'html'}" class="form-control">
                        {/if}
                        </div>
                        <div class="col-sm-5"><em>{$i.description|escape:'html'}</em></div>
                    </div>
                    {/foreach}
                </div>
            </div>
        </div>
        {/foreach}
    </div>

    <div class="d-flex gap-2 mt-3 sticky-bottom bg-white py-2">
        <button class="btn btn-primary" type="submit" name="submit" value="Save">{$g_lang_button_save}</button>
        <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
    </div>
</form>
<script src="{$g_base_url}js/bootstrap5/admin-settings.js"></script>
```

Note: **fully multi-open** — no `data-bs-parent` is present, so every panel toggles independently (clicking one header never closes another). The JS filter may also expand/collapse multiple panels at once.

- [ ] **Step 2: Verify it renders**

Run the unit suite (no model change, but confirm no template regressions):
Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --testsuite Unit`
Expected: OK (358 tests, 2028 assertions).

Also php -l best-effort: `php -l application/views/common/settings.tpl` (Smarty may not parse under php -l; if it errors, that's expected — real verification is the browser/E2E).

- [ ] **Step 3: Commit**

```bash
git add application/views/common/settings.tpl
git commit -m "feat(settings): convert settings page to multi-open accordion (ODM #436)"
```

---

### Task 2: Rework `admin-settings.js` — accordion search + sidebar panel expansion

**Files:**
- Modify: `public/js/bootstrap5/admin-settings.js`

**Interfaces:**
- Consumes: `#settingsFilter`, `#settingsAccordion`, `.accordion-collapse` ids `group-{$grp.name}`, `.setting-row` with `data-settings-name`/`data-settings-desc`, `#adminSidebarNav .setting-group-link[data-group]`, `#settingsEmpty`.
- Produces: live row filter with per-panel auto expand/collapse; sidebar group-link → expand matching panel; `#settingsEmpty` empty state; previous open/closed state restored on clear.

- [ ] **Step 1: Write the new JS**

Replace the entire contents of `public/js/bootstrap5/admin-settings.js` with:

```js
document.addEventListener('DOMContentLoaded', function () {
  var filter = document.getElementById('settingsFilter');
  var panels = Array.prototype.slice.call(document.querySelectorAll('#settingsAccordion .accordion-collapse'));
  var defaultState = {}; // group slug -> initially-open flag (from the template markup)
  var previousState = {}; // group slug -> open/closed as the user left it

  panels.forEach(function (panel) {
    var group = panel.id.replace('group-', '');
    defaultState[group] = panel.classList.contains('show');
    previousState[group] = defaultState[group];
  });

  function collapseInstance(panel) {
    return bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
  }

  function setPanel(panel, open) {
    var inst = collapseInstance(panel);
    if (open && !panel.classList.contains('show')) {
      inst.show();
    } else if (!open && panel.classList.contains('show')) {
      inst.hide();
    }
  }

  // Sidebar Settings-Groups deep-links: expand the matching accordion panel.
  document.querySelectorAll('#adminSidebarNav .setting-group-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var group = link.getAttribute('data-group');
      var panel = document.getElementById('group-' + group);
      if (panel) {
        setPanel(panel, true);
        previousState[group] = true;
      }
    });
  });

  if (!filter) return;

  filter.addEventListener('input', function () {
    var q = filter.value.trim().toLowerCase();
    var anyVisible = false;

    panels.forEach(function (panel) {
      var group = panel.id.replace('group-', '');
      var rows = panel.querySelectorAll('.setting-row');
      var panelHasMatch = false;

      rows.forEach(function (r) {
        var hay = (r.dataset.settingsName + ' ' + r.dataset.settingsDesc).toLowerCase();
        var hide = q !== '' && hay.indexOf(q) === -1;
        r.style.display = hide ? 'none' : '';
        if (!hide) {
          panelHasMatch = true;
          anyVisible = true;
        }
      });

      // While searching: auto-expand groups with matches, collapse groups without.
      // When the query is cleared: restore the user's previous open/closed state.
      if (q !== '') {
        setPanel(panel, panelHasMatch);
      } else {
        setPanel(panel, previousState[group]);
      }
    });

    // Empty-state text when nothing matches
    var empty = document.getElementById('settingsEmpty');
    if (q !== '' && !anyVisible) {
      if (!empty) {
        empty = document.createElement('div');
        empty.id = 'settingsEmpty';
        empty.className = 'text-muted text-center py-4';
        empty.textContent = filter.dataset.emptyMsg || 'No settings match';
        var accordion = document.getElementById('settingsAccordion');
        accordion.parentNode.insertBefore(empty, accordion);
      }
      empty.style.display = '';
    } else if (empty) {
      empty.style.display = 'none';
    }
  });

  // Record manual open/closed state so clearing the search can restore it.
  panels.forEach(function (panel) {
    panel.addEventListener('shown.bs.collapse', function () {
      previousState[panel.id.replace('group-', '')] = true;
    });
    panel.addEventListener('hidden.bs.collapse', function () {
      previousState[panel.id.replace('group-', '')] = false;
    });
  });
});
```

- [ ] **Step 2: Verify JS syntax + no regressions**

Run: `node --check public/js/bootstrap5/admin-settings.js`
Expected: no syntax errors.

Run unit suite (unchanged model): `php application/vendor/bin/phpunit -c phpunit.xml.dist --testsuite Unit`
Expected: OK.

- [ ] **Step 3: Confirm multi-open manual behavior**

The template has no `data-bs-parent`, so manual clicks already toggle each panel independently (fully multi-open). Verify in the E2E (Task 3) that at least two panels are open simultaneously after manual clicks.

- [ ] **Step 4: Commit**

```bash
git add public/js/bootstrap5/admin-settings.js
git commit -m "feat(settings): accordion search filter + sidebar panel expansion (ODM #436)"
```

---

### Task 3: Update the E2E spec to the accordion

**Files:**
- Modify: `tests/admin-settings.spec.ts`

**Interfaces:**
- Consumes: the accordion markup (`.accordion-item`, `.accordion-header` button with `data-bs-target`, `.accordion-collapse` id `group-{$grp.name}`, `#settingsFilter`, `#settingsAccordion`, `#adminSidebar`, `#adminSidebarNav`), and the JS behavior from Tasks 1-2.

- [ ] **Step 1: Rewrite the spec's three tests**

Replace the three test bodies in `tests/admin-settings.spec.ts` (keep the imports, `retryGoto`, and `loginAs` helpers unchanged):

```ts
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
  await expect(page.locator('#group-general .badge')).toHaveText(/\d+/);

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
```

- [ ] **Step 2: Run the spec against the live app**

Start the app if needed (`make serve-local-quiet` in a background shell; the DB is the Portuguese-seeded dev DB). Then:
Run: `ADMIN_USER=admin ADMIN_PASSWORD=admin npx playwright test --project=smoke tests/admin-settings.spec.ts`
Expected: 3 passed.

- [ ] **Step 3: Commit**

```bash
git add tests/admin-settings.spec.ts
git commit -m "e2e: cover settings accordion + top search (ODM #436)"
```

---

### Task 4: Final verification of the accordion redesign

**Files:** none new.

- [ ] **Step 1: Full unit suite**

Run: `make test`
Expected: all green (457 tests equivalent).

- [ ] **Step 2: Manual smoke on the live app**

Start `make serve-local-quiet`, log in as admin/admin, and verify:
1. `/settings?submit=update` renders `#settingsFilter`, `#settingsAccordion`, and each group panel (General open by default, with the count badge).
2. Clicking a panel header expands/collapses it (multi-open: open Security then Storage, both stay open).
3. Typing `theme` auto-expands the UI & Appearance panel and shows its theme row; typing a nonsense string shows the `#settingsEmpty` "No settings match" message.
4. Clearing the search restores the previous panel state.
5. Change `title` → Save → the value persists (save path intact) and `public_sharing` select still renders.
6. From the sidebar's Settings Groups sub-nav, click "General" — the General panel expands.

- [ ] **Step 3: Commit any final polish**

If anything needed a fix, commit it. If the tree is clean, do NOT create an empty commit — just report.

---

## Self-Review

**Spec coverage:**
- Multi-open accordion (one panel per group) → Tasks 1-2 (Task 2 Step 3 decides manual multi-open vs single-open-per-click and the E2E asserts both panels open).
- Page-level search filters rows live, auto-expands matched groups / collapses empty ones, restores prior state on clear → Task 2.
- "No settings match" empty state → Task 2.
- Sidebar Settings-Groups links expand the matching panel → Task 2.
- Save path, CSRF, per-control rendering, sticky footer unchanged → Task 1 (carried verbatim).
- Remove tab rail + in-rail search → Task 1.
- E2E updated + re-registered → Task 3 (config already lists the spec).
- Unit suite stays green → Tasks 1/2/4.

**Placeholder scan:** No TBD/TODO. The Task 2 Step 3 "decide based on spec language" is a concrete instruction with a test to enforce it, not a placeholder.

**Type consistency:** Group ids `group-{$grp.name}` used consistently in template, JS, and E2E. `#settingsFilter`, `#settingsEmpty`, `#settingsAccordion`, `#adminSidebarNav .setting-group-link` all consistent. `.setting-row` with `data-settings-name`/`data-settings-desc` preserved. `$settings_groups` shape unchanged from earlier tasks (`['label','name','settings']`).