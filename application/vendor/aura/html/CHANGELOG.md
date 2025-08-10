# CHANGELOG

## 2.6.0
- ADD: Nested HTML snippet/structure helper via #41 by @jakejohns
- ADD: Overriding helper factories via #55 by @jakejohns
- Fix: ctype_digit deprecation error on 8.1 via #62 by @arokettu
- Update continuous integration to support upto 8.1 via #60, #61 by @harikt

## 2.5.0

- ADD: VoidTag helper
- ADD: Element helper
- ADD: Support additional attributes on Scripts helper
- ADD: Support multi check boxes
- ADD: Support "Internal" Styles and Scripts
- DOC: Various documentation and phpDoc fixes.

## 2.4.1

This release modifies the testing structure and updates other support files.


## 2.4.0

This release fixes a bug in Checkbox/Radio helpers by adding a feature. Previously, the helpers used strict checking of values, which was an unintentional holdover from previous versions. They both now have a strict() method, just like the Select helper, that allows you to turn strict checking off and on. The default is "off."

It also includes a fix to the Escaper encoding lists: they were previously using "iso8859-" and now use "iso-8859-" (note the added dash, per the listing at http://php.net/manual/en/mbstring.supported-encodings.php).

Finally, the links, metas, scripts, styles, and title helpers now allow one-off use by passing values directly to the helper invocation. See the updated documentation for these helpers for more information.


## 2.3.0

This release has SECURITY FIXES. All users are encouraged to upgrade immediately.

- SEC: The AbstractChecked helper, which is the parent for Radio and Checkbox, now HTML-escapes the label. Previously, no escaping was applied.
- SEC: The Textarea helper now HTML-escapes the value. Previously, no escaping was applied.
- SEC: The Select helper now HTML-escapes each option label. Previously, no escaping was applied.
- FIX: The attributes for the Label helper now default to array() instead of null.


## 2.2.0

- FIX: Return empty string instead of null in AbstractList::__toString
- DOC: Branch alias 2.1 for the service config has been changed
- DOC: Fix typo in AttrEscaper::__invoke() doc comment
- DOC: Fix typo in HelperLocator::has() doc comment
- ADD: Add Helper/AnchorRaw for anchors w/ unescaped text; adds the helper, the factory to the locator, and the test.
- DOC: Various fixes to soothe PHPDocumentor.


## 2.1.1
- CHG: Disable auto-resolve for container tests and make config explicit.

## 2.1.0
- DOC: Updated docblocks and README files.
- CHG: Renamed services using new rules: "aura/html:escaper" and "aura/html:helper".
- TST: Add new container integration tests.


## 2.0.0
- DOC: Updated docblocks and README files.
- FIX #18
- FIX #17: Input helper now extends HelperLocator rather than composing it. This helps support easier DI configuration.
- CHG: Standardize all input helpers to return self.
- CHG: Allow Input::__invoke() to return the input locator object
- CHG: Allow Label::__invoke() to return the label object
- ADD: Class "Escaper" as a helper.
- FIX #6: In Select helper, add placeholder() and strict() methods, and no longer uses strict equality by default.

## 2.0.0-beta1

Extracted from Aura.View package.

