# UDF Help Sections by Field Type — Design

Date: 2026-09-06

## Problem

`udf_help.html` opens in a popup from UDF field labels on the Add File form, but the
file is a bare stub ("This sample file is provided...") with no sections and no
anchors, so the popup shows boilerplate rather than actual help. Additionally, the
Edit File form renders UDF labels as plain text with no help link at all.

The help target is currently `udf_help.html#Add_File_<display_name>`, but the display
name is free-form and admin-defined, so content organized around it cannot be
maintained.

## Goal

- Give UDF help links a stable, maintainable anchor scheme so each field jumps to a
  relevant help section.
- Render help links on **both** the Add File and Edit File forms.
- Fill `udf_help.html` with real, type-specific help content.
- Fix the un-escaped output and stray comments found while editing the edit-form
  function.

## Anchor scheme

Each UDF field links to `udf_help.html#Add_File_Type_<field_type>` based on its
stored `field_type`. Link text remains the field's `display_name`. Fields of the same
type share one help section.

| `field_type` | Meaning      | Anchor                |
|--------------|--------------|-----------------------|
| 1            | Select List  | `#Add_File_Type_1`    |
| 2            | Radio        | `#Add_File_Type_2`    |
| 3            | Text         | `#Add_File_Type_3`    |
| 4            | Sub-Select   | `#Add_File_Type_4`    |

## Help content

Rebuild `public/udf_help.html` with four anchored sections, each with a heading and a
short explanation matching actual field behavior:

- **Select List** — pick one value from a dropdown.
- **Radio** — choose one value via radio buttons.
- **Text** — free-form single-line text input.
- **Sub-Select** — hierarchical two-level pick (primary then secondary).

## Code changes

### `application/controllers/helpers/udf_functions.php`

**`udf_add_file_form()`** (line ~53): change the href from
`udf_help.html#Add_File_<display_name>` to `udf_help.html#Add_File_Type_<field_type>`.
Keep the `file_exists($docroot . '/udf_help.html')` guard and the `popup(this,'Help')`
handler. Link text stays `e::h($row[2])` (display name).

**`udf_edit_file_form()`** (starting ~line 210): add the same help-link pattern for
every type branch. Wrap the label `<td>` in the `popup(this,'Help')` link pointing at
`udf_help.html#Add_File_Type_<type>`, guarded by the same `file_exists` check with a
plain-text fallback.

**Cleanup** in the edit function:
- Escape previously un-escaped output in labels and option values:
  - `$row[0]` (display name) in the type 1/2 and type 4 branches.
  - `$sel` / `$sel_pri` interpolated into option `selected`/`checked` comparisons and
    values.
- Remove the stray `//CHM` comments.

### `public/udf_help.html`

Replace the stub with the four type sections described above. This file lives in the
docroot (moved there in the prior fix) and is served at `/udf_help.html`.

## Tests

Update `tests/Unit/UdfFunctionsTest.php` (TDD — test change written first, watched
fail):

- **Add form**: for a type-3 field, assert the rendered link points to
  `udf_help.html#Add_File_Type_3` and uses the display name as link text.
- **Edit form**: mock `$_REQUEST['id']` and the extra queries; assert the same
  type-based anchor is emitted.
- **Edit form, help file absent**: assert no `popup(` is emitted and the display name
  renders as plain text.

No test asserts the static content of `udf_help.html`; anchors are asserted via the
link hrefs.

## Out of scope

- No DB schema changes.
- No changes to the admin UDF management screens (`views/common/udf/*.tpl`).
- `public/help.html` (the main help file) is untouched.

## Verification

- `make test` green (all PHPUnit suites).
- Manual browser check: on `/add` and an edit page with a UDF field, the label is a
  link that opens the appropriate type section in a popup; with the help file absent,
  the label renders as plain text.