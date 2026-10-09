# Current state

- Current branch: `master`
- Current phase: Collection table and multi-page renderer
- Last completed unit: Bounded collection registration, examples, snapshot, validation, and formatting
- Current working state: Collection data contract and related tests pass; logical unit is ready to commit
- Acceptance criteria completed: 27 of 65; baseline, scoping, scoped assets, and collection data criteria
- Acceptance criteria remaining: See unchecked items in `ROADMAP.md`
- Known blockers: None. Browser tests have been inventoried but not run in this unit.
- Latest validation: Pint passed; collection, rendering, TemplateManager, and designer API subset passed with 41 tests and 96 assertions.
- Expected next task: Define layout schema v2 and implement the generic collection table with deterministic measurement and pagination.
- Expected next commit message: `Koleksiyon değişkenleri eklendi`
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
