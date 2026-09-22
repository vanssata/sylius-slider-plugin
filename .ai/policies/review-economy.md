# Review economy policy

Review is the most expensive stage in the pipeline and the one that catches the
defects nobody else would. This policy does not make it cheaper by doing less of
it. It makes it cheaper by **not paying twice for the same evidence**.

The measurements below come from one real T4 bugfix: ~100 minutes wall clock, of
which **~73 were subagents and ~60 were review** — two plan reviews and four
adversarial passes at STRONG, each 5–10 minutes. Three of the four adversarial
passes returned `blockers_open`, and **two of those blockers were regressions
introduced by the remediation for the previous one**.

## The one rule

**A fact verified once is never verified again by a thinking model.**

Every re-derivation is a fresh agent paying full price to reach a conclusion the
project already owns. Write the conclusion down where the next agent must read it.

## 1. Probe before you delegate

Before handing a diff to `ai-reviewer`, the manager runs its own adversarial probe
and attaches the result to the review request.

This is not a review. It is a **shape matrix**: the five to fifteen inputs that
the change's own threat model says are interesting, run through the real code or
through the language semantics the code depends on. One `node -e`, one `php -r`,
one `python3 -c` in the scratchpad. A minute of work.

In the measured task every blocker was a language-semantics fact reachable this
way — `JSON.stringify` calls `toJSON` even on a function value; `Array.prototype.map`
runs `ArraySpeciesCreate` and returns the caller's subclass; `Array.from` reads
and executes the caller's `@@iterator`; `Array.isArray` is true for a `Proxy`.
Each was confirmed in five lines **after** a ten-minute reviewer found it.

Derive the matrix from what the change *does*, not from a checklist. A change that
serialises untrusted input probes the exotic shapes of its language: subclasses,
proxies, boxed primitives, accessors, `null`-vs-`undefined`, the empty and the
enormous. A change to a state transition probes the transitions that should be
unreachable. A change to a query probes the empty result, the duplicate and the
concurrent writer.

Record the matrix and its outcomes in the ledger below. The reviewer then starts
where you stopped instead of starting where you started.

## 2. The evidence ledger

`.ai/reports/<task-id>/review-ledger.md` is append-only and is read by every
review agent before it plans its own work.

```
| claim | verified by | outcome | pass |
|---|---|---|---|
| the SECRET_KEYS gate reads the raw key, no un-gating path | node -e, both orders | CONFIRMED | probe |
| both regexes byte-identical to HEAD | git diff | CONFIRMED | 1 |
| `.map` returns the caller's Array subclass | node -e | DEFECT, fixed L-step-17 | 2 |
| component chain `[db.migrate]` byte-identical | pnpm test | CONFIRMED | 1 |
```

Rules:

- A row is written by whoever established it — manager, probe, or a review pass.
- A `CONFIRMED` row is **out of budget** for later passes. A reviewer that
  re-derives one has spent the task's money on nothing.
- A row is only re-opened when the code under it changed. Say which change
  re-opened it.
- `DEFECT` rows stay, with the step that fixed them. They are the task's memory of
  what has already gone wrong, and the second-attempt rule below reads them.

The ledger is what replaces agent memory. Without it, every pass is pass one.

## 3. Findings are fixed as one batch, and re-review is scoped, not repeated

A review returns all its findings at once, and they are fixed at once: one
`state.py remediate` step whose scope is the finished steps plus what the
findings name, every BLOCKER and HIGH addressed in it, then the verification
command once. Never one fix, one run, one re-review per finding — that is the
serial loop the measurements below describe, and the remediation of one finding
is where the next regression comes from.

A review after remediation answers two questions and no others:

1. Does each named finding actually close, against the real code?
2. **What did the remediation introduce?**

Nothing else. The prompt says so explicitly, and points at the ledger for
everything already settled. A re-review that re-reads the whole change is a new
first review wearing the wrong name, and it costs the same.

## 4. Split one pass into parallel dimensions, not serial rounds

Four serial passes at ten minutes are forty minutes of wall clock. Three narrow
reviewers in one message are ten.

Split by **failure class**, so the dimensions do not overlap and no reviewer needs
the whole system:

- semantics and types — what the code now computes that it did not;
- resources and failure modes — what can throw, hang, allocate without bound, or
  abort the process; what is uncatchable;
- the record — does every claim in the plan, the docs and the risk register
  resolve and hold.

Give each the ledger and its own dimension only. Merge the findings yourself.
This is cheaper *and* better: a reviewer with one job goes deeper into it than a
reviewer asked to cover everything.

## 5. The second-attempt rule

**When a fix is the second attempt at the same defect class, stop patching and
name the invariant first.**

In the measured task the same class was patched three times: `.map` returned the
caller's type, so it became `Array.from`, which ran the caller's iterator, which
became a bounded indexed loop. Each patch was a local answer to a local symptom.
The invariant — *caller-controlled metadata must never steer the walk: not the
type it emits, not the iteration protocol, not the length* — was only written down
after the third pass, and would have produced the final code on the first.

So: before implementing the second fix for a class, write the invariant into the
plan in one sentence, then implement to the invariant and probe against it. A fix
that changes **how** a value is produced, not merely what it contains, always gets
the shape matrix before review.

## 6. Cheap facts stay cheap

Never spend the thinking tier on mechanical verification. Delegate to FAST or to a
script, or run it yourself:

- every `file:line` citation in the plan, the reports and the risk register still
  resolves to what it claims — re-derive these **last**, after the code is final,
  once, rather than three times as citations drift;
- the formatter and linter pass on every file a step touched — a step that writes
  a test file ends by formatting it, or the next fix step fails on someone else's
  file and cannot touch it;
- test counts before and after, and which skips are environmental.

## 7. The record is part of the change

A review pass spent on a self-contradictory risk register is a pass not spent on
the code. When a task closes a gap that a register entry calls open, the entry is
corrected **in the same step as the fix**, not later. When the plan gains a
remediation, the plan gains a section — at T3 and above the artefact a human
approves must describe the change that exists, and a diff wider than the plan is
invisible to the path guard, because the file set never changed.

## 8. The one thing the sensors may replace

At T2 and below, when every sensor in `risk-tiers.json` `required_for_skip` is
green or explicitly not applicable **on the tree as it is now**, the model
review is replaced by the sensor set and `state.py review-gate` records
`review_status: skipped_green`. This is the intent's decision 6, and it is an
exception to the rule below, written here so the two do not contradict each
other.

It is narrow on purpose. The skip requires that the suite passed on this exact
tree, that the tests the plan named **fail without the change** (`bite` — a
green suite that never reaches the diff is not evidence), that the diff is
inside its budget, and that the tier re-scored from the real diff is still T2
or lower. A missing linter is not green: `unavailable` keeps the review, and
only a human writing `lint_command: none` says the project has no linter. No
sensor writes `skipped_green` by asserting it — `review-gate` reaches it from
measurements, and `state.py set review_status skipped_green` is refused.

From T3 the review always runs. What the sensors change there is where it
starts: their rows are in the ledger, CONFIRMED, before the reviewer plans its
work.

## What this policy will not trade away

- No review stage the tier requires is skipped, shortened, or merged — except
  the T2 model review, and only under §8's measured conditions.
- No reviewer is moved to a cheaper model to go faster. Narrow the surface, never
  the mind.
- A blocker still returns to implementation, however late it arrives and however
  many passes it costs.
- The probe is the manager's own work and never replaces the adversarial pass. It
  exists so the pass starts further in, not so it happens less.
