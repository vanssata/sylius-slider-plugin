# Workflow: feature

New behaviour that someone asked for. The default workflow.

> Who runs a stage is set by `pipeline_profile` in `policies/risk-tiers.json`.
> The table names the agent for when a stage is delegated. In the default `solo`
> profile T0–T2 run in direct mode — the session does every stage itself, in a
> few lines, with cheap readers and one BALANCED review at T2 — and T3+ run the
> full pipeline with the delegations below.

| Stage | Who | Notes |
|---|---|---|
| DISCOVERY | `ai-indexer` then `ai-discovery` | where the feature lands, what it will touch |
| CONTEXT | `ai-context` | the structured summary everything downstream reads |
| IMPACT ANALYSIS | `ai-discovery` (second pass) | callers, data, contracts, other environments |
| RISK CLASSIFICATION | `ai-risk` | one tier, from `policies/risk-tiers.json` |
| PLAN | none at T0/T1; the session at T2; `ai-planner` from T3 | steps with `allowed_files`; STRONG for T3/T4, `ai-expert` for T5 |
| PLAN REVIEW | `ai-reviewer` | T3 and above |
| IMPLEMENTATION | the session, one step at a time | scope-guarded |
| TEST | the session (`ai-tester` in `team`) | per step, only that step's tests; the full suite once after the last step, to the end, then the e2e suite once at the end of the task; every failure fixed as one batch, then one more run |
| ADVERSARIAL REVIEW | `ai-reviewer` | T2 and above |
| SECURITY REVIEW | `ai-security` | T4, T5, and anything touching auth or personal data |
| RELEASE REPORT | `ai-release` | full report from T2 up |
| HUMAN APPROVAL | the human | the pipeline stops here |

## Specific to this workflow

- The plan states what stays the same, not only what changes. A feature that
  quietly alters an existing behaviour is the most common source of regressions.
- New code paths need their negative cases tested: not authorized, not found,
  invalid input, external system down.
- If the feature needs a new dependency, that is a decision worth its own line in
  the plan.
