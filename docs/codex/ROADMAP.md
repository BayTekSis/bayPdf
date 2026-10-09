# BayPdf Finance readiness roadmap

Progress is calculated only from the objective checklist below. A box is checked after the behavior is implemented and its relevant verification passes.

## A. Baseline and backward compatibility

- [x] Repository instructions, manifests, implementation, tests, CI, and documentation are audited.
- [x] Branch, HEAD, clean-tree state, and commit-signing configuration are recorded before edits.
- [x] Current PHP/Laravel support and installed development runtime are recorded.
- [x] Current backend, frontend, and browser test inventories are recorded.
- [ ] Legacy layout fixtures cover every existing element, variable type, page format, and orientation.
- [ ] Legacy fixed-page documents render without implicit flow or schema conversion.

## B. Generic scope isolation

- [x] A minimal public server-side scope resolver contract is documented and bound by the package.
- [x] Scoping disabled preserves the shared installation behavior.
- [x] Scoping enabled without a resolved scope fails closed.
- [x] Templates store an additive nullable opaque scope key with a safe index.
- [x] Template list/create/read operations are restricted to the resolved scope.
- [x] Save, publish, clone, preview, version reads, and safe programmatic rendering reject cross-scope IDs.
- [x] Route binding returns unavailable/404 behavior for cross-scope template and version IDs.
- [x] Legacy unscoped records remain deterministic and are never silently assigned to a tenant scope.

## C. Scoped assets

- [ ] New uploads use a deterministic current-scope storage prefix.
- [ ] Asset list/read operations expose only current-scope assets.
- [ ] Cross-scope asset keys are rejected even when a key is known.
- [ ] Static template images and image variables enforce current-scope ownership.
- [ ] Traversal, remote URL, stream wrapper, byte, pixel, and type protections remain enforced.
- [ ] Legacy unscoped asset migration behavior is explicit and non-destructive.
- [ ] Deletion/reference limitations for draft and published version assets are documented.

## D. Collection schema and data

- [ ] Document types accept bounded collection definitions containing scalar record fields.
- [ ] Collection fields support text, date, number, and money only.
- [ ] Nested collections, objects, closures, HTML, and arbitrary nested row data are rejected.
- [ ] Configurable collection, field, row, string-byte, nesting, and payload limits are enforced.
- [ ] Empty, one-row, many-row, malformed-row, unknown-field, and missing-required-field cases are tested.
- [ ] Collection number, money, date, and long-text formatting are tested.
- [ ] Collection schemas are preserved in TemplateVersion snapshots and clones.
- [ ] Bounded example rows drive designer preview without production data.

## E. Collection table designer

- [ ] A generic `collection_table` element is defined without business semantics.
- [ ] Source collection and field mappings are selectable in the designer.
- [ ] Columns support labels, deterministic widths, alignment, padding, borders, header style, and row style.
- [ ] Invalid or overflowing column widths fail with an actionable validation error.
- [ ] Table settings use labelled keyboard-accessible controls and preserve existing keyboard positioning.
- [ ] Advanced/data controls use progressive disclosure and small viewports remain usable.
- [ ] Frontend tests cover source selection, columns, repeat settings, validation, and save payload.

## F. Multi-page renderer

- [ ] A versioned layout schema adds multi-page behavior without changing legacy schema meaning.
- [ ] Collection tables paginate by measured available vertical space.
- [ ] Zero rows, one row, exact fit, one-row overflow, 50+ rows, and mixed wrapped rows are tested.
- [ ] Wrapped cells share a deterministic row height across columns.
- [ ] A row taller than the usable page fails once with a value-free actionable error.
- [ ] Table headers can repeat on continuation pages.
- [ ] Maximum generated page and element limits prevent page explosion.
- [ ] Same version, data, and configuration produce deterministic document structure.
- [ ] Representative first, continuation, and final PDF pages receive visual inspection evidence.

## G. Headers, footers, and page context

- [ ] Page regions support bounded first/all/continuation/last visibility rules.
- [ ] Static and variable elements render safely in page headers and footers.
- [ ] Built-in current-page and total-page context renders without host registration.
- [ ] Total page count uses deterministic layout/rendering rather than byte placeholder replacement.
- [ ] Repeated header/footer and Page X / Y behavior is covered by renderer tests.

## H. Flowing trailing content

- [ ] A bounded generic flow abstraction positions content after the primary collection table.
- [ ] Trailing content uses remaining final-page space without overlap or clipping.
- [ ] Trailing content moves deterministically to a new page when it does not fit.
- [ ] The supported multiple-collection/flow limit is enforced and documented.

## I. Browser and security verification

- [ ] Playwright verifies the legacy single-page authoring lifecycle.
- [ ] Playwright verifies scoped template isolation.
- [ ] Playwright verifies a collection table that previews across multiple pages.
- [ ] Playwright verifies repeated page content, page numbers, and trailing flow state.
- [ ] Security tests cover cross-scope templates, versions, assets, image variables, guessed IDs, malformed collections, and resource exhaustion limits.

## J. Documentation, distribution, and release readiness

- [ ] README, public API, security, template, variable, architecture, and upgrade documentation match code.
- [ ] `yapilacaklar/` and continuity state are synchronized after every logical unit.
- [ ] Compiled designer assets are current and included while development-only files remain excluded.
- [ ] Fresh backend QA, frontend tests/build, workbench preparation, browser tests, and distribution checks pass.
- [ ] Final security and backward-compatibility reviews are recorded.
- [ ] The self-contained Finance readiness report includes BayDesk integration guidance and an evidence-based release verdict.

## Progress

- Completed: 12
- Total: 65
- Progress: 18.5%
