# Design — Modernized admin area: grouped, searchable settings + dashboard (ODM #436)

## Problem

GH issue opendocman/opendocman#436 (open, `enhancement`). The site **Settings**
page renders every row of the `settings` table as one flat, unordered list
(`application/views/common/settings.tpl`). As ODM has grown the list has gotten
long and hard to navigate — an admin must scan the entire page to find and
change a single setting. The **admin dashboard** (`application/controllers/admin.php`)
is a card grid whose links duplicate what a sidebar would provide and gives no
quick path to a specific admin function.

Scope (agreed with user): modernize both the **admin dashboard** and the
**settings page**, kept as separate pages, wrapped in a **shared sidebar shell**
that also appears on the other admin sub-pages (users, departments, categories,
filetypes, reports). Only the way settings are *presented* changes — the flat
storage model and the save path are untouched.

## Goal

Give admins a single navigable admin area: a **sidebar shell** that groups all
admin functions, a **search box** that filters them, and a **settings page**
whose settings are grouped by concern in **vertical tabs** with a **global
search** across all groups. Keep auto-discovery so new/unknown settings always
appear.

## Decisions

1. **Code-driven group map** (no schema change). A PHP map in
   `Settings.class.php` maps every setting name → group key. Any setting not in
   the map falls into an **Other** group, preserving auto-discovery.
2. **Sidebar shell wraps all admin pages.** Each admin controller switches from
   `display_smarty_template('_content.tpl')` to a new `_admin_content.tpl` that
   renders the sidebar + content. Active page is highlighted via a per-controller
   `$active_admin` var.
3. **Vertical tabbed settings.** Groups run down the left as vertical tabs; the
   active group's settings fill the right panel. Save/cancel buttons live in a
   sticky footer bar shared across all groups (edits survive tab switches).
4. **Global settings search.** One box at the top of the settings content area
   filters across **all** groups at once; a query hides non-matching settings
   within each group (final decision: tab-bound filter, matches remain in
   their group's tab — see Section 4).
   (each tagged with its group); clearing the query restores tab view.
5. **Admin search box** in the sidebar filters all admin links live; Enter jumps
   to the first match.
6. **Group names translatable; setting names/descriptions as-is.** Group headers
   use new `$lang['settings_group_*']` keys added to all 17 language files.
   Setting name/description continue to come from the `settings` table.
7. **Sticky sub-nav inside groups is dropped** in favor of vertical tabs (user
   approved vertical tabs over stacked/sticky-nav, tabbed, and accordion).
8. **`mail_*` settings** (issue #17) map into Email & Notifications once that
   branch merges — no further code needed beyond the map gaining those names.

## Components

### 1. Settings group map (`application/models/Settings.class.php`)

```php
private const GROUP_MAP = [
    'debug'   => 'general',
    'demo'    => 'general',
    'title'   => 'general',
    'max_query' => 'general',
    'authen'  => 'security',
    'root_id' => 'security',
    'allow_signup' => 'security',
    'default_signup_department' => 'security',
    'allow_password_reset' => 'security',
    'try_nis' => 'security',
    'dataDir' => 'storage',
    'snapshotDir' => 'storage',
    'max_filesize' => 'storage',
    'site_mail' => 'email',
    // mail_* (issue #17) land here
    'authorization' => 'review',
    'revision_expiration' => 'review',
    'file_expired_action' => 'review',
    'theme' => 'appearance',
    'language' => 'appearance',
];
```

- Group order is a fixed array; unknown keys sort into `other` at the end.
- Group display strings from `$lang['settings_group_general']`, etc.
- `edit()` gains a method that groups the query result and assigns
  `$settings_groups` (e.g. `['general' => ['name'=>'general','label'=>...,'settings'=>[...]], ...]`).
- The `edit()` method continues to assign `themes`, `languages`, `useridnums`,
  `departments`, `settings_array` for backward compatibility with any partial
  template reuse.

### 2. Sidebar shell (`_admin_content.tpl`, `_admin_sidebar.tpl`)

Sidebar groups all admin functions:

```
Search admin…            (live filter)
Dashboard
Content Management       Users · Departments · Categories · UDFs
Files                    Delete/Undelete · Reviews · Rejects · Check Expiration · Checked-out Files
Settings & System        Settings · File Types · Search Index
Reports                  Access Log · File List
About / Plugins          version info + callPluginMethod('onAdminMenu')
```

- Sidebar is **active-page aware**: each admin controller sets
  `$active_admin` (e.g. `'settings'`, `'users'`); the sidebar highlights the
  matching item and expands its group.
- Bootstrap `offcanvas` on small screens; fixed column on `lg`+.
- On the settings page the sidebar also lists the settings groups
  (General, Security & Auth, Storage & Files, Email & Notifications, Review,
  UI & Appearance, Other) as sub-links that deep-link to that vertical tab.
- Controllers switching to `_admin_content.tpl`: `admin`, `admin_users`,
  `admin_departments`, `admin_categories`, `filetypes`, `settings`, `udf`,
  `access_log`, `file_list_report`, `content_index`.
- Plugins hook (`callPluginMethod('onAdminMenu')`) renders into the sidebar's
  plugin section.

### 3. Admin dashboard (landing view)

The dashboard becomes the sidebar's landing view. Old card grid is replaced by:

- Welcome heading.
- **Quick stats** with live counts: users, departments, categories, documents.
- **Quick actions**: add user, add department, edit settings, public page
  (when enabled).
- **Version info** (app + DB) retained in the content area.

Dashboard search: the sidebar search doubles as navigation here.

### 4. Settings page (grouped vertical tabs + global search)

Layout reuses the verified mockup (vertical tabs):

- Left rail: vertical tabs, one per group, with a count badge
  (e.g. `General · 4`). Active tab highlighted.
- Right panel: the active group's settings, each rendering **label + inline
  description** under the label.
- One form wraps all tabs (same save path as today: POST to `settings`,
  CSRF, dataDir/snapshotDir trailing-slash cleanup, numeric validation,
  `last_message`).
- **Sticky footer bar** with Save / Cancel buttons (shared across tabs).
- Global search box above the tab rail filters matching settings across **all**
  groups (hidden groups' matching rows are shown in their own tab; user
  switches tabs to view them — **final decision, 2026-08-22: tab-bound
  filter**, not a flat merged result list). "No settings match &ldquo;X&rdquo;"
  empty state when no group contains a match.
- Per-setting control renderer (unchanged semantics):
  - `bool` → True/False select
  - `theme` → theme dropdown, `language` → language dropdown
  - `file_expired_action` → 4-option select
  - `authen` → MySQL-only select
  - `root_id` → users dropdown
  - `default_signup_department` → departments dropdown
  - else → text input

### 5. i18n

New strings (group headers, sidebar group labels, dashboard labels) added to
**all 17 language files** under `application/includes/language/`.

## Out of scope

- Changing how settings are stored (only presentation changes).
- Changing the settings save path or existing setting names.
- Rebuilding the admin CRUD pages (users/departments/categories) — they are
  wrapped by the shell but not redesigned.
- Full translation of setting names/descriptions (they remain as stored in DB).

## Testing

- Unit: `Settings::groupSettings()` groups known names correctly, falls back to
  `other` for unknown names, preserves group order.
- Integration: `settings` page renders all groups and every existing setting is
  still editable; save path unchanged.
- E2E: navigate admin → settings; search filters; every existing setting still
  edits + saves; sidebar search filters links; new/unknown setting renders
  under Other.