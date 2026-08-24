# Design — Settings page: multi-open accordion + top search (ODM #436 follow-up)

## Problem

The Settings page currently renders the groups as a **vertical tab rail** with a
search box in that rail (`application/views/common/settings.tpl`). The user
finds the tabbed navigation on the Settings page redundant with the admin
sidebar (which already lists every section including a Settings Groups
sub-nav). They want the Settings page to present the groups as an **accordion**
instead, while keeping a page-level search that filters within it.

## Goal

Replace the Settings page's vertical-tab + in-rail-search layout with a
**multi-open Bootstrap accordion** (one collapsible panel per settings group)
plus a **full-width page-level search box** that filters settings live and
auto-expands/collapses groups. Save path, per-control rendering, and the
sidebar's Settings-Groups deep-links remain intact (deep-links now expand the
matching accordion panel).

## Decisions

1. **Multi-open accordion.** Each settings group is an independent
   `.accordion-item` panel (no `data-bs-parent`), so any number of groups can
   be open at once. Bootstrap 5 `accordion` + `collapse` components (JS already
   loaded globally via `bootstrap.bundle.min.js`).
2. **Page-level search, live filter.** A full-width `input#settingsFilter`
   above the accordion filters `.setting-row` rows across all panels by name +
   description. Matching rows stay visible; non-matching rows hide.
3. **Auto expand/collapse from search.** While a query is active, panels
   containing matches are forced open (`.show`), panels with no matches are
   forced closed. Clearing the query restores the previous manual open/closed
   state.
4. **Empty state.** When a query matches no settings, show a
   "No settings match &ldquo;X&rdquo;" message (same pattern as the current tab
   version).
5. **Sidebar Settings-Groups links expand panels.** The existing
   `#adminSidebarNav .setting-group-link` deep-links (rendered by
   `_admin_sidebar.tpl` when `$settings_groups` is set) now expand the matching
   accordion panel by its group id instead of switching a vertical tab.
6. **Save path unchanged.** The accordion stays inside the single
   `form#settingsForm` POST to `settings`; CSRF, per-control rendering,
   dataDir/snapshotDir cleanup, numeric validation, and the sticky
   Save/Cancel footer are identical to the current version.
7. **Remove the tab rail + its search box.** The left `nav-pills` column
   (`#settingsTabs`) and the in-rail `#settingsFilter` box are removed; the
   page body is a single column.

## Components

### 1. `application/views/common/settings.tpl` (rewrite)

Replace the two-column (rail + tab-content) layout with a single column:

- `#settingsFilter` search box (full-width, `form-control`), `aria-label` set.
- A Bootstrap accordion where each `$settings_groups` entry becomes an
  `.accordion-item` with a header button (group label + count badge) and a
  `.accordion-collapse` body containing that group's `.setting-row` rows.
- Each panel's collapse id derives from the group slug: `group-{$grp.name}`
  (kept so the E2E test's `#group-general`-style selectors and the sidebar
  deep-links keep working).
- The form `id="settingsForm"`, `{$csrf_token_field}`, per-control renderer
  (bool/theme/language/file_expired_action/authen/root_id/default_signup_department/else text),
  and the sticky Save/Cancel footer are carried over verbatim.
- Include `admin-settings.js` at the end of the form (unchanged include).

### 2. `public/js/bootstrap5/admin-settings.js` (rework)

- Remove the vertical-tab activation handler.
- Keep the sidebar `#adminSidebarNav .setting-group-link` click handler, but
  change it to expand the matching accordion panel:
  ```js
  link.addEventListener('click', function (e) {
    e.preventDefault();
    var group = link.getAttribute('data-group');
    var panel = document.querySelector('#group-' + group + ' .accordion-collapse');
    if (panel) new bootstrap.Collapse(panel, { toggle: true }).show();
  });
  ```
- Rework the filter so it:
  - On input, toggles each `.setting-row` visibility by name/description.
  - For each panel, decides matched/empty by whether any row in it is visible.
  - Expands matched panels, collapses empty ones, but only while a query is
    present; on clearing, restores the previous open/closed state.
  - Shows the `#settingsEmpty` message when no panel has a match.

### 3. `application/views/common/_admin_sidebar.tpl` (no change)

The sidebar's Settings-Groups sub-links (`data-group`, `setting-group-link`)
already render when `$settings_groups` is set. They now target accordion
panels instead of tabs, so no template change is required.

### 4. Tests

- Unit: no model change expected (grouping/save untouched); the existing
  `SettingsTest` suite must remain green.
- E2E `tests/admin-settings.spec.ts`: update the selectors to the accordion
  (the vertical-tab-specific assertions — `#settingsTabs`, `#tab-general`,
  `#group-general` as a tab-pane — must be replaced with accordion equivalents).
  The spec should verify: accordion renders all groups, a group expands on
  click, the global search filters and auto-expands, and the sidebar
  group-link expands a panel. The E2E app runs against a Portuguese DB, so
  assertions must stay on DOM ids / setting names, not labels.

## Out of scope

- Changing the settings storage model, save path, or existing setting names.
- Changing the admin sidebar structure beyond the existing Settings-Groups
  links.
- Changing any other admin page.

## Testing

- Unit: full unit suite stays green (no model change).
- E2E: `tests/admin-settings.spec.ts` updated to accordion; runs against
  `http://localhost:8080` via `make serve-local-quiet`.
- Manual smoke: on the live page, verify (a) accordion renders all 6 groups,
  (b) clicking a header expands/collapses independently, (c) searching filters
  and auto-expands matched groups / collapses empty ones, (d) clearing the
  search restores the prior state, (e) the Save button persists a changed
  value, (f) the sidebar Settings-Groups link expands the matching panel.

## Non-goals

- Not introducing a new search paradigm (kept the existing filter).
- Not reworking the other admin pages.