# Codex continuity entry point

BayPdf work can continue without access to an earlier chat. At the start of a new conversation, read and reconcile these sources in order:

1. `AGENTS.md`
2. `docs/codex/README.md`
3. `docs/codex/PROJECT_CONTEXT.md`
4. `docs/codex/CURRENT_STATE.md`
5. `docs/codex/ROADMAP.md`
6. `docs/codex/DECISIONS.md`
7. Relevant package documentation under `docs/`
8. `git status`
9. `git log --oneline --decorate -20`

The Git tree is authoritative for implemented behavior. Compare the documents with the current branch, HEAD, working tree, tests, and recent commits before continuing. Reconcile stale documentation in the same logical unit as the related implementation work.

## “Devam et” protocol

When the user says “Devam et” or gives an equivalent continuation instruction:

1. Read every applicable `AGENTS.md`.
2. Read this entry point and `CURRENT_STATE.md`.
3. Inspect Git status, current branch, and recent log.
4. Compare documented state with code, tests, and Git history.
5. Reconcile any mismatch without discarding user changes.
6. Identify the last completed logical unit from Git and `WORKLOG.md`.
7. Continue from the next unchecked acceptance criterion in `ROADMAP.md` whose dependencies are complete.

Do not infer completion from chat history. A criterion is complete only when its implementation and fresh verification evidence exist in the repository state.
