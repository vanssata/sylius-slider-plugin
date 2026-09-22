# T-2026-09-22-001 — plan (T2, direct)
1. Behat shop context: non-JS sessions request the canonical host `localhost` that the fixture step pins.
   Files: tests/Behat/Context/Ui/Shop/SliderFrontendContext.php
2. CI: drop continue-on-error + wrong tsconfig comment; artifact name without ':'; CHANGELOG entry;
   record the FunctionalTestCase channel leak as a known risk.
   Files: .github/workflows/build.yaml, CHANGELOG.md, .ai/project/known-risks.md
Test: Behat slider_shop suite on Sylius 2.1.16 after `phpunit --testsuite=non-unit` (the CI order), then `make verify` + `make behat` on the 2.2 stack.

## Release report (short form)
- Changed: SliderFrontendContext non-JS visits request http://localhost (the hostname the fixture step pins);
  build.yaml drops continue-on-error + the wrong tsconfig comment, artifact name uses a colon-free DB_SLUG;
  CHANGELOG [Unreleased] Fixed; known-risks entry for the FunctionalTestCase channel leak.
- Preserved: no shipped code (src/, config/, templates/, assets/, translations/) touched; @javascript path unchanged.
- Verification: Sylius 2.1.16 copy, phpunit non-unit then Behat non-JS: 8 failed before, 14/14 after.
  Sylius 2.2.9 copy: ECS OK, PHPStan OK, PHPUnit 350 tests OK, Behat non-JS 14/14. JS Behat not run locally (no chromedriver); CI covers it.
- Review (BALANCED): code clean; BLOCKER = tier re-scored to T5 by infra path scope, needs human downgrade; MEDIUM fixed in R1 (known-risks).
- Rollback: git revert <commit>.
- Open: FunctionalTestCase channel leak (known-risks.md).
