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
