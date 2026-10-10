# Current state

- Current branch: `master`
- Current phase: Local BayDesk consumer contract hardening; release remains a separate handoff.
- Last completed unit: Scope-aware public template/version reads, clone lock refresh, and route-aware designer base. Git remains authoritative for commit state.
- Last implementation commit before closeout: `a0cf920` — progressive designer, scoped asset catalog, workbench example and browser scenarios.
- Closeout work: Legacy matrix, opt-in visual PDF fixture, stronger browser assertions, exact-byte scope filtering, hidden-trailing pagination and scalar text-binding regressions. Use `git status` for the actual commit state.
- Acceptance status: `ROADMAP.md` is authoritative; completion refers to local package work, not publication.
- Release blockers: Remote CI on the final commit, next version selection and user tag/release handoff. Packagist/private reporting remain external steps from project records.
- Latest validation: isolated Sail/Testbench consumer snapshot `composer qa` PASS — 78 tests/276 assertions, Pint 36 files, PHPStan zero errors and distribution policy valid. The first distribution attempt lacked the repository's `.lpv` in the detached archive; copying `.lpv` and `.gitattributes` read-only into QA restored the configured check. BayDesk Sail/PostgreSQL immutable-commit consumer: 12 tests/147 assertions PASS. Remote CI remains unverified; frontend/browser checks were not rerun for this unit.
- Additional evidence: Isolated MySQL 8.4.3 case-insensitive scope suite — 9 tests/38 assertions PASS; representative first/continuation/final PDF pages visually inspected; archive/runtime checks and dependency variants are recorded in `VERIFICATION.md` and the Finance readiness report.
- Expected next task: Make the local commit remotely reachable with authorization, verify remote CI, then prepare the selected release handoff. Do not move the existing `0.1.0` tag.
- Session entry working tree: Modified `tests/MultiPageRenderingTest.php` and untracked `tests/LegacyCompatibilityTest.php`; both were preserved and completed.
- Recommended next model/reasoning: GPT-5.6 Sol, high reasoning, because public API, persistence, rendering, and security boundaries are changing.

## Historical baseline at the initial audit

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
