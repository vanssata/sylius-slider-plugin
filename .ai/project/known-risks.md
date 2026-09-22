# Known risks

<!-- Findings that are real but out of scope for the task that found them. This
     is where "do not fix it, write it down" lands. A human turns entries here
     into their own tasks. -->

<!--
## <short title>

- **Found**: date, by which task
- **Where**: `file:line`
- **What could go wrong**:
- **How likely / how bad**:
- **Suggested fix**: one sentence, not a plan
- **Status**: open / ticketed / accepted / fixed
-->

## Functional tests leak a second channel into the shared test DB

- **Found**: 2026-09-22, T-2026-09-22-001 (Sylius ~2.1.0 CI leg)
- **Where**: `tests/Functional/FunctionalTestCase.php:59` (`ensureChannel()`)
- **What could go wrong**: KNOWN FACT — it persists channel `FUNCTIONAL` (hostname `localhost`) and never removes it; CI runs the non-unit PHPUnit suite before Behat on the same DB. With two channels Sylius' `SingleChannelContext` fallback no longer applies, so any Behat/functional request whose host matches no channel hostname throws `ChannelNotFoundException`. That is how the 2.1 leg broke; the Behat shop context now requests the pinned host, but new steps that browse the shop can hit it again. RISK — the Behat fixture step pins *every* channel, `FUNCTIONAL` included, to the same hostname, so the shop request resolves via `ChannelRepository::findOneEnabledByHostname()` (`ORDER BY id ASC`) to the lowest-id channel. That is `FASHION_WEB` only because the Sylius fixtures run before any functional test; reordering CI steps or recreating the DB differently would silently resolve the wrong channel instead of throwing.
- **How likely / how bad**: medium / test-only, CI red.
- **Suggested fix**: wrap functional tests in a rolled-back transaction (e.g. dama/doctrine-test-bundle) or remove the channel in `tearDown()`.
- **Status**: fixed in T-2026-09-22-002. KNOWN FACT: `FunctionalTestCase::tearDown()` removes the channels `ensureChannel()` created in that test (`tests/Functional/FunctionalTestCase.php`), and `tests/Functional/FunctionalTestCaseChannelCleanupTest.php` guards this. A channel that already existed is not removed, so a local DB that already holds `FUNCTIONAL` from an earlier run keeps it until it is recreated. The admin user, sliders and slides those tests create are still left behind; none of them affects channel resolution. dama/doctrine-test-bundle was not used because it would add a dependency. RISK (review, MEDIUM): when a test has already failed, PHPUnit drops an exception thrown by the cleanup in `tearDown()`, so that one channel can be left behind silently. It cannot reach Behat in CI, because a failed non-unit step ends the job first.
