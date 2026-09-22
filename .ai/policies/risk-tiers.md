<!-- generated from risk-tiers.json sha256:6c7b1b9b733f3ec7d8e038e62f484419c5e888cd094f64c1f1471b9cd9bb3fe8 -->
<!-- If /ai-status reports this hash as stale, risk-tiers.json changed and this
     mirror did not. The JSON file is the source of truth; update this by hand. -->

# Risk tiers

Every task gets exactly one tier before anything is planned. The tier decides
three things: who plans it, who reviews it, and whether a human signs it off.

| Tier | What it covers | Plan reviewed | Human approves the plan | Adversarial review | Security review | Human approves the release |
|---|---|---|---|---|---|---|
| **T0** | documentation, comments, translations with no logic | no | no | no | no | no |
| **T1** | formatting, an isolated admin screen, a label | no | no | no | no | no |
| **T2** | a normal isolated feature, a new service with limited reach | no | no | yes | no | no |
| **T3** | shared domain behaviour: orders, workflows, async processing, an important integration | yes | yes | yes | when auth or personal data is touched | yes |
| **T4** | payments, accounting, tax, fiscal, authentication, authorization, order state transitions, customer data | yes | yes | yes | yes | yes |
| **T5** | migration strategy, infrastructure, Kubernetes, production deployment, destructive schema change, cross-system migration | yes | yes | yes | yes | yes |

## Which model works on it

The tier is the contract; which model it resolves to depends on the runtime the
session is in. The pipeline, the gates and the obligations below are identical
either way.

| Tier | Plans and reviews on |
|---|---|
| T0, T1 | the session, directly; readers on FAST |
| T2 | BALANCED |
| T3, T4 | STRONG |
| T5 | EXPERT — `ai-expert` |

Which model a tier is comes from the installed plan, per runtime: `state.py profile
--tier <TIER>` prints it, and `/ai-status` shows all four.

Under Codex an agent's own file outranks the model asked for when it is spawned,
so the T3/T4 re-runs of `ai-risk` and `ai-planner` use the dedicated
`ai-risk-strong` and `ai-planner-strong` agents. Same tier, same trigger.

Report the model that actually ran, not the one that was requested: a rate-limit
gate may have moved an agent down a tier.

Escalation is per task and one step at a time. The triggers are listed in
`risk-tiers.json`; the short version is that a cheap agent which returns thin,
contradictory or empty evidence gets escalated, and nothing starts expensive
"just in case".

A tier can be raised by anyone at any moment. Lowering one is a human decision,
written into the task record with the reason.

## Who does each stage: the pipeline profile

`pipeline_profile` in `risk-tiers.json` decides who runs a stage, never whether
it runs. Every tier keeps its `stages_required`; the profile says which of them
go to a subagent. The default `solo` profile has two modes:

- **direct** (T0–T2): no pipeline ceremony. The session names the files, edits,
  runs the step's own tests, then the verification command once at the end,
  then the e2e suite once after it, and fixes every failure as one batch. T0/T1 keep no state file at all; T2 is one `state.py quick` call that
  arms the scope guard and one `state.py close` at the end. The only subagents
  are cheap readers (`Explore`, `log-reader` on FAST) and, at T2, one
  `ai-reviewer` on BALANCED over the diff.
- **sdlc** (T3–T5): the full pipeline, recorded stage by stage, with the plan,
  plan review, adversarial review, security review and release report delegated
  to the STRONG and EXPERT tiers as the table says.

| Tier | `solo` (default) delegates | `team` delegates |
|---|---|---|
| T0, T1 | nothing — direct mode, no state file: say which files, edit, verify (T1) | discovery (and the test run at T1) |
| T2 | the adversarial review, on BALANCED; the plan is three to five lines in the conversation | every stage except implementation |
| T3 | plan, plan review, adversarial review — on STRONG | every stage except implementation |
| T4 | plan, plan review, adversarial review, security review, release report | every stage except implementation |
| T5 | discovery and impact as well; the plan goes to `ai-expert` | every stage except implementation |

Tests run in three scopes, and never more often than that. **Inside a step**:
only the tests the plan named for that step, through `step_test_command` scoped
to the files it touched — never the full suite, never e2e; those failures are
fixed in the step, before `state.py step-done`. **After the last step**: the
full fast suite, `verify_command`, once, to the end, with no fail-fast flag —
every failure is collected and classified first, every new regression is fixed
in **one** remediation step (`state.py remediate`), and the command runs once
more. At most two rounds, then the human decides. **At the end of the task**,
after the fast suite is green: `e2e_command`, once — never per step, skipped at
T0/T1 and whenever the change cannot reach an e2e-covered flow, which is said
in one line. Review findings are handled the same way as test failures: one
batch, one scoped re-review only when a blocker was fixed. A bugfix still shows
its failing test first.

In `solo` the session still delegates one `ai-discovery` when the area is
unfamiliar or the plan depends on an `UNKNOWN`, `ai-risk` on STRONG when a T3+
classification is not obvious, and `log-reader` when the verification output is
long. The triggers are listed under `delegate_anyway_when`.

## Diff budget, scopes and sensors

A tier is a prediction made before the work; the diff is what the work turned out
to be. `state.py step-done` measures the step and the task against `diff_budget`
and re-scores the tier from `path_scopes`.

| | per step | per task |
|---|---|---|
| T0 | 300 lines / 15 files | 600 / 30 |
| T1 | 120 / 5 | 300 / 10 |
| T2 | 200 / 8 | 400 / 15 |
| T3 | 250 / 10 | 800 / 30 |
| T4 | 150 / 6 | 400 / 15 |
| T5 | 150 / 6 | 400 / 15 |

Lock files, snapshots, minified and generated files are excluded; the `docs`
scope is counted and reported but not budgeted. Over the budget is **not** a
refusal to do the work — it asks for the step to be split
(`state.py step-split <id> --files …`). A file outside the step's
`allowed_files` is `SCOPE_CHANGE_REQUIRED`, as it has always been.

`path_scopes` is ordered and the first match wins, which is why `pipeline`
stands above `docs` and `tests` above `payments`. A change reaching
`**/Payment/**` is at least T4 whatever the tier said at the start. Re-scoring
**only ever raises** a tier: `downgrade_rule` means a human lowers one, in
writing, and `state.py risk`/`triage` now refuses a lowering that no human
asked for.

### When the sensors replace the review

At T2 and below, when every sensor in `required_for_skip` is green or
explicitly not applicable — the suite passed on this exact tree, the required
tests fail without the change (`bite`), the diff is inside its budget and the
re-scored tier is still T2 or lower — the model review is replaced by the
sensor set, and `state.py review-gate` records `review_status: skipped_green`.
This is the intent's decision 6; `review-economy.md` carries the reasoning.

A sensor is green only when it measured something. A missing `lint_command` is
`unavailable` and blocks the skip; only `lint_command: none`, written by a
human in `testing.md`, counts as not applicable. `sensors.py detect` proposes
the line and never runs the tool it detected.

## Extra obligations by tier

- **T3 and above**: characterization tests come before any change to legacy behaviour.
- **T4**: the release report must have a rollback section and a monitoring section,
  and the plan must state the idempotency and retry behaviour explicitly.
- **T5**: migration analysis covering locks, table size, duration, deployment
  ordering and old-version compatibility; a rehearsed rollback, not a described
  one; and destructive operations need their own separate approval that names
  the operation.

## When the tier is not obvious

Take the higher one. The cost of an unnecessary review is a few minutes; the
cost of an unreviewed payment change is not.
