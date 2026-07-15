## [2.1.0-release](https://github.com/opendocman/opendocman/compare/2.0.2-release...v2.1.0-release) (2026-07-13)


### Features

* add Release Please automation for SemVer releases ([#360](https://github.com/opendocman/opendocman/issues/360)) ([a54e521](https://github.com/opendocman/opendocman/commit/a54e521ce0dc4b26cb5a2e7ffdaa0c38bed440b9))
* replace static mimetypes.php with league/mime-type-detection library (issue [#349](https://github.com/opendocman/opendocman/issues/349)) ([#364](https://github.com/opendocman/opendocman/issues/364)) ([1c85882](https://github.com/opendocman/opendocman/commit/1c85882e8ad40f87d76d442efcbb7c7afdd72943))
* unified installer & migration system, fix Docker/blank-page bugs ([#362](https://github.com/opendocman/opendocman/issues/362)) ([4dde2d2](https://github.com/opendocman/opendocman/commit/4dde2d2d047e3422f2d31bfbca6a0b880ef0a4f5))


### Bug Fixes

* breadcrumb links use REQUEST_URI instead of SCRIPT_NAME (fixes [#372](https://github.com/opendocman/opendocman/issues/372)) ([85c5bba](https://github.com/opendocman/opendocman/commit/85c5bbaf3dc39dd7afafba3a95e6b07a10a69715))
* correct form cancel buttons, multiselect dropdown, and required field indicators (fixes [#368](https://github.com/opendocman/opendocman/issues/368)) ([5b94690](https://github.com/opendocman/opendocman/commit/5b9469078ec24654d165b3fd882ddb24f1cf4325))
* redirect deleted users to login page (fixes [#14](https://github.com/opendocman/opendocman/issues/14)) ([#367](https://github.com/opendocman/opendocman/issues/367)) ([840e0e1](https://github.com/opendocman/opendocman/commit/840e0e136590fa4ade538c3ac39320f2fb4ab63e))
* rename check_exp.php to check-exp.php to match URL routing (fixes [#369](https://github.com/opendocman/opendocman/issues/369)) ([eecb7cc](https://github.com/opendocman/opendocman/commit/eecb7cc4d932f07af7b05a095c5ceb36ae13f674))
* replace literal Smarty syntax with getTokenField() in access_log view (fixes [#370](https://github.com/opendocman/opendocman/issues/370)) ([79c886a](https://github.com/opendocman/opendocman/commit/79c886aba33df32ccf8d53ad59bed87cc8d59086))
* resolve JavaScript errors on add/edit document pages (fixes [#371](https://github.com/opendocman/opendocman/issues/371)) ([46c724f](https://github.com/opendocman/opendocman/commit/46c724f10994b9b42e61ed40509c1628ad1c7467))
* strip query string in getTokenField() and getToken() for CSRF consistency (fixes [#373](https://github.com/opendocman/opendocman/issues/373)) ([c2f4b69](https://github.com/opendocman/opendocman/commit/c2f4b69a6794bee8a6345565174c2af54c50571a))
