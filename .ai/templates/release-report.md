# Release report: {{TASK_ID}}

<!-- The single page a human reads before approving. Every field gets an answer;
     "none" is an answer, "n/a" needs a reason. -->

task:
risk_level:

summary:

affected_modules:

files_changed:

behaviour_changed:

behaviour_preserved:
<!-- What was specifically checked, and how. Not "nothing else changed". -->

database_changes:
<!-- Migration, ordering, locks, duration, reversibility. "none" if none. -->

api_changes:
<!-- Including events and message payloads. Backward compatible? -->

configuration_changes:
<!-- New environment variables, parameters, feature flags, and their defaults. -->

external_integrations:

tests:
<!-- What ran, what passed, what is still failing and why. -->

security_review:
<!-- Verdict and open findings, or why it was not required. -->

known_risks:

monitoring:
<!-- What to watch after deployment, and where. -->

rollback:
<!-- Executable steps. If the change cannot be undone, say so plainly here. -->

manual_checks:
<!-- What a person must verify that no test covers. -->

review_findings:
<!-- Open BLOCKER/HIGH items, or "none open". -->

human_approval_required: yes | no
<!-- If yes: exactly what is being approved. -->
