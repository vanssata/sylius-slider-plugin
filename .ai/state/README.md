# Task state

`current.json` is the machine-readable record of the task in flight. It is
written by `skills/ai-task/state.py` (atomically), read by `/ai-status`, and
enforced by `ai-scope-guard`, which derives the files the current step may touch
from it.

Two other writers exist, and only these two. A schema migration in
`/project-update` may change the state through `ctx.patch_state()`, with the
same atomic write and a `schema_migrated` history entry — that is how a task in
flight survives a change to the shape of this file. And `hooks/context-guard.py`
writes `session.json`, the sidecar beside this file, and nothing else: it never
touches `current.json`. Nothing else writes any of it — an agent never edits
them by hand, and the path guard refuses when one tries.

It is **not committed**: it describes a session, not the repository. The
`.gitignore` entry added by `/ai-init` keeps it out of git.

When a task closes, `state.py archive` moves it to
`.ai/reports/<task-id>/state.json`, which **is** committed — that is the audit
trail: which stages ran, in what order, who approved what, and when.

It holds facts, never transcripts:

```
task_id, goal, workflow, risk_tier, current_stage, affected_modules,
context_summary_ref, approved_plan { ref, current_step_id, steps[] },
completed_steps, test_status, e2e_status, review_status, security_status, open_risks,
next_action, owner_runtime, resume_point { stage, step_id, next_action, at, runtime },
questions { file, pending[] }, handoff { file, written_at, reason },
human_approval { required, granted, granted_by, granted_at,
                 requested_at, gate_id, requested_session, rejected_at, via, unattended },
created_at, updated_at, history[]
```

`state.py` applies the schema-2 keys in memory as it loads, so a project that has
not run `/project-update` yet still answers every command; `save()` then writes
them. A reader that opens this file directly sees what is on disk, not those
defaults.

## The other files beside it

| File | Written by | Committed |
|---|---|---|
| `current.json` | `state.py`, and `update.py` during a migration | no |
| `session.json` | `context-guard.py`, on SessionStart and each prompt | no |
| `handoff.md` | `state.py handoff` — never a model | no |
| `../reports/<task-id>/questions.md` | `state.py ask/answer/questions` | yes |
| `../reports/<task-id>/events.jsonl` | `state.py` (`emit`), append-only | yes |

`handoff.md` is the ≤ 30-line frame a session that lost its context reads first:
the goal, the next action and its resume point, the pending questions, the latest
decisions, rejected options and failed attempts, and the user's last instruction
verbatim. It is rendered from the four sources above, so deleting it loses
nothing — the next stage-moving command writes it again.

`events.jsonl` is the journal: one line per state change, `history[]`'s facts
with types beside them. It is best effort by contract — a line that cannot be
written never fails the command that emitted it — so nothing depends on it being
complete, and readers skip a line they cannot parse.

If this file is corrupt, `state.py` refuses to read it rather than replacing it.
Inspect it by hand: the scope guard's boundaries come from here, and a silently
regenerated state file means an unbounded implementer.
