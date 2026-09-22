# Review — T-2026-09-22-002

Reviewer: ai-reviewer, BALANCED tier (model: sonnet). The report is written by the manager because the reviewer returns its findings instead of writing files.

**Verdict: passed.** No BLOCKER or HIGH.

| Sev | Finding | Disposition |
|---|---|---|
| MEDIUM | `FunctionalTestCase.php` `tearDown()`: PHPUnit drops an exception thrown in `tearDown()` when the test itself already failed (`TestCase::runBare()`), so the channel could be left behind silently in that run. | Not fixed. In CI a failed non-unit step ends the job before Behat, so the leftover channel cannot reach Behat. Recorded in `.ai/project/known-risks.md`. |
| LOW | The new test calls `tearDown()` itself and PHPUnit calls it again. It is harmless today because both calls are idempotent. | Kept: it tests the real lifecycle method, not a proxy for it. |
| LOW | `@var list<mixed>` could be `list<int>`. | Kept: `getId()` is `mixed` on Sylius' `ResourceInterface`, so `list<int>` would only move the PHPStan complaint. |

Examined and clean: tearDown ordering vs. kernel reboots (ids are stored, not entities); no foreign key from plugin entities to the channel (slider/slide channel codes live in JSON); Channel's currency/locale join rows cascade; a found channel is never registered for removal; the docs are accurate.
