# Testing policy

Tests describe behaviour. Coverage percentage is a side effect, not a goal.

## Characterization first

Before changing behaviour that is not covered by a test — which is most legacy
behaviour — write a test that passes against the code **as it is today**. That
test is the definition of what must not break. Only then change the code.

```
characterization test → refactor → verify identical behaviour → feature change
```

Never do those in one step, and never in one commit.

## What to consider, by kind of change

| Change touches | Also test |
|---|---|
| a shared service | the existing callers, not only the new one |
| money, tax, fiscal | rounding, currency, negative and zero amounts, refunds |
| a state machine | every transition that is now reachable, and the ones that must stay unreachable |
| an async consumer | retry, double delivery, out-of-order delivery, poison message |
| an external integration | timeout, 500, malformed response, partial write |
| a write path | idempotency, concurrency, transaction boundaries |
| authorization | the negative case, for every role |
| a public API or event | the old shape still works |

## Three scopes, three moments

A task runs tests three times at most, and each run has its own scope. Nothing
else runs in between.

| When | What runs | Command |
|---|---|---|
| after each implementation step, before `step-done` | **only the tests the plan named for that step** — the files it touched, their direct callers' tests | `step_test_command` |
| once, after the **last** step | the full fast suite — unit and integration, no e2e | `verify_command` |
| once, at the **end of the task**, after the full suite is green | the end-to-end suite | `e2e_command` |

- A step never runs the full suite, and **never** runs e2e. If the plan named
  no test for a step, nothing runs for it.
- A step's tests are scoped by path or filter, not by `--stop-on-failure`: the
  scoped run still goes to the end, and its failures are fixed inside that step.
- The e2e suite runs **once per task**, at the very end, never per step. Skip it
  at T0/T1, and when the change cannot reach a flow the e2e suite covers — say
  which, in one line; silence is not a verdict.
- A failure in a step's own tests is fixed in that step, before `step-done`;
  the batch rule below is for the two end-of-task runs.

## Reading a failure

**Run to the end, then fix as one batch.** The verification command runs with
no fail-fast flag and is never interrupted at the first failure. Collect every
failure, classify each one, fix all the new regressions in a single remediation
step (`state.py remediate`), and run the command once more. One failure never
sends the task back to the start: the plan, the tier and the finished steps
stand. At most two remediation rounds; a third means the human decides.

Classify before fixing. The four answers are:

- **EXISTING TEST FAILURE** — it failed before this change too. Say so, do not
  fix it inside this task.
- **NEW REGRESSION** — this change broke it. Fix the code, not the test.
- **TEST ENVIRONMENT FAILURE** — database, fixtures, network, container. Say what
  is missing.
- **UNKNOWN** — you could not tell. Say what you tried.

**Never edit a test to make it pass when production behaviour changed
unexpectedly.** A test that suddenly disagrees with the code is evidence, and
deleting evidence is the worst available option.

## Verification

Three commands, one per scope. `verify_command` proves the project is healthy;
`step_test_command` proves one step is; `e2e_command` proves the whole flow
still works. Keep each to one command — chain the pieces in a Makefile or
composer script if there are several — and make sure none of them stops at the
first failure (`--stop-on-failure`, `-x`, `--bail`, `failfast` are off).

`verify_command` must **exclude** the e2e suite: it is the fast loop run after
the last step and again after a remediation batch. `e2e_command` runs once, at
the end of the task. A bugfix still shows its new failing test first — that one
test only, through `single_test`.

```
verify_command:      make verify
step_test_command:   docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit {files}
e2e_command:         make e2e          # one surface only: make e2e-check SPEC=tests/e2e/<ctx>/<file>.spec.ts
lint_command:        make ecs          # check only (ecs check src); make verify runs ECS with --fix instead
typecheck_command:   make phpstan      # level max + baseline (phpstan.neon)
healthy_output:      ECS: "[OK] No errors found" (it runs with --fix, so it may rewrite files and still pass)
                     PHPStan: "[OK] No errors" at level max against phpstan-baseline.neon
                     PHPUnit: "OK (<n> tests, <m> assertions)" and exit 0
                     Playwright: "<n> passed (<time>)" across the desktop/tablet/mobile projects
runtime:             make verify ~2-4 min (container start + PHPStan dominates)
                     a scoped phpunit run ~20-40 s (container start dominates)
                     make e2e ~5-10 min including the playwright service coming up
single_test:         docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit --filter <TestMethodName>
```

`step_test_command` may contain `{files}`, which is replaced by the paths the
step names; without it the paths are appended. The placeholder is what lets
`sensors.py` run a step's own tests for the "test must bite" check, so a runner
that only takes a `--filter` gets `bite: unavailable` rather than a wrong answer.

`lint_command` and `typecheck_command` are read by the deterministic sensors,
never by a model. They decide, together with the test run, whether a T2 change
can skip its model review (`risk-tiers.json`, `sensors`). A **missing** line is
`unavailable` and keeps the review; only the literal `none`, written by a human,
says the project has no such tool. `sensors.py detect` proposes the exact line
from the config files it finds, and never runs what it detected.

Nothing runs on the host: this machine has no PHP and no Node, and
`.claude/hooks/container-guard.sh` denies a bare `php` / `composer` /
`vendor/bin/*` / `yarn` / `npx` and prints the `docker compose` replacement.
Every command above is a `make` target or a `docker compose run`.

`APP_ENV=test` is not optional — the compose stack points `DATABASE_URL` at
`sylius_%kernel.environment%`, so a run without it hits `sylius_dev` instead of
`sylius_test`. `make verify` and `make phpunit` set it; a hand-written
`docker compose run` must pass `-e APP_ENV=test` itself.

If the project has no e2e suite, write `e2e_command: none` and say so — an
empty line is read as "not written down yet" and the next task will go looking
for it. Say which group, tag or directory marks the e2e tests and how
`verify_command` excludes them, so a step's scoped run and the fast suite stay
free of them.

## Project specifics

**Three runners, three jobs.**

| Runner | Config | Covers | Entry point |
|---|---|---|---|
| PHPUnit | `phpunit.xml.dist` | unit + integration, kernel-booted | `make phpunit` |
| Behat | `behat.dist.xml` | Sylius domain/UI scenarios; the `@javascript` leg drives the `chrome` service | `make behat` |
| Playwright | `tests/e2e/` | storefront + admin in a real browser, 3 viewports | `make e2e` |

Behat is **not** in `verify_command`: `composer ai:verify` is ECS → PHPStan →
PHPUnit only (`composer.json` `scripts.ai:verify`). Run `make behat` for the
feature a change touches, separately from the fast loop. `docs/dev/testing.md`
has the Behat-vs-Playwright decision.

**e2e exclusion.** The e2e suite is not a PHPUnit group — it is a separate
runner in a separate container (`playwright`), so `verify_command` cannot
accidentally pull it in. `make e2e-down` stops that container; it is part of
finishing a task, nothing cleans it up at session end.

**Fixtures.** `make load-fixtures` (full Sylius) or `make load-slider-fixtures`
(`vanssa_sylius_slider_demo` only, = `composer load-slider-demo-fixtures`).
Behat needs an `ENV=test` stack with fixtures loaded. Playwright drives the
running nginx through the shared Traefik —
`BASE_URL=http://sylius-slider.localhost` (bypass: `http://localhost:82`).
Loading fixtures resets the `FASHION_WEB` channel hostname and 404s the whole
shop until it is cleared again: `docs/dev/local-domains.md`. In `sylius_test`
the `FUNCTIONAL` channel must keep `hostname = 'localhost'` — the functional
tests reuse it and fail with "Channel could not be found!" if it is nulled.

**Frontend changes need a compiled bundle before any browser test.** Start the
watcher once per task (`make node-watch`), confirm the recompile
(`make node-watch-logs`), stop it at the end (`make node-watch-stop`). Restart
it — not just re-sync — after editing any `controllers.json` or
`assets/package.json`; webpack merges those at config-load time only.
`make e2e-check SPEC=…` already blocks until the bundles are newer than the
newest `assets/` source.

**CI.** `.github/workflows/` — check it before assuming a command is covered.
