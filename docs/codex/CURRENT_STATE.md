# Current state

- Current branch: `master`
- Current phase: Baseline and continuity
- Last completed unit: Repository truth audit and pre-implementation snapshot
- Current working state: Continuity documents are being introduced; implementation has not started
- Acceptance criteria completed: Baseline repository/config/runtime audit, clean-tree confirmation, current backend test baseline, current frontend unit baseline
- Acceptance criteria remaining: See unchecked items in `ROADMAP.md`
- Known blockers: None. Browser tests have been inventoried but not run in this unit.
- Latest validation: `composer test` passed with 34 tests and 80 assertions; `npm.cmd test` passed with 2 tests; Playwright lists 4 scenarios.
- Expected next task: Implement and test the generic server-side scope contract and additive template scope migration.
- Expected next commit message: `Codex süreklilik sistemi oluşturuldu`
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
