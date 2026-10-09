# Architecture decisions

## Stable boundaries

1. BayPdf remains business-domain agnostic. No consumer, finance, tax, accounting, or e-invoice model is part of package runtime code.
2. The host owns business authorization and supplies all render data. BayPdf does not fetch host models.
3. Scope identifiers are opaque strings whose meaning belongs to the host.
4. Browser input never selects the authoritative scope; it is resolved server-side.
5. Scoping remains opt-in so current shared installations keep their behavior. Enabled scoping fails closed when no scope resolves.
6. Published versions remain immutable and continue to carry their variable schema snapshot.
7. Collection input is bounded and limited to records of supported scalar fields. Nested collections are excluded from the first implementation.
8. Legacy fixed-page layout remains supported as its own contract. Multi-page capabilities use an explicit schema version rather than changing old JSON semantics.
9. Finance, tax, e-invoice, delivery, accounting, and retention logic stays outside BayPdf.
10. tFPDF remains the renderer unless a tested prototype proves a concrete blocker. An engine change requires a separate product-owner decision.

## Verification boundary

The implemented defaults are recorded below and in `config/baypdf.php`. Passing local checks establishes integration readiness, not a release: the final commit still needs remote CI, a separately selected version, and the user's tag/release handoff.

## Implemented scope decisions

- Public contract: `BayPdf\Contracts\ScopeResolver::resolve(): ?string`.
- Default binding returns null and `scoping.enabled=false` preserves the shared query behavior.
- Enabled mode requires a non-empty opaque identifier of at most 191 bytes without control characters.
- `baypdf_templates.scope_key` is nullable and indexed. Existing rows remain null.
- TemplateVersion carries no duplicate scope column; queries derive ownership through `template_id`.
- Scoped route binding and TemplateManager return not-found behavior for foreign template/version IDs.
- Scope predicates preserve the stored opaque key and enforce byte equality independently of database collation. SQLite and isolated MySQL 8.4 verification include case, accent and trailing-space differences. Other driver predicates remain runtime-unverified in this session.
- Version reassignment is prohibited for every version, preventing ownership changes through the relation.
- Legacy ownership adoption is an explicit host migration over verified template IDs; automatic assignment is forbidden.

## Implemented asset decisions

- Scoped assets use `asset_prefix/scopes/<sha256(scope)>`; opaque scope values are not exposed in storage keys.
- No asset registry table is added. Prefix-bounded storage listing supplies the current catalog.
- Static image keys are checked during document validation; image variable keys are checked during rendering.
- Legacy adoption copies validated bytes to a current-scope mirror and preserves the old JSON key.
- Adoption never deletes the original and may be repeated only for scopes whose ownership is verified by the host.
- BayPdf exposes no deletion or garbage collector. Hosts must consider every draft and immutable published version reference before external cleanup.

## Implemented collection decisions

- Collection variables extend the existing DocumentTypes registry and TemplateVersion `variables` snapshot; no parallel schema store is introduced.
- Rows are list entries containing flat associative records. Supported fields are text, date, number, and money.
- Missing optional collections resolve to an empty list. Required collections require at least one row.
- Per-document-type collection/field limits and per-render row/payload/field-byte limits are configurable.
- The global row limit counts all registered collections in one render; a collection `max_rows` may only lower that cap.
- Validation errors expose field paths and reasons, never submitted values.
- Nesting is fixed at one collection of scalar records. HTML-like strings are plain text.

## Implemented layout and pagination decisions

- Legacy JSON remains schema v1 by omission. Flow features require explicit `schema_version: 2` and no automatic rewrite occurs.
- V2 has absolute page elements plus one primary collection table and a bounded trailing element list.
- Flow uses separate first/continuation top boundaries and one bottom boundary; headers and footers stay outside that usable body.
- Pagination is planned from tFPDF font metrics before drawing. Total pages come from the plan and page context renders in one PDF pass.
- Rows never split. Wrapped columns share the maximum measured row height; an oversized row fails with its path and no value.
- Table header repetition is explicit. Page elements support first/all/continuation/last repeat rules.
- Trailing blocks follow the true table end and move whole to a new page when needed.
- Hidden trailing blocks take no space in the pagination plan. Bound text elements require scalar text-compatible variables; collections cannot reach text drawing.
- Defaults cap output at 100 pages and 200 layout elements. Existing asset, QR, text and collection limits continue to apply.
- tFPDF creation timestamp metadata prevents a byte-for-byte determinism guarantee across different seconds; pagination and document structure are deterministic.
