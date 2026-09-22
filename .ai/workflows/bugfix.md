# Workflow: bugfix

Something behaves wrongly. The goal is the smallest change that fixes it without
changing anything else.

> Who runs a stage is set by `pipeline_profile` in `policies/risk-tiers.json`.
> The table names the agent for when a stage is delegated. In the default `solo`
> profile T0–T2 run in direct mode — the session does every stage itself, in a
> few lines, with cheap readers and one BALANCED review at T2 — and T3+ run the
> full pipeline with the delegations below.

| Stage | Who | Notes |
|---|---|---|
| DISCOVERY | `ai-discovery` | reproduce first: find the exact path that produces the symptom |
| CONTEXT | `ai-context` | include the *correct* behaviour and where it is defined |
| IMPACT ANALYSIS | `ai-discovery` | who else depends on the current, wrong behaviour |
| RISK CLASSIFICATION | `ai-risk` | a bug in a payment path is still T4 |
| PLAN | `ai-planner` | failing test first, then the fix |
| IMPLEMENTATION | the session | the failing test is its own step |
| TEST | the session (`ai-tester` in `team`) | the new test is shown failing before the fix; each step runs only its own scoped tests; the full verification command runs once after the last step, to the end, then the e2e suite once; every failure fixed as one batch |
| ADVERSARIAL REVIEW | `ai-reviewer` | T2 and above |
| SECURITY REVIEW | `ai-security` | if the bug was a security bug, always |
| RELEASE REPORT | `ai-release` | |
| HUMAN APPROVAL | the human | |

## Specific to this workflow

- **Write the failing test first.** A bugfix without a test that failed before it
  is a bugfix nobody can prove.
- Find out *why* the bug exists before fixing it. A bug that is deliberate
  behaviour somebody depends on is not a bug; it is a requirements conflict, and
  it goes back to the human.
- Resist the urge to fix the surrounding code. Note it, fix the bug.
- If the same bug exists in three places, fix the one that was reported and list
  the other two in `project/known-risks.md`.
