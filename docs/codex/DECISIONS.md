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

## Pending implementation decisions

- Layout schema v2 region and flow shape.
- Maximum defaults after renderer tests establish practical deterministic bounds.

## Implemented scope decisions

- Public contract: `BayPdf\Contracts\ScopeResolver::resolve(): ?string`.
- Default binding returns null and `scoping.enabled=false` preserves the shared query behavior.
- Enabled mode requires a non-empty opaque identifier of at most 191 characters without control characters.
- `baypdf_templates.scope_key` is nullable and indexed. Existing rows remain null.
- TemplateVersion carries no duplicate scope column; queries derive ownership through `template_id`.
- Scoped route binding and TemplateManager return not-found behavior for foreign template/version IDs.
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
