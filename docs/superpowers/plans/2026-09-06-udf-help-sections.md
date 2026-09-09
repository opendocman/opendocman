# UDF Help Sections by Field Type — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give UDF help links stable type-based anchors on both the Add and Edit File forms, fill `public/udf_help.html` with type-specific help sections, and clean up the edit-form function while touching it.

**Architecture:** A single small helper `udf_help_link($display_name, $field_type)` in `udf_functions.php` produces the popup link (or a plain-text fallback when `udf_help.html` is absent), used by both `udf_add_file_form()` and `udf_edit_file_form()`. The href target is `udf_help.html#Add_File_Type_<field_type>`. `public/udf_help.html` is rebuilt with four anchored sections matching those targets.

**Tech Stack:** PHP 8.4+, Aura Html Escaper (`e::h`), PHPUnit 9.6 + Mockery, Smarty templates.

## Global Constraints

- PHP floor is `>= 8.4` (composer.json) — no legacy syntax or deprecated constructs.
- ALL dynamic output must be escaped with `e::h()` (repo-wide convention). The existing un-escaped `$row[0]` in `udf_edit_file_form()` MUST be escaped as part of this work.
- Preserve the docroot-relative help-file check pattern introduced in the previous fix:
  `$docroot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : getcwd();`
- Do NOT change the database schema; do NOT touch admin UDF screens (`application/views/common/udf/*.tpl`); do NOT modify `public/help.html`.
- Follow TDD: write/update tests first, watch them fail, then implement.
- The E2E suite has 2 known pre-existing failures (smoke-uat category-delete, public-sharing) unrelated to this work — do not attempt to fix them here.
- Do not commit `application/templates_c/.gitignore` deletions (spurious; restore with `git restore application/templates_c/.gitignore` if present).

---

### Task 1: Add `udf_help_link()` helper and switch Add form to type-based anchors

**Files:**
- Modify: `application/controllers/helpers/udf_functions.php` (add helper before `udf_add_file_form()` at ~line 30; change link at lines 49–56)
- Test: `tests/Unit/UdfFunctionsTest.php`
- Modify (indirect): `public/udf_help.html` — no, Task 3

**Interfaces:**
- Produces: `string udf_help_link(string $display_name, int $field_type)` — returns an escaped HTML anchor `<a class="body" href="udf_help.html#Add_File_Type_<type>" onClick="return popup(this,'Help')" style="text-decoration:none">…display…</a>` when the docroot contains `udf_help.html`, else returns `e::h($display_name)`.

- [ ] **Step 1: Update the Add-form test to expect the type-based anchor**

In `tests/Unit/UdfFunctionsTest.php`, in `testUdfAddFileFormOutputsHelpLinkWhenHelpFileExistsInDocroot`, change the first assertion from the per-field anchor to the type-based anchor, and add a link-text assertion:

```php
    public function testUdfAddFileFormOutputsHelpLinkWhenHelpFileExistsInDocroot(): void
    {
        file_put_contents($this->tempDocroot . '/udf_help.html', '<html></html>');
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;

        $this->bindGlobalPdo($this->createMockUdfStatement());

        ob_start();
        udf_add_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('href="udf_help.html#Add_File_Type_3"', $output);
        $this->assertStringContainsString("onClick=\"return popup(this,'Help')\"", $output);
        $this->assertStringContainsString('>My Field</a>', $output);
    }
```

The mock statement already returns `[['myfield', 3, 'My Field']]` (table_name, field_type, display_name), so the field type is 3.

- [ ] **Step 2: Run the Add-form test to verify it fails**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UdfFunctionsTest`
Expected: `testUdfAddFileFormOutputsHelpLinkWhenHelpFileExistsInDocroot` FAILS with `Failed asserting that '...' contains "href="udf_help.html#Add_File_Type_3""` because the current code emits `#Add_File_My Field`. The plain-text test still passes.

- [ ] **Step 3: Add the `udf_help_link()` helper**

Insert immediately before `function udf_add_file_form()` in `application/controllers/helpers/udf_functions.php` (inside the `if (!defined('udf_functions'))` block, at file scope like the other functions):

```php
    function udf_help_link($display_name, $field_type)
    {
        $docroot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : getcwd();
        if (file_exists($docroot . '/udf_help.html')) {
            return '<a class="body" href="udf_help.html#Add_File_Type_' . (int) $field_type . '" onClick="return popup(this,\'Help\')" style="text-decoration:none">' . e::h($display_name) . '</a>';
        }
        return e::h($display_name);
    }
```

- [ ] **Step 4: Use the helper in `udf_add_file_form()`**

Replace the inline block (current lines 50–56):

```php
        foreach ($result as $row) {
            echo '<tr><td>';
            $docroot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : getcwd();
            if (file_exists($docroot . '/udf_help.html')) {
                echo '<a class="body" href="udf_help.html#Add_File_'. e::h($row[2]) .'" onClick="return popup(this,\'Help\')" style="text-decoration:none">'. e::h($row[2]) .'</a>';
            } else {
                echo e::h($row[2]);
            }

            echo '</td><td>';
```

with:

```php
        foreach ($result as $row) {
            echo '<tr><td>';
            echo udf_help_link($row[2], $row[1]);
            echo '</td><td>';
```

Note: in this function `$row[0]` = table_name, `$row[1]` = field_type, `$row[2]` = display_name.

- [ ] **Step 5: Run the Add-form test to verify it passes**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UdfFunctionsTest`
Expected: both tests in `UdfFunctionsTest` PASS.

- [ ] **Step 6: Commit**

```bash
git add application/controllers/helpers/udf_functions.php tests/Unit/UdfFunctionsTest.php
git commit -m "feat: point UDF help links to type-based anchors on the add form"
```

---

### Task 2: Add help links to the Edit form and clean up escaped output

**Files:**
- Modify: `application/controllers/helpers/udf_functions.php` (`udf_edit_file_form()`, starting ~line 210)
- Test: `tests/Unit/UdfFunctionsTest.php`

**Interfaces:**
- Consumes: `udf_help_link(string $display_name, int $field_type)` from Task 1.
- Note: in `udf_edit_file_form()`, the SELECT is `display_name, field_type, table_name`, so `$row[0]` = display_name, `$row[1]` = field_type, `$row[2]` = table_name — the argument order to `udf_help_link()` is therefore `($row[0], $row[1])`.

- [ ] **Step 1: Add the Edit-form tests**

Append two tests to `tests/Unit/UdfFunctionsTest.php`. Add `unset($_REQUEST['id']);` to `tearDown()`, then:

```php
    public function testUdfEditFileFormOutputsHelpLinkWhenHelpFileExistsInDocroot(): void
    {
        file_put_contents($this->tempDocroot . '/udf_help.html', '<html></html>');
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;
        $_REQUEST['id'] = 5;

        $stmtUdf = \Mockery::mock(\PDOStatement::class);
        $stmtUdf->shouldReceive('execute')->once()->with([])->andReturn(true);
        $stmtUdf->shouldReceive('fetchAll')->once()->andReturn([['My Field', 3, 'myfield']]);

        $stmtData = \Mockery::mock(\PDOStatement::class);
        $stmtData->shouldReceive('execute')->once()->with([':id' => 5])->andReturn(true);
        $stmtData->shouldReceive('fetch')->once()->andReturn(['current value']);

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtUdf, $stmtData);

        global $pdo;
        $pdo = $pdo;

        ob_start();
        udf_edit_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('href="udf_help.html#Add_File_Type_3"', $output);
        $this->assertStringContainsString('>My Field</a>', $output);
    }

    public function testUdfEditFileFormOutputsPlainTextWhenHelpFileMissing(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;
        $_REQUEST['id'] = 5;

        $stmtUdf = \Mockery::mock(\PDOStatement::class);
        $stmtUdf->shouldReceive('execute')->once()->with([])->andReturn(true);
        $stmtUdf->shouldReceive('fetchAll')->once()->andReturn([['My Field', 3, 'myfield']]);

        $stmtData = \Mockery::mock(\PDOStatement::class);
        $stmtData->shouldReceive('execute')->once()->with([':id' => 5])->andReturn(true);
        $stmtData->shouldReceive('fetch')->once()->andReturn(['current value']);

        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtUdf, $stmtData);

        global $pdo;
        $pdo = $pdo;

        ob_start();
        udf_edit_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('My Field', $output);
        $this->assertStringNotContainsString('popup(this', $output);
    }
```

The `tearDown()` change:

```php
    protected function tearDown(): void
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        unset($_REQUEST['id']);
        if (file_exists($this->tempDocroot . '/udf_help.html')) {
            unlink($this->tempDocroot . '/udf_help.html');
        }
        rmdir($this->tempDocroot);
        parent::tearDown();
    }
```

- [ ] **Step 2: Run the Edit-form tests to verify they fail**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UdfFunctionsTest`
Expected: both new Edit tests FAIL (`Failed asserting that '...' contains "href="udf_help.html#Add_File_Type_3""`), because the edit form currently emits the raw display name with no link. The Task-1 tests still pass.

- [ ] **Step 3: Switch the three edit-form label branches to the helper**

In `udf_edit_file_form()`:

Replace the type 1/2 branch label (line ~212):
```php
            if ($row[1] == 1 || $row[1] == 2) {
                echo '<tr><td>' . $row[0] . '</td><td>';
```
with:
```php
            if ($row[1] == 1 || $row[1] == 2) {
                echo '<tr><td>' . udf_help_link($row[0], $row[1]) . '</td><td>';
```

Replace the type 3 branch label (line ~262):
```php
            } elseif ($row[1] == 3) {
                echo '<tr><td>' . e::h($row[0]) . '</td><td>';
```
with:
```php
            } elseif ($row[1] == 3) {
                echo '<tr><td>' . udf_help_link($row[0], $row[1]) . '</td><td>';
```

Replace the type 4 branch label (line ~282):
```php
                echo '<tr><td>' . e::h($row[0]) . '</td><td>';
```
with:
```php
                echo '<tr><td>' . udf_help_link($row[0], $row[1]) . '</td><td>';
```

These three replacements escape the display name (fixing the previously un-escaped `$row[0]` in the type 1/2 branch) and add the help link. `$sel` and `$sel_pri` are used only in equality comparisons against DB values (`if ($sel == $sub_row[0])`) and are never output — no escaping change is needed for them.

- [ ] **Step 4: Remove the stray `//CHM` comments in the edit form**

Remove these two comment lines within `udf_edit_file_form()`:
- the `//CHM` line immediately above `elseif ($row[1] == 4) {` (currently line ~277)
- the `//CHM` line after the type-4 block closing `}` (currently line ~365)

Do NOT touch `//CHM` comments in other functions (`udf_add_file_form`, `udf_add_file_insert`, `udf_edit_file_update`, `udf_details_display`) — they are out of scope.

- [ ] **Step 5: Run Edit tests to verify they pass**

Run: `php application/vendor/bin/phpunit -c phpunit.xml.dist --filter UdfFunctionsTest`
Expected: all four tests in `UdfFunctionsTest` PASS.

- [ ] **Step 6: Run the full unit/integration suite**

Run: `make test-quiet`
Expected: no regressions (495 tests pass; the same 2 pre-existing E2E failures are NOT in this suite).

- [ ] **Step 7: Commit**

```bash
git add application/controllers/helpers/udf_functions.php tests/Unit/UdfFunctionsTest.php
git commit -m "feat: add type-based UDF help links to the edit form"
```

---

### Task 3: Rebuild `public/udf_help.html` with type-based sections

**Files:**
- Modify: `public/udf_help.html`

**Interfaces:**
- Produces: anchors `#Add_File_Type_1` .. `#Add_File_Type_4` matching the hrefs emitted by `udf_help_link()` (Tasks 1–2). Uses the same `<a name="…">` anchor convention as `public/help.html`.

- [ ] **Step 1: Replace the stub with four sections**

Rewrite `public/udf_help.html` with:

```html
<html>
<head>
<title>OpenDocMan - User Defined Fields</title>
</head>
<body>
<h1>OpenDocMan - User Defined Fields</h1>
<p>
User Defined Fields (UDFs) are extra fields that your administrator has added
to the Add/Edit File forms. Each field has a different purpose depending on its
type. The section matching the field you are using is shown below.
</p>

<b><a name="Add_File_Type_1"></a>Select List</b><br>
Select List fields let you pick a single value from a drop-down list. Click the
box and choose the value that best fits the file being added or edited. Only one
value can be selected.
<br><br>

<b><a name="Add_File_Type_2"></a>Radio</b><br>
Radio fields let you choose a single value by clicking one of several radio
buttons. Click the button next to the value you want. Only one value can be
selected at a time.
<br><br>

<b><a name="Add_File_Type_3"></a>Text</b><br>
Text fields accept a single line of free-form text. Type the information
directly into the box.
<br><br>

<b><a name="Add_File_Type_4"></a>Sub-Select</b><br>
Sub-Select fields let you make a two-level selection. First choose a value from
the primary drop-down list; the second (secondary) drop-down then shows the
values that belong to your primary choice. Select the appropriate secondary
value to finish.
<br><br>
</body>
</html>
```

- [ ] **Step 2: Verify the anchors are served**

Run: `curl -s http://localhost:8080/udf_help.html | grep -o 'name="Add_File_Type_[1-4]"' | sort -u`
Expected: all four anchors present. (The PHP dev server is already running on :8080.)

- [ ] **Step 3: Commit**

```bash
git add public/udf_help.html
git commit -m "feat: add type-based help sections to udf_help.html"
```

---

### Task 4: End-to-end verification

**Files:** none

- [ ] **Step 1: Run the full PHPUnit suite**

Run: `make test-quiet`
Expected: `OK (495 tests, …assertions)` — no errors.

- [ ] **Step 2: Lint the changed PHP files**

Run: `php application/vendor/bin/phplint application/controllers/helpers/udf_functions.php tests/Unit/UdfFunctionsTest.php`
Expected: `OK! (Files: 2, Success: 2)`.

- [ ] **Step 3: Manual browser check of the Add form**

The dev DB at :8080 has one UDF field: `odm_udftbl_asdf_primary` / display name `asda` / type 4.
- Log in with admin/admin at http://localhost:8080/
- Open http://localhost:8080/add
- Confirm the `asda` label renders as a link to `udf_help.html#Add_File_Type_4`
- Click it; a "Help" popup opens showing the **Sub-Select** section.

- [ ] **Step 4: Manual browser check of the Edit form**

The Edit form requires a file with UDF data. If no suitable file exists for id-based edit, create one via the Add form (its UDF value may be empty) then open `http://localhost:8080/edit?id=<file id>` and confirm the UDF label is a link opening the type-matched help section. If no file can be created, rely on the unit tests and note the manual step as skipped.

- [ ] **Step 5: Confirm git state is clean of spurious changes**

Run: `git status --short`
Expected: no `application/templates_c/.gitignore` deletion. If present, restore it (`git restore application/templates_c/.gitignore`) and do NOT commit it.