# Signup Success Message Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the sign-up success output accurate and styled — remove the misleading "check your email" copy and render a proper success page.

**Architecture:** Change the `message_account_created` language string in all 17 files to drop the email phrase. In `signup.php`, replace the raw `echo ...; exit;` success block (mysql path) and the fall-through (non-mysql path) with a rendered Bootstrap success page via the app's existing `draw_header()` + Smarty `_content.tpl` pattern.

**Tech Stack:** PHP, PDO, Smarty templates, Bootstrap 5, PHPUnit + Mockery.

## Global Constraints

- `message_account_created` is used only by `signup.php` (verified). Changing its value affects only signup.
- New/changed `$lang[...]` entries must be updated in **all 17** language files under `application/includes/language/` (arabic, bangla, chinese, croatian, czech, danish, dutch, english, french, german, italian, portuguese, romanian, spanish, swedish, tamil, turkish).
- The sign-up form page renders via `draw_header(msg('signup'), $last_message)` → `ob_start()` → ... → `ob_get_clean()` → `$GLOBALS['smarty']->assign('content', $content)` → `display_smarty_template('_content.tpl')` → `draw_footer()`. Reuse this pattern.
- `msg('login')`, `e::h()`, `msg()` are available; `$GLOBALS['CONFIG']['base_url']` is the login target.
- phpunit binary is `application/vendor/phpunit/phpunit/phpunit` (no `bin/phpunit`).

---

### Task 1: Styled signup success page + message copy

**Files:**
- Modify: `application/controllers/signup.php` (success output)
- Modify: `application/includes/language/*.php` (17 files, `message_account_created` value)
- Test: `tests/Unit/SignupControllerTest.php` (extend existing)

**Interfaces:**
- Consumes: existing signup submit path (`$_POST['adduser']`), `PasswordHasher::hash()`, `msg()`, `e::h()`.
- Produces: on successful submit, `signup.php` renders a styled success page (via Smarty `_content.tpl`) with the account-created message; the mysql path shows the username + temp password in a `<code>` box + a Login button; the non-mysql path shows the message without a password and does not re-render the form.

- [ ] **Step 1: Write/extend the failing test**

Extend `tests/Unit/SignupControllerTest.php`. Add a test that, after a successful mysql-auth submit, the captured Smarty `content` (assigned to `$GLOBALS['smarty']`) contains the account-created message and the temp password, and that the raw echo path is gone. Use the existing test's stubbing conventions (the file already stubs `Smarty`, `Escaper`, `crumb`, and `$GLOBALS['CONFIG']`). Replace the current assert with one that captures the `content` passed to `$GLOBALS['smarty']->assign('content', …)` and asserts it contains `message_account_created` output and the password.

- [ ] **Step 2: Run test to verify it fails**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter SignupControllerTest 2>&1 | tail -20`
Expected: FAIL — current code does not assign styled content this way.

- [ ] **Step 3: Implement the styled success page in `signup.php`**

Replace the submit-success block (currently `echo msg('message_account_created') …` and the mysql `echo … exit;`) with a rendered page:

```php
// Account created successfully — show a styled confirmation.
draw_header(msg('label_signup_success'), $last_message);
ob_start();
?>
<div class="d-flex justify-content-center">
    <div class="card w-100" style="max-width: 28rem;">
        <div class="card-header bg-success text-white"><h5 class="card-title mb-0"><?php echo msg('message_account_created'); ?></h5></div>
        <div class="card-body">
            <p><?php echo msg('username') . ': ' . e::h($_POST['username']); ?></p>
            <?php if ($GLOBALS['CONFIG']['authen'] == 'mysql') { ?>
                <p><?php echo msg('message_account_created_password'); ?>: <code><?php echo e::h($_REQUEST['password']); ?></code></p>
            <?php } ?>
            <a class="btn btn-primary" href="<?php echo e::h($GLOBALS['CONFIG']['base_url']); ?>"><?php echo msg('login'); ?></a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$GLOBALS['smarty']->assign('content', $content);
display_smarty_template('_content.tpl');
draw_footer();
exit;
```

Ensure this replaces BOTH the mysql path's `echo …; exit;` AND the non-mysql fall-through (which currently echoes then falls into the form-render path). After this block, execution should always `exit` for a successful submit regardless of auth mode, so the non-mysql path no longer re-renders the form. If `label_signup_success` does not exist, add it (or reuse an existing suitable label) — if adding a new key, add it to all 17 language files too.

- [ ] **Step 4: Update `message_account_created` in all 17 language files**

Change `$lang['message_account_created']` in each of the 17 files so the English value is `'Your account has been created.'` and each translation drops any "check your email" phrase. For files that currently translate the email phrase, use a translation meaning just "Your account has been created." (English fallback acceptable where a clean translation isn't obvious).

- [ ] **Step 5: Run tests to verify they pass**

Run: `php application/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist --filter SignupControllerTest 2>&1 | tail -20`
Expected: PASS. Also run `make test-unit 2>&1 | tail -8` (all pass).

- [ ] **Step 6: Verify no "email" remains in `message_account_created`**

Run: `grep -rn "message_account_created'\]" application/includes/language/*.php | grep -i email`
Expected: no output.

- [ ] **Step 7: Commit**

```bash
git add application/controllers/signup.php application/includes/language/*.php tests/Unit/SignupControllerTest.php
git commit -m "fix: accurate, styled signup success message (no misleading email notice)"
```

---

## Self-Review

- **Spec coverage:** message copy in 17 files (Task 1) ✅; styled success page for both auth paths (Task 1) ✅; non-mysql no longer re-renders form (Task 1) ✅.
- **Placeholder scan:** none; concrete code provided.
- **Type/name consistency:** `message_account_created`, `label_signup_success` (if added) consistent across task and spec.
