# Current state

- Current branch: `master`
- Current phase: Collection schema and data
- Last completed unit: Scoped asset storage, catalog, reads, references, and legacy adoption
- Current working state: Scoped asset implementation and related tests pass; logical unit is ready to commit
- Acceptance criteria completed: 19 of 65; baseline, template/version scope, and scoped asset criteria
- Acceptance criteria remaining: See unchecked items in `ROADMAP.md`
- Known blockers: None. Browser tests have been inventoried but not run in this unit.
- Latest validation: Pint passed; asset scope, rendering, designer API, and template scope subset passed with 40 tests and 105 assertions.
- Expected next task: Bounded collection registration, schema snapshots, example rows, data validation, and scalar field formatting.
- Expected next commit message: `Varlık kapsam güvenliği güçlendirildi`
- Uncommitted user changes: None at task start.
- Recommended next model/reasoning: GPT-5.6 Sol, high reasoning, because public API, persistence, rendering, and security boundaries are changing.

## Verified baseline

- HEAD at audit: `65a671e45e53e4dceba47a3d4218e666b8f446c3`
- Database: `baypdf_templates`, `baypdf_versions`
- Layout: unversioned fixed page JSON with absolute `elements`
- Variable types: text, date, number, money, image, qr
- Element types: text, variable, image, qr, line, rectangle
- Page formats: A4, A5, Letter; portrait and landscape
- Scope: shared; no tenant isolation
- Assets: private-disk prefix validation, no scope ownership
- Versions: schema snapshot, `lock_version`, immutable after publish
- Pagination, collections, headers, footers, page numbers, and flow: not implemented
