# Current state

- Current branch: `master`
- Current phase: Backward compatibility, visual PDF, documentation, and release verification
- Last completed unit: Progressive schema-v2 designer, scoped asset catalog, workbench example, and browser scenarios
- Current working state: Designer source and compiled assets support collections, repeat rules, page numbers, and trailing content; logical unit is ready to commit
- Acceptance criteria completed: 56 of 65; implementation and browser/security behavior are complete
- Acceptance criteria remaining: See unchecked items in `ROADMAP.md`
- Known blockers: None.
- Latest validation: Pint PASS; targeted backend 41 tests/132 assertions PASS; frontend 5 tests PASS; production build PASS; Playwright 6 scenarios PASS.
- Expected next task: Complete legacy compatibility fixtures, visual PDF inspection, distribution/security review, and final report.
- Expected next commit message: `Dinamik tablo tasarımcısı eklendi`
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
