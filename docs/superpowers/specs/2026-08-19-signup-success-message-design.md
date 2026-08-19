# Design — Fix misleading signup success message

## Problem

The sign-up success output is misleading and unstyled. In `signup.php` (mysql auth),
on successful registration the app echoes `msg('message_account_created')` =
*"Your account has been created. Please check your email for login information."*
immediately followed by the temp password shown on-screen. The message is wrong
on two counts:

1. `signup.php` **never sends an email** (0 `mail()` calls), so there is no email
   to check. `message_account_created` is used only by `signup.php` (verified via
   grep — the admin-add-user flow uses `message_account_created_add_user` instead).
2. The output is raw `echo` text before `exit` (mysql path), so the page has no
   styling. For non-mysql (ldap) auth, the message echoes then falls through and
   re-renders the whole sign-up form below it.

There is no account/email-confirmation process, so the fix is to remove the
"check your email" copy and style the success page.

## Decisions

1. **No confirmation process exists** — do not build one. Remove the misleading
   email text.
2. **Message copy change** in all 17 language files: `message_account_created`
   becomes *"Your account has been created."* (strip the email phrase from each
   translation).
3. **Styled success page** for both auth paths using the app's existing
   `draw_header()` + Smarty `_content.tpl` pattern, replacing the raw echo.

## Components

### 1. Message copy (17 language files)

`application/includes/language/*.php`: change `$lang['message_account_created']`
so it no longer references checking email, in all 17 files. English becomes:
`'Your account has been created.'`

### 2. Styled success page (`application/controllers/signup.php`)

Replace the submit-success output with a rendered page:
- `draw_header(msg('label_signup_success'), $last_message)` (or similar)
- A Bootstrap success card containing:
  - **"Your account has been created."**
  - Username
  - Temp password in a `<code>` box (mysql auth only — existing behavior, styled)
  - **Login** button linking to `base_url`
- Render via `ob_start()` / `ob_get_clean()` / `$GLOBALS['smarty']->assign('content', …)` /
  `display_smarty_template('_content.tpl')` / `draw_footer()`, matching how the
  sign-up form page itself renders.
- Applies to both the mysql path (with password) and the non-mysql path (without
  password, and no longer falls through to re-render the sign-up form).

## Out of scope

- Building an account-confirmation/email-verification workflow.
- Changing where the temp password is generated or sent.
- The admin-add-user email flow (`message_account_created_add_user`), which
  already sends an email and is accurate.

## Testing

- Unit: sign-up success path renders the styled content (Smarty content assigned)
  with the account-created message and, for mysql, the temp password; the
  non-mysql path does not re-render the form and shows no password.
- Grep check: `message_account_created` no longer contains "email" in any of the
  17 language files.
