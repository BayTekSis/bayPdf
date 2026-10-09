# Worklog

## 2026-10-09 — Baseline and continuity

- Completed unit: Repository truth audit, pre-implementation snapshot, and continuity system
- Main files/domains: Repository configuration, public API, models, renderer, designer API/UI, tests, CI, package docs
- Validation: `composer test` — 34 tests/80 assertions PASS; `npm.cmd test` — 2 tests PASS; `npx.cmd playwright test --list` — 4 scenarios inventoried
- Next unit: Generic server-side scope isolation and additive template scope persistence

## 2026-10-09 — Generic template/version scope isolation

- Completed unit: Opaque server-side scope contract, additive template scope persistence, scoped lifecycle queries, scoped route binding, and explicit legacy-data strategy
- Main files/domains: Scope contract/context, service provider, template/version models, TemplateManager, designer API, config, migration, public/security/install docs
- Validation: Pint PASS; targeted scope, TemplateManager, and designer API suite — 24 tests/77 assertions PASS
- Next unit: Scoped asset storage, access, references, and lifecycle documentation

## 2026-10-09 — Scoped assets

- Completed unit: Fingerprinted scoped upload paths, scoped catalog/read enforcement, static and variable image reference checks, and explicit non-destructive legacy adoption
- Main files/domains: Assets service, document validation, designer asset API route, scope/security/install/template architecture docs
- Validation: Pint PASS; targeted asset scope, rendering, designer API, and scope suite — 40 tests/105 assertions PASS
- Next unit: Bounded collection schema, example rows, snapshot behavior, and data validation

## 2026-10-09 — Bounded collection data

- Completed unit: Collection registration/schema, scalar row validation and formatting, configurable resource bounds, examples, and TemplateVersion snapshot/clone behavior
- Main files/domains: DocumentTypes, VariableResolver, DocumentValidator, config limits, variable/security/architecture/public API docs
- Validation: Pint PASS; targeted collection, rendering, TemplateManager, and designer API suite — 41 tests/96 assertions PASS
- Next unit: Layout schema v2, generic collection table, deterministic row measurement, and automatic pagination

## 2026-10-09 — Multi-page collection renderer

- Completed unit: Layout schema v2, measured collection table, automatic continuation pages, repeatable page elements/table header, Page X / Y context, and trailing flow blocks
- Main files/domains: DocumentValidator, PdfRenderer, Canvas, internal TextLayout/FlowPaginator, renderer/config/security/template/architecture docs
- Validation: Pint PASS; latest multi-page lifecycle + legacy rendering + TemplateManager subset — 35 tests/70 assertions PASS
- Next unit: Progressive designer UX, frontend save-payload tests, workbench commercial-document example, and browser coverage

## 2026-10-09 — Progressive multi-page designer

- Completed unit: Progressive collection-table tools, source/column/style controls, page repeat/context settings, trailing content authoring, scoped asset reuse, and generic workbench example
- Main files/domains: Vue designer, layout helpers, translations/CSS, compiled package assets, frontend tests, Playwright scenarios, workbench registration
- Validation: Pint PASS; targeted backend 41 tests/132 assertions PASS; frontend 5 tests PASS; Vite production build PASS; Playwright 6 scenarios PASS
- Next unit: Legacy compatibility matrix, representative PDF visual inspection, full QA/distribution/security review, and final report

## 2026-10-09 — Compatibility and final verification

- Completed unit: All legacy element/variable/page combinations, opt-in PDF evidence, browser width-validation and real Blob page checks, refreshed package/upgrade/continuity documents and self-contained BayDesk integration report.
- Review fixes: Independent Codex findings were reproduced before changes; scope comparisons now enforce exact bytes, hidden trailing blocks do not paginate, and text cannot bind collections. Existing scope keys and published JSON were preserved. Two further designer regressions preserve column alignment and fit trailing/footer content after page-size changes.
- Validation: Main `composer qa` — 73 tests/259 assertions, Pint, PHPStan and distribution rules PASS; frontend 7, Vite build and browser 6 PASS; shipped assets rebuilt; Boost 35-file no-drift PASS. Dependency variants, PDF visuals and archive/runtime checks are detailed in `docs/VERIFICATION.md`.
- Database evidence: Separate disposable MySQL 8.4.3, case-insensitive collation — 9 scope tests/38 assertions PASS. The server was shut down; no host database was used.
- Next unit: Remote CI on the final commit and a separately selected release handoff; existing 0.1.0 tag is unchanged.
