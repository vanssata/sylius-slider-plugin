# How agents work in this repository

One `.ai/` tree serves every runtime — same pipeline, same tiers, same scope rule
— and one task state under `state/`, so a task started in one runtime resumes in
the other. Read the non-negotiables below, then only the row for what you are doing.
Everything else loads on demand; nothing here is worth reading twice.

## Non-negotiable (read even if you read nothing else)

1. Production behaviour is the source of truth. Code that looks wrong may be
   load-bearing: document what you find, change only what the task asked for.
2. A change runs through `/ai-task`. Each step names the files it may touch; an
   edit outside them is refused — answer `SCOPE_CHANGE_REQUIRED` and have the
   plan amended, never widen the step yourself.
3. No agent commits, pushes, merges or deploys. Approval is given by a human,
   outside the agent.
4. Verify before reporting done: the verification command once, to the end,
   every failure fixed as one batch, the output shown. Run it through
   `state.py test-run` — it caps the runs, keeps the log out of the context,
   and a green exit code needs no agent to interpret it.
5. A refactoring changes no behaviour and is never mixed with a feature; legacy
   behaviour without a test gets a characterization test first.
6. Everything written into `project/` carries an evidence label — KNOWN FACT
   with `file:line`, INFERENCE with what it came from, UNKNOWN, RISK. An
   inference is never written as a fact.
7. `docs/sdlc/constitution.md` is this project's own list of principles. Cite
   them as `C<n>` in a spec, a plan or a review; say so in one line if it is
   absent, and carry on.

## Doing X → read Y

| Doing | Read |
|---|---|
| anything, first | `policies/safety.md` |
| starting or resuming a task | `state.py handoff --print`, then the `/ai-task` skill |
| deciding the tier, who reviews, what a human approves | `policies/risk-tiers.json` (`policies/risk-tiers.md` mirrors it) |
| choosing an agent or a tier | `policies/model-routing.md`, `policies/review-economy.md` |
| a large file, logs, a tool or MCP server | `policies/context-management.md`, `policies/tooling.md` |
| writing code under `<dir>/` | `policies/coding.md`; `rules/<slug>.md` for that directory |
| tests | `policies/testing.md` — Verification: the three commands |
| finishing a step, running the suite, asking whether the review can be skipped | `state.py step-done`, `state.py test-run`, `state.py review-gate` — the measurements are deterministic and the refusals name the way out |
| schema, data, migrations | `policies/database.md`, `policies/production.md` |
| auth, secrets, personal data, payments | `policies/security.md` |
| git, release | `policies/git.md`, `policies/release.md` |
| feature / bugfix / refactoring / hotfix / investigation | `workflows/<kind>.md` |
| acting as a role | `agents/<role>.md`; the manager is `agents/manager.md` |
| an artefact | `templates/<artefact>.md` → `reports/<task-id>/` |
| understanding the system | `project/overview.md`, then `project/*.md` |
| principles, intent, spec, plan, decisions | `docs/sdlc/constitution.md`, `docs/sdlc/` |
| the project is behind the plugin | `/project-update` (`VERSION` is this tree's schema) |

## Keeping this directory true

`project/` describes the system as it was last surveyed. When you discover that it
is wrong, fix it in the same task — a knowledge base nobody trusts is worse than
none. Re-run `/ai-init` after a large change to refresh it.
