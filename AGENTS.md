See @CLAUDE.md

## Rules

- Never change code in `vendor/` or `node_modules/`.
- **Nothing runs on the host.** This machine has no PHP and no Node — every
  `php` / `composer` / `vendor/bin/*` / `yarn` / `node` invocation goes through
  `docker compose run --rm php …`, `docker compose exec …` or a `make` target.
  See `.ai/project/ai-tooling.md` and `.ai/project/commands.md` for the full command table.
<!-- claude-agentic:start -->
## AI agent workflow

This repository runs an agentic pipeline under `.ai/`. Read `.ai/AGENTS.md` first: it routes to the policies, workflows and rules, which load on demand; `.ai/policies/` is binding.

- Production behaviour is the source of truth: document problems outside the task, do not fix them.

- A change runs through `/ai-task <request>`; `/ai-status` shows where it stands. Each step names the files it may touch — an edit outside them is refused: answer `SCOPE_CHANGE_REQUIRED`. One `apply_patch` is checked file by file: a patch that reaches outside the step is refused whole.

- Verify before reporting done: `verify_command` from `.ai/policies/testing.md` once, to the end, then `e2e_command` once; every failure fixed as one batch; show the output.
- No agent commits, merges or deploys; approval is given by a human outside the agent. When a review flags the same mistake twice, the correction goes into this file.
- Files, not chat, carry decisions: `.ai/reports/<task-id>/questions.md` (answer by filling `[Answer]:`), `.ai/state/handoff.md` (read first when resuming), `docs/sdlc/constitution.md` (this project's principles).
<!-- claude-agentic:end -->
