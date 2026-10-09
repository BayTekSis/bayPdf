# Current state

- Current branch: `master`
- Current phase: Scoped assets
- Last completed unit: Generic server-side template/version scope isolation
- Current working state: Scope implementation and related tests pass; logical unit is ready to commit
- Acceptance criteria completed: 12 of 65; baseline criteria plus all generic template/version scope criteria
- Acceptance criteria remaining: See unchecked items in `ROADMAP.md`
- Known blockers: None. Browser tests have been inventoried but not run in this unit.
- Latest validation: Scope, TemplateManager, and designer API subset passed with 24 tests and 77 assertions after Pint passed.
- Expected next task: Scope asset storage prefixes, listing, reads, static/image-variable render paths, and legacy behavior.
- Expected next commit message: `Şablon kapsam izolasyonu eklendi`
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
