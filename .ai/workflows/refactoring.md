# Workflow: refactoring

Changing the shape of code without changing what it does. The most dangerous
workflow in a legacy codebase, because success is invisible.

> Who runs a stage is set by `pipeline_profile` in `policies/risk-tiers.json`.
> The table names the agent for when a stage is delegated. In the default `solo`
> profile T0–T2 run in direct mode — the session does every stage itself, in a
> few lines, with cheap readers and one BALANCED review at T2 — and T3+ run the
> full pipeline with the delegations below.

| Stage | Who | Notes |
|---|---|---|
| DISCOVERY | `ai-discovery` | every caller, every test, every dynamic reference |
| CONTEXT | `ai-context` | with `legacy_constraints` filled properly |
| IMPACT ANALYSIS | `ai-discovery` | serialized data, cache keys, service ids, event names |
| RISK CLASSIFICATION | `ai-risk` | shared code is T3 minimum |
| PLAN | `ai-planner` | characterization tests as their own steps, before anything moves |
| PLAN REVIEW | `ai-reviewer` | always, whatever the tier |
| IMPLEMENTATION | the session | one mechanical transformation per step |
| TEST | the session (`ai-tester` in `team`) | the same tests, unchanged, must still pass — the characterization tests for the moved code run inside their step, the full suite once at the end, then the e2e suite once; failures fixed as one batch |
| ADVERSARIAL REVIEW | `ai-reviewer` | looking specifically for behaviour drift |
| RELEASE REPORT | `ai-release` | |
| HUMAN APPROVAL | the human | |

## The order, which is not negotiable

```
characterization test → refactor → verify identical behaviour → (a different task) feature change
```

## Rules

- **Never** combine a refactoring with a feature change. If the plan contains
  both, it is two tasks.
- A test that has to be modified during a refactoring is a signal that behaviour
  changed. Stop and say so.
- Renaming something that appears in a string — a service id, a template name, a
  queue name, a route name, a column — is not a rename, it is a migration.
- If the only justification is "this is cleaner", the task needs a human to
  confirm it is worth the risk before it starts.
