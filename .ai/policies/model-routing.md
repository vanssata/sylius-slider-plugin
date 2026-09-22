# Model routing policy

**The tiers are the contract; the models are the implementation.** This project
can be worked on from Claude Code, from Codex, or from both, and the pipeline is
identical either way. What changes is which model each tier resolves to. Routine
workload runs on the cheap tier; the expensive tiers are paid for only on a named
trigger — adversarial review, high-risk decisions and what a cheaper tier could
not settle — never for fact collection.

## Tiers

| Tier | Model | Used for |
|---|---|---|
| **LOCAL** | *not available* | see the note below |
| **FAST** | the plan's FAST model, effort `low` | reading and running: file search, inventories, counting, logs and test output, running a command and reporting it (`Explore`, `log-reader`, `ai-tester`, `ai-indexer`) |
| **BALANCED** — default for agents | the plan's BALANCED model, effort `low`–`medium` | discovery with judgment, context compression, planning up to T2, mechanical edits, release assembly, the T2 review |
| **STRONG** | the plan's STRONG model, effort `high` | the STRONG triggers below |
| **EXPERT** | `ai-expert`, pinned to the plan's EXPERT model — `xhigh` where the plan allows it, `high` elsewhere; `architect` may pin a model of its own | the EXPERT triggers below |

Which model a tier is depends on the plan and the runtime: the installed profile
decides it, `state.py profile --tier <TIER>` prints it for the runtime you are in,
and `/ai-status` shows all four. This file names tiers only, so it is true on
every plan.

The main session runs the model the installed profile sets and does the
implementation itself. Which stages spawn an agent at all
is decided by `pipeline_profile` in `risk-tiers.json`: in `solo`, T0–T2 run in
direct mode — nothing on STRONG, one BALANCED review at T2 — and tests run once
after the last step, to the end, with every failure fixed as one batch.

**Codex agent precedence.** A value written into a Codex agent file wins over the
model asked for when the agent is spawned. So "re-run `ai-risk` on a stronger
model" cannot work there; the STRONG re-runs use the dedicated `ai-risk-strong`
and `ai-planner-strong` agents, which pin the STRONG model in their own files. Same tier, same
trigger, different mechanism.

**On LOCAL.** The original design of this system assumed a local model for
indexing and repetitive inspection. Neither runtime has a local-model backend, so
that work is done by deterministic tools (`rg`, `git`, `jq`, the framework CLI)
plus `ai-indexer` on the cheapest hosted model. Nothing in this infrastructure
depends on a local model; if one becomes available it slots in at the FAST tier.

## STRONG triggers

1. Review of a finished change before a commit is proposed (`ai-reviewer`), and
   `ai-security` for authentication, authorization, secrets, payments, personal
   data, webhooks and any T4/T5 change.
2. `ai-risk` on the BALANCED tier answered T3+ or `confidence: uncertain` — re-run
   it at STRONG; `ai-planner` at STRONG for T3/T4.
3. Root cause after a BALANCED diagnosis already failed once, or a bug in
   concurrency, retries/idempotency, caching or data integrity.
4. A reversible but costly design choice with two or more viable options
   (`architect`).

## EXPERT triggers

1. The task is T5: migration, infrastructure, production architecture.
2. STRONG could not settle it: `confidence: uncertain`, or two STRONG results
   contradict each other.
3. An irreversible design with several viable options — core data model, public
   API or event contract, service split.
4. The final check of a T5 plan or of a production-incident root cause before a
   human acts on it.

## Escalation

```
FAST → BALANCED → STRONG → EXPERT
```

One step at a time, for one task, when a trigger fires — and the trigger is named
in the task record. Never escalate the whole fleet, never start at EXPERT because
it exists, and never retry a failed *reasoning* task on a cheaper model —
downgrading is for mechanical work only. An overload is not a failure: the
fallback chain moves the agent to another model on its own.

## Fan-out

Cost scales with the number of agents, and every agent pays its own start-up. A
sweep of three or more parallel agents runs at FAST or BALANCED, always. One
STRONG or EXPERT agent per question, and one EXPERT agent per task.

## Briefing the expensive tiers

Collect first, cheaply. A STRONG or EXPERT agent receives a compact brief — the
context summary, `file:line` facts, the question and the options — and asks for a
specific file when it needs one. It is never sent to explore the repository.

## Reporting what actually ran

Every artifact that names a tier names the model too — "STRONG (<model>)", with
the model `state.py profile --tier STRONG` printed — because a tier alone does not tell a reviewer what the review
cost or how much weight to give it. Report the model that **ran**, not the one
that was requested: a gate may have sent an EXPERT agent down one tier during a
rate limit, and a Codex agent file may have overridden the spawn-time model. Both
runtimes expose the current state through `/ai-status`.

## What never gets delegated

The final synthesis, and anything the user reads as a conclusion. A cheap agent
may collect the evidence for it; the main session writes it.
