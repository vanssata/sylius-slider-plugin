# Safety policy

This is the file every agent reads first. It is short on purpose. It binds every
agent in every runtime — Claude Code and Codex alike.

## The one rule

**Production behaviour is the source of truth until proven otherwise.** Code that
looks wrong, dead, or badly written may be load-bearing. The repository's history
is evidence; your intuition about how the code "should" look is not.

## What an agent must never do

- Change application code that the current task did not ask about.
- Rename, move, reorganise or "clean up" anything as a side effect.
- Remove code because it looks unused. Find the callers first; if there are none,
  say so in the report and leave the code alone.
- Combine a refactoring with a feature change in the same step.
- Modify database schemas, dependencies or framework versions outside a task that
  is explicitly about that.
- Weaken a test, a linter rule or a static-analysis level to make a check pass.
- Commit, push, merge or deploy on its own initiative.
- Read production secrets or customer data into the model context.

If you find a problem outside the current task: **write it down, do not fix it.**
Unrelated findings go to `.ai/project/known-risks.md` or into the report's
"observations" section, and the human decides whether they become their own task.

## Scope

Every implementation step names the files it may touch. Editing anything else is
refused by `ai-scope-guard` and must be answered with `SCOPE_CHANGE_REQUIRED`:
stop, say which file you need and why, and let the plan be amended. A step whose
scope keeps growing is a step that was planned wrong.

`ai-git-guard`, `ai-path-guard` and `ai-scope-guard` all run in both runtimes. A
multi-file edit in one call — Codex's `apply_patch` — is checked path by path, so
a single out-of-scope or protected file rejects the whole patch. What a guard
refuses is not a hint: do not reword the command to get past it.

A guard is a backstop, not the policy. Some checks exist in only one runtime —
Codex has no hookable read tool, so the large-read cap is Claude-only there — and
an agent that behaves correctly only because a hook is watching is not behaving
correctly. The rules above hold whether or not a hook enforces them.

## Evidence labels

Anything an agent writes into `.ai/project/` carries one of four labels:

- **KNOWN FACT** — read from the code, a config file, a migration or a test.
  Cite `file:line`.
- **INFERENCE** — a conclusion drawn from facts. Say what it is drawn from.
- **UNKNOWN** — something that matters and could not be determined. Say what
  would answer it.
- **RISK** — something that looks dangerous. Say what could go wrong.

Never present an inference as a fact. When documentation and code disagree,
record both under "Documented behaviour" and "Observed behaviour", with the
evidence for each, and let the human decide which is the bug.

## When in doubt

Stop and ask. An agent that pauses costs a message; an agent that guesses on a
payment path costs an incident.
