# Context management policy

Context length, not model choice, is the dominant cost of an agent session: the
main session re-reads its whole context on every turn, so one large file read is
paid for again on every subsequent turn.

## The progressive strategy

```
repository → candidate files → relevant files → structured summary → task context
```

Each arrow narrows the material. Agents downstream of the summary should receive
the summary plus the exact files they need, never the repository.

## Rules for every agent

- Search with `rg -n` / `grep -n` and read only the ranges that matched. Do not
  read a file in order to search it.
- Never read a file over ~4000 lines whole. Read the range, or send a reader
  agent and keep its answer.
- A large file is read by the **cheapest model**: `Explore` for code,
  `log-reader` for logs and test output, both on FAST. They return the relevant
  excerpt with `file:line` and one line on why — not the file. A reader that
  returns the whole file has answered the wrong question; narrow it and ask
  again.
- Tools and MCP servers are context too. Only the ones this task named are
  loaded, and they go off again at the end of it — `tooling.md`.
- Never paste logs, test output, migrations, lockfiles or generated code into the
  context. Summarise, cite, and move on.
- Prefer one subagent that returns twenty lines over five tool calls whose raw
  output stays in context for the rest of the session.
- Do not rediscover what `.ai/project/` already records. If it is wrong, fix the
  file — that is what it is for.

## Context budgets

| Tier | Gets |
|---|---|
| FAST | a file list, a pattern, one question |
| BALANCED | the task context plus the specific files named in it |
| STRONG | the compressed task context plus the critical source, nothing else |
| EXPERT | only what the decision turns on: the conflict, the constraints, the options |

Never hand a stronger model unrelated source files, full logs, complete git
history, dependency trees, vendor directories, caches or build artifacts.

## Large files, by whom

| Size | Who reads it | What comes back |
|---|---|---|
| a known range | the session | the range, via `offset`/`limit` |
| a large file, target known by `grep -n` | the session | only the matching ranges |
| a large file, target not yet located | a FAST reader (`Explore`) | the ranges that matter, `file:line`, one line each |
| logs, test output, CI output | a FAST reader (`log-reader`) | the failing lines, at most ~30, plus a diagnosis |

A STRONG agent never reads a large file to find something in it: it receives the
excerpt a FAST reader already found. Paying the STRONG tier to scroll is the most
expensive way to do the cheapest job in the pipeline.

## Deterministic tools first

`rg`, `git`, `jq`, an AST tool, the language server, PHPStan/Psalm, the framework
CLI and the test runner answer questions exactly and for free. Ask the model to
reason **about their output**, not to reproduce what they already know.

## Structured context shape

The Context agent produces exactly this, and nothing else:

```
task:
affected_modules:
entry_points:
execution_flow:
relevant_files:
interfaces:
dependencies:
legacy_constraints:
business_rules:
tests:
known_risks:
unknowns:
open_questions:
```
