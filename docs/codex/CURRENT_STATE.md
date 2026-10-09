# Current state

- Current branch: `master`
- Current phase: Designer support for schema v2
- Last completed unit: Generic collection table, automatic multi-page renderer, page repeat/context, and trailing flow
- Current working state: Backend layout v2 and renderer tests pass; logical unit is ready to commit
- Acceptance criteria completed: 47 of 65; backend scope, assets, collections, pagination, page context, and trailing flow are complete
- Acceptance criteria remaining: See unchecked items in `ROADMAP.md`
- Known blockers: None. Browser tests have been inventoried but not run in this unit.
- Latest validation: Pint passed; multi-page lifecycle, legacy rendering, and TemplateManager subset passed with 35 tests and 70 assertions.
- Expected next task: Add progressive designer controls, frontend tests, workbench sample data, and browser scenarios for layout v2.
- Expected next commit message: `Çok sayfalı tablo üretimi eklendi`
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
