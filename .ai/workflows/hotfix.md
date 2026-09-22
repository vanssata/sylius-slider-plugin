# Workflow: hotfix

Production is broken now. Stages get shorter; none of them disappears.

> Who runs a stage is set by `pipeline_profile` in `policies/risk-tiers.json`.
> The table names the agent for when a stage is delegated. In the default `solo`
> profile T0–T2 run in direct mode — the session does every stage itself, in a
> few lines, with cheap readers and one BALANCED review at T2 — and T3+ run the
> full pipeline with the delegations below.

| Stage | Who | Notes |
|---|---|---|
| DISCOVERY | `ai-discovery` | narrow: the failing path only |
| CONTEXT | the session, inline | a paragraph, recorded in the state file |
| RISK CLASSIFICATION | `ai-risk` | the tier of the code being touched, not of the urgency |
| PLAN | `ai-planner` or the session | the smallest change that stops the bleeding |
| IMPLEMENTATION | the session | one step |
| TEST | the session (`ai-tester` in `team`) | at minimum, a test that reproduces the incident, run scoped; the full command once after the fix, then e2e only if the incident is in a flow it covers; failures fixed as one batch |
| ADVERSARIAL REVIEW | `ai-reviewer` | short, focused on "what else does this touch" |
| SECURITY REVIEW | `ai-security` | if the incident is a security incident |
| RELEASE REPORT | `ai-release` | short form, but rollback is mandatory |
| HUMAN APPROVAL | the human | always, and usually faster than the rest |

## Rules

- Urgency lowers the *depth* of a stage, never its existence. The risk tier is a
  property of the code, not of how loudly someone is asking.
- A hotfix is the smallest possible change. Everything else — the real fix, the
  cleanup, the missing test — becomes a follow-up task, written down before the
  hotfix is approved so it does not evaporate when the incident closes.
- Record what actually happened in `project/known-risks.md`: the trigger, the
  detection, and what would have caught it earlier.
- Never disable a test or a check to get a hotfix out. If CI is the obstacle, a
  human decides that, explicitly.
