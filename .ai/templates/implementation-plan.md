# Implementation plan: {{TASK_ID}}

goal:
current_behaviour:
desired_behaviour:
affected_components:
explicitly_unaffected:

## Steps

<!-- One commit's worth of work each; the repository works after every one.
     allowed_files is enforced by ai-scope-guard, so it must be accurate. -->

### Step 1

- description:
- allowed_files:
- forbidden_files:
- forbidden_reason:
- required_behaviour:
- behaviour_that_must_not_change:
- required_tests:      <!-- the tests this step adds or must keep green -->
- step_tests:          <!-- the scoped command that runs exactly those, and nothing else:
                            step_test_command from testing.md with this step's path or filter.
                            Never the full suite, never e2e. "none" is a valid answer. -->

## End-of-task verification

<!-- Runs once, after the LAST step — not per step. -->

- verify_command:      <!-- the full fast suite, e2e excluded -->
- e2e_command:         <!-- once, after the fast suite is green; "none" plus the reason
                            when no e2e-covered flow can be reached by this change -->

## Compatibility strategy

## Migration impact

<!-- T5: locks, table size, duration, deployment order, old-version
     compatibility. Otherwise "none" with a sentence saying why. -->

## Rollback

## Observability

<!-- The log line, metric or dashboard that shows this working, and the one that
     would show it failing. -->

## Risks

## Open questions

<!-- Mark blocking ones (blocking). A plan with a blocking question is not
     approved. -->
