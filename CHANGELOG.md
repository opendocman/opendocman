# Changelog

## [2.7.0-release](https://github.com/opendocman/opendocman/compare/v2.6.0-release...v2.7.0-release) (2026-08-07)


### Features

* installer pre-flight checks and fix PDF parsing error ([#415](https://github.com/opendocman/opendocman/issues/415)) ([#418](https://github.com/opendocman/opendocman/issues/418)) ([622b328](https://github.com/opendocman/opendocman/commit/622b32814302da8110f9bee325250a447a13cc7e))
* permission inheritance design doc, implementation plan, and rename 'None' to 'Unset' across all language files ([69670af](https://github.com/opendocman/opendocman/commit/69670afac663ca82df26a217c0c287bc345ad85d))
* widen main content container to match navbar width ([#414](https://github.com/opendocman/opendocman/issues/414)) ([e9a9109](https://github.com/opendocman/opendocman/commit/e9a9109c897aaf57d60f8bcc7857b94853b007d9))


### Bug Fixes

* add cPanel PHP 8.3 handler to .htaccess for demo site compatibility ([5f7b38b](https://github.com/opendocman/opendocman/commit/5f7b38b91279dc033ce0b5993c96dc6fff3afa8a))
* correct revision history lifecycle for incoming staging ([#413](https://github.com/opendocman/opendocman/issues/413)) ([0c82042](https://github.com/opendocman/opendocman/commit/0c82042afa4dfb76bc72347ff7f6e3c56cb5762f))
* pass empty argv to migrate() in demoRefresh ([ce95837](https://github.com/opendocman/opendocman/commit/ce958376701cbf3b18c35063fc1c8c7bb9a8a2a7))
* redirect non-index pages to installer when DB has no tables ([#410](https://github.com/opendocman/opendocman/issues/410)) ([c22fa86](https://github.com/opendocman/opendocman/commit/c22fa86aa16725b75e730bdfc2d1b39424b18e44))
* restore missing popup() function for help links ([#417](https://github.com/opendocman/opendocman/issues/417)) ([2efe56e](https://github.com/opendocman/opendocman/commit/2efe56e01b735a4c8b17909b3a8ede264c69a78a))
* session_start guards, base_url trailing slash, CSRF validation, and E2E retry ([006cd22](https://github.com/opendocman/opendocman/commit/006cd22780cf8a72bd94df8b36a0e83142d70777))
* use getTokenForTemplate() instead of getToken() in content_index ([#411](https://github.com/opendocman/opendocman/issues/411)) ([d141137](https://github.com/opendocman/opendocman/commit/d141137c0c847aa7588c4984e4232e2fd861a7da))

## [2.6.0-release](https://github.com/opendocman/opendocman/compare/v2.5.0-release...v2.6.0-release) (2026-08-01)


### Features

* add custom_code.tpl include to bootstrap5 header for deploy-time snippets ([b639542](https://github.com/opendocman/opendocman/commit/b6395422ae7181b2fa5b15cae2d9eefd647f668e))
* add text/rtf as default filetype ([#407](https://github.com/opendocman/opendocman/issues/407)) ([53a9c23](https://github.com/opendocman/opendocman/commit/53a9c23f4ed15040f97d5953abf805847264399b))
* auto-discover migrations via glob() and add rollback support ([#408](https://github.com/opendocman/opendocman/issues/408)) ([22103b5](https://github.com/opendocman/opendocman/commit/22103b59ed5c66d6027a1472055e45182bfe6f02))

## [2.5.0-release](https://github.com/opendocman/opendocman/compare/v2.4.0-release...v2.5.0-release) (2026-07-31)


### Features

* add snapshot system for demo site refresh ([#400](https://github.com/opendocman/opendocman/issues/400)) ([#404](https://github.com/opendocman/opendocman/issues/404)) ([9b5edc4](https://github.com/opendocman/opendocman/commit/9b5edc41ccda001fdd03b9aa1e60a6f3e8a0a571))


### Bug Fixes

* add packages section to release-please-config.json for v4 compatibility ([717bb00](https://github.com/opendocman/opendocman/commit/717bb00e5ad176f4ab526ed575d65a0ac34c8a6b))
* use empty-string key in .release-please-manifest.json for root component ([0b4cc74](https://github.com/opendocman/opendocman/commit/0b4cc740d4fe91f7b016a7725d53b7ba911f8250))

## [2.4.0-release](https://github.com/opendocman/opendocman/compare/v2.3.0-release...v2.4.0-release) (2026-07-29)


### Features

* add file content search with full-text indexing ([#1](https://github.com/opendocman/opendocman/issues/1)) ([#396](https://github.com/opendocman/opendocman/issues/396)) ([72424bc](https://github.com/opendocman/opendocman/commit/72424bcdaa1cd5c177260d16f48c7e80934e5ce6))
* inline "add category" AJAX form on Add/Edit File pages ([#395](https://github.com/opendocman/opendocman/issues/395)) ([20049c3](https://github.com/opendocman/opendocman/commit/20049c386d6f3b3f738f6a3f9808d88f99abb778))
* require password change on first login after account creation ([#394](https://github.com/opendocman/opendocman/issues/394)) ([4f0994c](https://github.com/opendocman/opendocman/commit/4f0994c18ab372b5a6891458ea4661a05a611978))

## [2.3.0-release](https://github.com/opendocman/opendocman/compare/v2.2.0-release...v2.3.0-release) (2026-07-22)


### Features

* add _content.tpl and convert inline-PHP controllers to use it ([efa4bae](https://github.com/opendocman/opendocman/commit/efa4bae9f397cb527776621cc99681eb960d8cbc))
* add bootstrap5 login page template ([d8d54fa](https://github.com/opendocman/opendocman/commit/d8d54fa1874e015a550371786b8f86bd356683de))
* add Delete Selected toolbar to file table with row selection state ([e661ffa](https://github.com/opendocman/opendocman/commit/e661ffa119f63c7a52694a6fba1e5ce1fdbe9f01))
* add Playwright E2E smoke test suite for UAT workflows ([d4eaf89](https://github.com/opendocman/opendocman/commit/d4eaf89e1d5051a19e981a9e00fb09ee47fe5340))
* add Playwright E2E smoke test suite for UAT workflows ([1d94ad7](https://github.com/opendocman/opendocman/commit/1d94ad7734979fb01cc3e1c9e20a05b61ba49a4d))
* Bootstrap 5 modern UI ([#381](https://github.com/opendocman/opendocman/issues/381)) ([caa73d2](https://github.com/opendocman/opendocman/commit/caa73d26a146d1019d47e86b05755a1f6d809238))
* bootstrap5 modern UI - layout fixes, CSRF, Smarty literal, form grid, access_log conversion ([bf45993](https://github.com/opendocman/opendocman/commit/bf4599344961d17054e58a86ca29e3c7e9fa3004))
* create bootstrap5 theme chrome files (header, footer, head_include, CSS, JS) ([a592481](https://github.com/opendocman/opendocman/commit/a592481eca25b126944bef6e22c2b8a44045aaa5))
* persist Tabulator page size across session via sessionStorage ([54eff03](https://github.com/opendocman/opendocman/commit/54eff0322a5c5bf9b9415474ce887fe9a75a2cae))
* raise server-side page size cap from 100 to 1000, add 1000 to selector ([454ddea](https://github.com/opendocman/opendocman/commit/454ddeadf094a9cf72719a8f7bfaffbe45a1f820))
* replace DataTable with Tabulator in file listing (Task 4) ([ddc4a0e](https://github.com/opendocman/opendocman/commit/ddc4a0e9936df295cf189836b59bf66cee8022da))
* replace embedded JSON file table with server-side AJAX pagination ([ba087d4](https://github.com/opendocman/opendocman/commit/ba087d43570bfd9386dda6067a815f538db97401))
* replace server-side list_files N+1 queries with Tabulator AJAX pagination across all file views ([94c06e8](https://github.com/opendocman/opendocman/commit/94c06e86ffd129fda80713278cfcad7b44fc3468))
* set bootstrap5 as the default theme ([aa3b18b](https://github.com/opendocman/opendocman/commit/aa3b18bc116e178a8d5a5a68d7b5cf466b5785f0))
* update admin and settings templates for BS5 ([c517de3](https://github.com/opendocman/opendocman/commit/c517de3fa3ee3f437a0ae9fc1a056b9a69ad490f))
* update document workflow templates for BS5 ([f0737bd](https://github.com/opendocman/opendocman/commit/f0737bde2c36ba95472ab8390fdb1be91bfe57cd))
* update remaining templates for BS5, remove obsolete templates ([7e9f329](https://github.com/opendocman/opendocman/commit/7e9f3293d67e1a601bc48c9cda5c2c89bdb37327))


### Bug Fixes

* address code review issues — out.tpl rendering, Tabulator columns, search remnant, udf validation ([99ae429](https://github.com/opendocman/opendocman/commit/99ae42935169cd3105cb1f86855dff7f7c3178a5))
* bootstrap5 theme fixes — CSRF, UDF styling, language keys, JS errors, admin UI ([a4b5700](https://github.com/opendocman/opendocman/commit/a4b5700e78eddca1de02887f7668ee124c30cb17))
* handle missing source file in batch delete (archive) flow ([bc7dcbb](https://github.com/opendocman/opendocman/commit/bc7dcbb9b086915ca62c13a010504b5b44a19c8a))
* make search and admin pages responsive ([a4f6857](https://github.com/opendocman/opendocman/commit/a4f6857c978dc6046bd5fa8cca7a9cc9f22e66de))
* register Version001402 in migration runner lists ([2c28e07](https://github.com/opendocman/opendocman/commit/2c28e074c9f110feeb0fdf355a1347a6d8325d66))
* remove deprecated tweeter/default themes, add migration to set bootstrap5 ([8df8242](https://github.com/opendocman/opendocman/commit/8df82429567a6bc3dfeb474431759bc9b1727202))
* remove hardcoded DB password from seed_docs.php ([a0b53c1](https://github.com/opendocman/opendocman/commit/a0b53c17cc9e575711f04f3429ef5d23bf958b53))
* responsive layout fixes across all pages ([e8599d3](https://github.com/opendocman/opendocman/commit/e8599d300cc95e619106732b66e14c1f719ae801))
* restore 11 templates referenced by controllers (wrapped via _content.tpl) ([328c10c](https://github.com/opendocman/opendocman/commit/328c10cecce0b10d80553dde2e987e98c3736a47))
* use page_title var in _content.tpl (set by draw_header) ([7beed50](https://github.com/opendocman/opendocman/commit/7beed509c26b3b80a988e09053f03681d16ffa4b))
* use POST for batch delete with CSRF token locked to /delete ([5a11785](https://github.com/opendocman/opendocman/commit/5a117858779a1bc0a2703bf8ca9ff48faa0e47cb))

## [2.2.0-release](https://github.com/opendocman/opendocman/compare/v2.1.0-release...v2.2.0-release) (2026-07-15)


### Features

* store uploaded files as {id}/{original_filename} instead of {id}.dat ([70231af](https://github.com/opendocman/opendocman/commit/70231afc64d2c76cd9db20e124f65b78309523c6))


### Bug Fixes

* handle 'current' revision and raw id strings for PHP 8.2 type strictness ([3451d0b](https://github.com/opendocman/opendocman/commit/3451d0b3577fe4e793d8bcc7aec2c91fefbf2930))
* mark all migrations complete on fresh install to prevent old migrations running against prefixed tables ([3ab64ea](https://github.com/opendocman/opendocman/commit/3ab64ea44087b01c4dc1df1ec53e1eabb1ddec3d))

## [2.1.0-release](https://github.com/opendocman/opendocman/compare/2.0.2-release...v2.1.0-release) (2026-07-13)


### Features

* add Release Please automation for SemVer releases ([#360](https://github.com/opendocman/opendocman/issues/360)) ([a54e521](https://github.com/opendocman/opendocman/commit/a54e521ce0dc4b26cb5a2e7ffdaa0c38bed440b9))
* replace static mimetypes.php with league/mime-type-detection library (issue [#349](https://github.com/opendocman/opendocman/issues/349)) ([#364](https://github.com/opendocman/opendocman/issues/364)) ([1c85882](https://github.com/opendocman/opendocman/commit/1c85882e8ad40f87d76d442efcbb7c7afdd72943))
* unified installer & migration system, fix Docker/blank-page bugs ([#362](https://github.com/opendocman/opendocman/issues/362)) ([4dde2d2](https://github.com/opendocman/opendocman/commit/4dde2d2d047e3422f2d31bfbca6a0b880ef0a4f5))


### Bug Fixes

* breadcrumb links use REQUEST_URI instead of SCRIPT_NAME (fixes [#372](https://github.com/opendocman/opendocman/issues/372)) ([85c5bba](https://github.com/opendocman/opendocman/commit/85c5bbaf3dc39dd7afafba3a95e6b07a10a69715))
* breadcrumb links use REQUEST_URI instead of SCRIPT_NAME (fixes [#372](https://github.com/opendocman/opendocman/issues/372)) ([57d42db](https://github.com/opendocman/opendocman/commit/57d42db6f13604aceb6da3d6da11335c6ed8095d))
* correct form cancel buttons, multiselect dropdown, and required field indicators ([82c75df](https://github.com/opendocman/opendocman/commit/82c75df9619c03bc765180c71b0f7ab10b9d83fc))
* correct form cancel buttons, multiselect dropdown, and required field indicators (fixes [#368](https://github.com/opendocman/opendocman/issues/368)) ([5b94690](https://github.com/opendocman/opendocman/commit/5b9469078ec24654d165b3fd882ddb24f1cf4325))
* redirect deleted users to login page (fixes [#14](https://github.com/opendocman/opendocman/issues/14)) ([#367](https://github.com/opendocman/opendocman/issues/367)) ([840e0e1](https://github.com/opendocman/opendocman/commit/840e0e136590fa4ade538c3ac39320f2fb4ab63e))
* rename check_exp.php to check-exp.php to match URL routing (fixes [#369](https://github.com/opendocman/opendocman/issues/369)) ([eecb7cc](https://github.com/opendocman/opendocman/commit/eecb7cc4d932f07af7b05a095c5ceb36ae13f674))
* rename check_exp.php to check-exp.php to match URL routing (fixes [#369](https://github.com/opendocman/opendocman/issues/369)) ([3cd8883](https://github.com/opendocman/opendocman/commit/3cd8883c1742951a335401ffdc472093f083d06a))
* replace literal Smarty syntax with getTokenField() in access_log view (fixes [#370](https://github.com/opendocman/opendocman/issues/370)) ([79c886a](https://github.com/opendocman/opendocman/commit/79c886aba33df32ccf8d53ad59bed87cc8d59086))
* replace literal Smarty syntax with getTokenField() in access_log view (fixes [#370](https://github.com/opendocman/opendocman/issues/370)) ([cf66a38](https://github.com/opendocman/opendocman/commit/cf66a389b4d14e932c1c52940ba8042a66de2e4f))
* resolve JavaScript errors on add/edit document pages (fixes [#371](https://github.com/opendocman/opendocman/issues/371)) ([46c724f](https://github.com/opendocman/opendocman/commit/46c724f10994b9b42e61ed40509c1628ad1c7467))
* resolve JavaScript errors on add/edit document pages (fixes [#371](https://github.com/opendocman/opendocman/issues/371)) ([e0b0893](https://github.com/opendocman/opendocman/commit/e0b0893c87532de4e9462bf35795795ec7367edc))
* strip query string in getTokenField() and getToken() for CSRF consistency (fixes [#373](https://github.com/opendocman/opendocman/issues/373)) ([c2f4b69](https://github.com/opendocman/opendocman/commit/c2f4b69a6794bee8a6345565174c2af54c50571a))
* strip query string in getTokenField() and getToken() for CSRF consistency (fixes [#373](https://github.com/opendocman/opendocman/issues/373)) ([35a284a](https://github.com/opendocman/opendocman/commit/35a284a64900bb67fd9cb9d631f5fe4f7985f02f))
