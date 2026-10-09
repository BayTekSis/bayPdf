# BayPdf project context

BayPdf is a PHP 8.3+ Composer package for Laravel 12 and 13. It provides versioned document templates, a visual layout designer, host-supplied dynamic data rendering, private image assets, and PDF generation. It is a package rather than a Laravel application; tests boot a kernel through Orchestra Testbench.

## Responsibility boundary

BayPdf owns generic document layout, template/version lifecycle, schema snapshots, scoped template and asset access, bounded data validation, and deterministic PDF rendering. The host owns business models, authorization, tenant semantics, business calculations, legal compliance, accounting, retention, delivery, and selection of data and published template versions.

The package must not encode consumer-specific models or concepts. Scope identifiers are opaque strings. Collection names and fields are registered by the host. Finance, tax, e-invoice, and accounting semantics remain outside the package.

## Runtime and distribution

- PHP: `^8.3`
- Laravel components: `^12.0 || ^13.0`
- PDF engine: tFPDF through the package's internal Canvas
- Package tests: PHPUnit and Orchestra Testbench
- Designer: Vue source compiled with Vite
- Consumer runtime: compiled assets ship in the Composer archive; Node/npm are development-only

## Public API principles

Public services provide the supported path for template lifecycle and rendering. Published versions remain immutable. Variable schemas are copied into each template version. Direct Eloquent or SQL access can bypass service-layer guarantees and is not the recommended integration path.

New public contracts must be minimal, explicit, stable, and independent of host domains. Internal renderer/layout helpers stay internal. Database and JSON changes are additive or versioned so existing templates continue to render.

## Security principles

The designer stays disabled by default and remains protected by host authentication plus the configured Gate. When scoping is enabled, the authoritative scope is resolved server-side; browser input never selects it. Cross-scope identifiers must behave as unavailable. Assets stay on a private disk, remote URLs and stream wrappers are rejected, and render data is not logged.

## Current objective

The active objective is Finance readiness through generic capabilities: opaque scope isolation, scoped assets, bounded collection records, a collection-backed table, automatic multi-page rendering, repeated headers and footers, page numbering, and bounded trailing flow. Existing fixed single-page documents must remain compatible.
