# T-2026-09-22-001 — discovery (Sylius ~2.1.0 CI leg)

## Symptom
Build workflow, leg "Sylius ~2.1.0, PHP 8.3, Symfony ^7.4, mysql:8.4": 8 non-JS scenarios of the
`slider_shop` suite fail with `Slider component section was not found.` (job 106743410072).

## Documented cause is wrong
- KNOWN FACT: job 106743410072 log — `yarn build` prints `shop`, `admin`, `app.shop`, `app.admin`
  "compiled successfully". No tsconfig / "Module build failed" line. The comment in
  `.github/workflows/build.yaml` (ux-live-component tsconfig) does not describe this failure.

## Dependency delta 2.1 leg vs 2.2 leg (same run 35727134244)
- sylius/sylius v2.1.16 vs v2.2.9; sylius/test-application v2.1.0-ALPHA.6 vs v2.2.0-ALPHA.1;
  sylius/twig-hooks, twig-extra v0.8.1 vs v0.9.1. Everything else identical.

## Root cause (reproduced locally, Sylius 2.1.16, throwaway copy in the php container, DB s21_test)
- KNOWN FACT: Sylius 2.2 `HostnameBasedRequestResolver::findChannel()` falls back across
  `LOCALHOST_EQUIVALENTS = ['localhost', '127.0.0.1', '::1']`; 2.1.16 does an exact
  `findOneEnabledByHostname($request->getHost())` only.
- KNOWN FACT: `tests/Behat/Context/Ui/Shop/SliderFrontendContext.php:34-47` pins every channel
  hostname to `localhost` for non-JS sessions, but `visitPath()` (`:55`) resolves against
  `base_url` = `http://127.0.0.1:8080/` (`tests/TestApplication/.env:3`), so the request host is `127.0.0.1`.
- KNOWN FACT: `tests/Functional/FunctionalTestCase.php:59-95` (`ensureChannel`) persists a second
  channel `FUNCTIONAL` and never removes it; CI runs the non-unit PHPUnit suite before Behat on the same DB.
- With one channel, `SingleChannelContext` (priority -128) masks the host miss — Behat alone passes
  locally (14/14). After `phpunit --testsuite=non-unit` there are 2 channels, Behat reproduces the
  exact 8 CI failures, and the failure HTML shows `ChannelNotFoundException: Channel could not be found!`.
- On 2.2 the localhost-equivalence fallback hides the same test bug.

## Second CI bug
- KNOWN FACT: "Upload Behat logs" fails: artifact name `Behat logs - Sylius ~2.1.0 - mysql:8.4 - <run_id>`
  contains `:` (build.yaml `name:` of the upload-artifact step).

## Out of scope (record, do not fix)
- RISK: FunctionalTestCase leaks the `FUNCTIONAL` channel into the shared test DB (test isolation).
