# BayPdf Finance Readiness Report

Date: 2026-10-09. Scope: the Finance readiness work after `65a671e`, including the final compatibility and verification changes. The existing `0.1.0` tag remains unchanged.

## Verdict

**Ready for a controlled BayDesk integration evaluation. Not yet ready for a tagged production release.** Local QA, browser, visual PDF and runtime distribution checks passed. The final commit still requires remote CI and a separately selected release version. Packagist registration and private vulnerability reporting remain external steps in the project records; their current remote state was not verified here.

## Delivered capabilities

| Area | Current contract and evidence |
|---|---|
| Scope | Opt-in server-side `ScopeResolver`; nullable template `scope_key`; versions derive ownership from templates. `ScopeIsolationTest` covers browser scope tampering, foreign IDs, missing scope and legacy visibility. |
| Assets | Private fingerprint paths, current-scope catalog/read checks, static/image-variable ownership checks and explicit non-destructive legacy adoption. `AssetScopeTest` covers these boundaries. |
| Data | Flat scalar collection records with text/date/number/money fields; bounded rows, fields, bytes and payload; schema/examples remain in immutable version snapshots. `CollectionDataTest` covers formatting, malformed input, limits and cloning. |
| Layout | Explicit schema v2; one measured collection table, continuation pages, repeated page/table headers, current/total page context and trailing blocks. `MultiPageRenderingTest` covers empty/exact/overflow/wrapped/oversized/page-limit cases. |
| Designer | Progressive collection controls, column mapping/width/alignment/style, repeat rules, page numbers, trailing content and scoped asset reuse. Five frontend tests and six browser scenarios cover authoring and preview. |
| Compatibility | Unversioned legacy JSON stays single-page. `LegacyCompatibilityTest` covers all six legacy element and variable types, three formats, both orientations, formatted text, preview/publish/render/clone and unchanged schema meaning. |

## Fresh verification

`composer qa` passed on PHP 8.4.21 / Laravel 13.35.0: strict manifest, Pint (35 files), PHPStan with zero errors, PHPUnit **73 tests / 259 assertions**, and distribution rules. `npm.cmd test` passed all seven tests; Vite build passed and refreshed the shipped designer assets. Workbench preparation and all six Chromium scenarios passed.

The same current source and test suite passed against existing isolated dependency installations:

| PHP | Laravel | Dependency selection | Result |
|---|---|---|---|
| 8.4.21 | 12.69.3 | stable | 72 tests / 255 assertions |
| 8.4.21 | 12.69.0 | lowest | 72 tests / 255 assertions |
| 8.4.21 | 13.30.0 | lowest | 72 tests / 255 assertions |
| 8.3.30 | 12.69.0 | lowest | 72 tests / 255 assertions |
| 8.3.30 | 13.30.0 | lowest | 72 tests / 255 assertions |

These checks reused installed dependency sets and loaded the current package source/tests through an isolated bootstrap; they did not re-resolve the full CI matrix. Old Symfony Translation dependencies emitted a PHP 8.4 deprecation notice in lowest runs. It was not suppressed or patched.

The scope suite also passed on a disposable MySQL 8.4.3 server under the BayPdf artifacts directory: **9 tests / 38 assertions** with `utf8mb4_unicode_ci`. Case, accent and trailing-space variants could not list or access another scope's templates/versions. The server was shut down after the check; no host database was used.

The 100-row synthetic PDF produced four A4 pages. Independent PyMuPDF raster inspection of pages 1, 2 and 4 showed aligned cells, separated headers/footers, Page X / 4 and the summary below the final row without clipping. Evidence is local under `.artifacts/finance-verified-page-{1,2,4}.png`; generate the PDF using the opt-in command in `docs/DEVELOPMENT.md`.

The Composer ZIP contained 42 entries, excluded development/private files, and matched current runtime files byte-for-byte. A fresh extracted installation passed `composer install --no-dev --no-scripts`, platform, autoload, required assets/migrations and absence-of-Testbench checks. Boost sync reported no drift in 35 files.

## Security and compatibility review

The in-session source review traced designer routes through host authentication and `AuthorizeDesigner`, template/version binding and lifecycle operations through `ScopeContext`, image keys through private-prefix and scope checks, and collection/layout input through validation and resource limits. Tests exercise authorization, CSRF, cross-scope IDs/assets, malformed rows, traversal/remote images, immutable versions and exhaustion bounds. This pass shares the implementation session's context; it is not a penetration test.

Independent Codex review found three defects: database collation could merge distinct scope keys, hidden trailing blocks consumed pagination space, and bound text could accept a collection. All three first failed regression tests and were fixed. Exact scope predicates preserve stored keys while rejecting collation-equivalent neighbors; hidden blocks reserve no space; text bindings require scalar-compatible schema. A second review reproduced column alignment resets and out-of-bounds trailing/footer content after shrinking the page; both were fixed with frontend regression tests. Composer and npm dependency audits found no reported vulnerabilities. The browser test also checks the actual downloadable PDF Blob and invalid column-width feedback.

Public changes are additive: shared mode stays the default, existing scalar definitions remain valid, and legacy JSON is not converted implicitly. Enabling scope changes visibility intentionally and requires verified ownership migration. Direct Eloquent/SQL access can bypass the service boundary. Published asset references require host-managed retention; BayPdf does not provide deletion or garbage collection.

## BayDesk integration

1. Install a verified version/commit, run package migrations and publish compiled assets. Add new config keys without overwriting host settings.
2. Keep the designer disabled until host authentication and the `manage-baypdf` Gate are connected. Bind `ScopeResolver` to authenticated server-side context; never accept authoritative scope from browser input. Resolve that context explicitly for queued jobs too.
3. Before enabling scope on existing data, verify template ownership by ID and call `Assets::adoptLegacy` under each authorized scope. Back up first; do not rewrite immutable published JSON or delete originals.
4. Register document types, collections and synthetic examples in the host provider. Create a new template for a changed variable schema; cloning retains the original snapshot.
5. Supply authorized scalar records and host-calculated totals to `TemplateManager::render` with a published version. The schema v2 example in `docs/TEMPLATES.md` supplies the generic table/header/footer/summary layout.
6. Exercise host-specific authorization, storage, queue context, long descriptions, empty/max-row inputs and final-page content before deployment.

BayDesk owns business models, calculations, permissions, compliance decisions, delivery and retention. BayPdf has no finance/tax/accounting/e-invoice engine. It renders host-supplied values and does not fetch host records.

## Limits and release handoff

Schema v2 supports one primary flowing collection; rows and trailing blocks move whole and cannot split. Defaults allow 500 collection rows, 20 fields per collection, 100 generated pages and 200 layout elements. Headers/footers must stay outside the flow area. Absolute page elements can overlap intentionally; the host must position them appropriately.

Pagination/document structure is deterministic for equal layout/data/config; PDF bytes can differ because tFPDF writes a creation timestamp. Complex-script shaping, signatures, HTML/PDF import and automatic asset cleanup remain outside this implementation. PostgreSQL/SQL Server runtime, database concurrency and load testing were not verified. MySQL verification covers scope isolation, not the entire suite. Visual evidence covers the representative A4 fixture, not every font/content combination. Remote CI on the final commit must pass before any tag handoff; the user selects and publishes the next release.

Final discriminator regression: invalid explicit schema versions previously discarded flow content through legacy dispatch; they now fail validation. This final fix passed local QA but was not independently re-reviewed after the three-round review limit. Dependency-variant results above precede this last fix.
