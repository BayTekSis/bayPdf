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

- Scoped asset prefix encoding and listing behavior.
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
