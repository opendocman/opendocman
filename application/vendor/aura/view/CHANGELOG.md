# CHANGELOG

## 2.4.0

* Added namespace support to TemplateRegistry by @jakejohns https://github.com/auraphp/Aura.View/pull/83.
* Updated CI to support php versions from 5.4 to 8.1 by @harikt

## 2.3.0

* Added ability to set map and path in ViewFactory.  https://github.com/auraphp/Aura.View/pull/73
* Removed CHANGES.md file.
* Added CHANGELOG.md

## 2.2.1

* Added ability to customize template extension. Thank you Josh Butts.

* Added documentation how to customize template extension.

## 2.2.0

* Added ability to customize template extension. Thank you Josh Butts.

* Added documentation how to customize template extension.

## 2.1.1

This release modifies the testing structure and updates other support files.


## 2.1.0

This release has one feature addition, in addition to doucmentation and support file updates.

Per @harikt, we have brought back the "finder" functionality from Aura.View v1. This means the TemplateRegistry can now search through directory paths to find templates implicitly, in addition to the existing explicitly registered templates. (Explicit mappings take precedence over search paths.)

Thanks also to @iansltx for his HHVM-related testing work.

## 2.0.1

- TST: Update testing structure, and disable  auto-resolve for container tests

- DOC: Update README and docblocks

- FIX: TemplateRegistry map now passes the array via set to make the file
  inside a closure

## 2.0.0

First stable 2.0 release.

- DOC: Update docblocks and README.

- CHG: View::render() now takes a second param, $data, for an array of vars to be extract()ed into the template scope. Closure-based templates will need to extract this on their own. (The previous technique of placing partial vars in the main template object still works.)

## 2.0.0-beta2

- [BRK] Stop using a "content variable" and begin using setContent()/getContent() instead.  In your layouts, replace `echo $this->content_var_name` with `echo $this->getContent()`. (This also removes the `setContentVar()` and `getContentVar()` methods.)

- [ADD] Add support for sections per strong desire from @harikt, which fixes #46.  The new methods are `setSection()`, `hasSection()`, and `getSection()`, along with `beginSection()` and `endSection()`.

## 2.0.0-beta1

First 2.0.0-beta1 release.

## 1.2.2

Hygiene release.

- Merge pull request #52 from harikt/v2config; adds configuration for v2 framework

- Merge pull request #50 from koriym/fix-typos; fixes some doc typos.

- Merge pull request #45 from harikt/label-issue; fix label issue pointed out in groups by guillaume ferrand and poiting to wrong docs

- Merge pull request #44 from jelofson/helpers; various helper updates:

    - Changed docblock to correct type for 'checked'

    - Fixed the ordering of the styles helper to match that of the scripts helper

    - Updated some tests

    - Added fluency to some helpers

    - Added some documentation for helpers

    - Fluent method in the Links helper and updated test.

## 1.2.1

- [FIX] TwoStepView::getView() now optionally returns the first view when
  there is no format specified; this stops exceptions from being raised when
  the client passes no Accept header and there are multiple view formats
  available.

## 1.2.0

- [TST] Add PHP 5.5 to the Travis build.

- [CHG] Escaper\Object now recursively escapes arrays instead of converting to
  ArrayObject and wrapping in an escaper

- [ADD] TwoStepView::getTemplate() to get the template out of the view

- [NEW] Helper\Form\Checkboxes

## 1.1.2

(updated to include the InstanceTest)

- [FIX] Correct the instance.php script and its associated scripts, and add
  an InstanceTest for it. Thanks, HariKT, for reporting this.

## 1.1.1

- [FIX] Correct the instance.php script and its associated scripts, and add
  an InstanceTest for it. Thanks, HariKT, for reporting this.

## 1.1.0

- [NEW] Form helpers: field, input, radios, repeat, select, and textarea.

- [NEW] List helpers: ul and ol.

- [NEW] Generic tag helper.

- [ADD] AbstractHelper methods indent(), setIndentLevel(), and void().

- [ADD] Method addCond() to the styles helper, to add a conditional style.

- [CHG] Disallow easy changing of quotes and charset via constructor; always
  go with ENT_QUOTES and UTF-8

- [CHG] Registry entries *must* be wrapped in a callable from now on

## 1.0.0

- [FIX] In scripts/instance.php, pass an EscaperFactory.

- [FIX] #15: https://github.com/auraphp/Aura.View/issues/15

- [CHG] TemplateFinder now uses is_readable() instead of SplFileObject, to
  help with testing using streams.

- [CHG] Renamed protected method TemplateFinder::fileExists() to exists(),
  because streams may not be files proper.
