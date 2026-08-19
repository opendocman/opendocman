# Design — Admin-configurable default department for new registrants (ODM #332)

## Problem

GH issue opendocman/opendocman#332 (still open, `security🔒`, milestone 2.1). The
sign-up form (`application/controllers/signup.php:166-188`) renders a department
`<select>` listing **all** departments, and it is fully selectable. A
self-registering user can therefore put themselves into any department (e.g. a
restricted "HR" department) and immediately inherit that department's document
permissions. There is currently no way to restrict or default the department a
new registrant is placed into.

This is inconsistent with the profile-edit path, where department changes are
only applied when the acting user is an admin
(`application/controllers/user.php:276-280`). Sign-up is the one unguarded entry
point.

A separate, expected behavior note: placing a user in a department does *not*
grant them access to that department's documents unless those documents have an
explicit `dept_perms` grant for the department (or a category template). This is
by design and is not changed here.

## Goal

Allow an administrator to configure the department that new self-registered
users are auto-assigned to, and give admins a way to see users who have not yet
been assigned a department (so they know a new registration needs attention).

## Decisions

1. **Single global setting** `default_signup_department` (a department dropdown
   on the Settings page, plus an "— unassigned —" option), mirroring the
   existing `allow_signup` setting.
2. **Auto-assign on sign-up**: new users are placed into the configured default
   department. If none is configured, they are created with `department = NULL`.
3. **Empty-default behavior**: when no default is configured, the new user is
   created with a `NULL` department and sees nothing until an admin assigns one.
4. **Admin visibility**: an admin-only link on `/out` (mirroring the
   "Documents waiting to be reviewed: N" pattern at `out.php:53-55`) shows the
   count of users with an unassigned department and links to the filtered
   user-management list.

## Components

### 1. New global setting `default_signup_department`

- New `settings` row added via a new migration `Version*.php` (auto-discovered
  by `MigrationLoader`), plus seeding in `application/installer/SchemaBuilder.php`.
- Settings page (`application/controllers/settings.php`) renders a department
  dropdown for this setting, with an "— unassigned —" (empty) option.
- Bump `ODM_DB_VERSION` in `application/version.php`; run `make dump-sql` to
  regenerate `database.sql`.

### 2. Sign-up flow (`application/controllers/signup.php`)

- Remove the department `<select>` (lines 166-188) from the form.
- On insert, set `department` to the configured default department ID; if the
  setting is empty/unset, set it to `NULL`.
- Ignore any client-supplied `department` field (no self-selection).

### 3. Null-department safety (permissions layer)

- `User_Perms::__construct()` (`application/models/User_Perms.class.php:75-78`)
  currently throws when the user's department is empty. Relax this guard so an
  empty department yields "no dept grants" (an empty listing) instead of
  throwing.
- Verify `UserPermission::getViewableFileIds()` and the `/out` listing return an
  empty list (or only per-file/category grants) for a null-department user
  rather than erroring. `Dept_Perms::loadData_UserPerm` already returns no rows
  for a null/0 department id, so the main change is the constructor guard.

### 4. Admin visibility on `/out` (`application/controllers/out.php`)

- Mirror the reviews pattern (`out.php:53-55`): admin-only, count of users with
  `department IS NULL`, shown only when the count is greater than zero:
  `"ⓘ Unassigned users: N"` linking to `admin_users?filter=unassigned`.

### 5. Filter in admin user management

- `application/controllers/admin_users.php` and
  `application/controllers/admin_crud_ajax.php:58` accept a `filter=unassigned`
  parameter that appends `AND u.department IS NULL` to the user-list query.
- Each row keeps its existing per-user edit link so the admin can assign a
  department.

### 6. Migration + i18n

- New migration `Version*.php` inserting the setting (and its default), bump
  `ODM_DB_VERSION`, update `SchemaBuilder.php`, regenerate `database.sql`.
- New translation strings (`label_unassigned_users`, plus any setting label)
  added to **all 17 language files** under `application/includes/language/`.

## Out of scope

- Changing the document→department permission model (department membership does
  not grant access to that department's documents; `dept_perms`/category
  templates govern that).
- Changing profile-edit department behavior (already admin-gated).
- LDAP-driven department assignment.

## Testing

- Unit: a user with a `NULL` department can construct a `User_Perms` /
  `UserPermission` and gets an empty viewable list (no exception).
- Integration: sign-up inserts the configured default department; empty setting
  inserts `NULL`; client-supplied `department` is ignored.
- E2E (where feasible): admin sees the "Unassigned users" link with a count that
  links to the filtered list.
