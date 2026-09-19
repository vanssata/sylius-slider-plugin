See @CLAUDE.md

## Rules

- Never change code in `vendor/` or `node_modules/`.
- **Nothing runs on the host.** This machine has no PHP and no Node — every
  `php` / `composer` / `vendor/bin/*` / `yarn` / `node` invocation goes through
  `docker compose run --rm php …`, `docker compose exec …` or a `make` target.
  See `CLAUDE.md` → *AI tooling* for the full command table.
- The `symfony-ai-mate` MCP server **is** configured, over stdio, via
  `.claude/scripts/mate-mcp.sh` (referenced from the gitignored `mcp.json`,
  which `.mcp.json` symlinks to). The launcher `exec`s into the running `php`
  service — never `docker compose run`, which leaks a container per session.
  Regenerate the gitignored `mate/` tree with `make mate-init` /
  `make mate-discover`.

<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: MCP-powered, project-aware coding guidance and tools.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer MCP tools over raw CLI commands whenever possible.
- Installed extensions: sylius/sylius-mate-extension, symfony/ai-mate, symfony/ai-symfony-mate-extension.
<!-- END AI_MATE_INSTRUCTIONS -->

<!-- claude-agentic:start -->
## AI agent workflow

This repository has an agentic engineering setup under `.ai/`. Read
`.ai/AGENTS.md` before making any change.

- Production behaviour is the source of truth. Document problems you find outside
  the task; do not fix them.
- Work runs through `/ai-task <request>`: discovery, context, impact, risk tier,
  plan, implementation, test, review, security review, release report, human
  approval. `pipeline_profile` in `.ai/policies/risk-tiers.json` says which of
  those the session does inline (solo: T0–T2 directly, with cheap readers and
  one `sonnet` review at T2; the full pipeline from T3) and which go to an
  agent. A step runs only its own tests; the full suite runs once after the last
  step and the e2e suite once after that, and every failure is fixed as one
  batch. `/ai-status` shows where a task stands.
- Verify before reporting done: run `verify_command` from the Verification
  section of `.ai/policies/testing.md`, then `e2e_command` once, and show the
  output of both. A bugfix starts with the
  failing test. When a review flags the same mistake twice, the correction goes
  into this file.
- Each implementation step names the files it may touch. Editing anything else is
  refused; answer `SCOPE_CHANGE_REQUIRED` and let the plan be amended. One
  `apply_patch` is checked file by file, so a patch that reaches outside the step
  is refused whole.
- The risk tier in `.ai/policies/risk-tiers.json` decides who reviews the change
  and whether a human must approve it. Payments, tax, fiscal, auth and order
  state transitions are T4 by default.
- Delegate to the named agents rather than asking for "a subagent": `ai-discovery`
  and `Explore` to read, `ai-reviewer` and `ai-security` to review, `ai-expert`
  only on a T5 or irreversible decision. A spawn with no agent named runs on the
  default subagent model.
- If this repository also has a `CLAUDE.md`, it describes the same pipeline for
  Claude Code. `.ai/` holds the one set of policies and task state both obey.
- Tools, MCP servers and large files are context. Anything this task does not
  need stays off, and a file too large to open is read by the cheapest model,
  which returns only the relevant excerpt. See `.ai/policies/tooling.md`.
- No agent commits, merges or deploys on its own initiative.
<!-- claude-agentic:end -->
