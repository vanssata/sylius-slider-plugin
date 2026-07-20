# Contributing

## Setup

```bash
composer install
(cd vendor/sylius/test-application && yarn install)
(cd vendor/sylius/test-application && yarn build)
vendor/bin/console assets:install
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
```

Optional demo data:

```bash
vendor/bin/console sylius:fixtures:load --suite=vanssa_sylius_slider_demo -n
```

## Quality checks

```bash
vendor/bin/phpunit --testsuite=unit
vendor/bin/phpunit --testsuite=functional   # needs the test database
vendor/bin/behat --strict --tags='@slider_admin'
vendor/bin/behat --strict --tags='@slider_frontend'
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/ecs check
vendor/bin/rector process --dry-run
```

All of these also run in CI (`.github/workflows/build.yaml`, workflow name
`Build`) against Sylius `~2.1.0` and `~2.2.0`. Docker equivalents:
`make phpunit`, `make behat`, `make phpstan`, `make ecs`, `make rector`
(see `Makefile`; `make init` / `make database-init` / `make up` / `make down`
bootstrap the Docker dev environment itself).

PHPStan uses a baseline (`phpstan-baseline.neon`) for pre-existing findings —
new code must analyse clean; do not add new entries to the baseline.

### Browser (`@javascript`) Behat scenarios

`features/admin/slider_editor_ux.feature` drives a real headless Chrome over
CDP (settings drawer, toolbar-driven locale/breakpoint editing, live draft
preview, preset try-on, fullscreen, scale-to-fit). They need the app served
under `APP_ENV=test` and a Chrome reachable at the `chrome` session's
`api_url` (defaults to `http://127.0.0.1:9222`; in the Docker setup copy
`behat.yml.dist` to `behat.yml` and point it at `http://chrome:9222`):

```bash
# Docker: serve the test env, then run against it
ENV=test docker compose up -d php nginx
docker compose run --rm -e APP_ENV=test -e BEHAT_BASE_URL=http://nginx \
    php vendor/bin/behat --strict --tags='@javascript'
```

If Playwright/browser testing doesn't reflect a fresh JS build, restart the
`chrome` container (`docker compose restart chrome`) — it can hold a stale
bundle in memory.

## Pull request rules

- Keep changes focused and atomic.
- Add or update tests for behavior changes.
- Update docs when adding fields/routes/features.
- Keep fixture media license-safe and documented.

## Coding conventions

- Follow Sylius and Symfony conventions.
- Use Twig Hooks for admin/shop UI composition.
- Prefer backward-compatible settings evolution in JSON fields.

## Releasing

- Update `CHANGELOG.md` (Keep a Changelog format) and cut the release
  section before tagging.
- The Symfony Flex recipe scaffold lives in
  `flex/recipes/vanssa/sylius-slider-plugin/<version>/` (human-readable
  sources) and is compiled to `flex/vanssa.sylius-slider-plugin.<version>.json`
  via `php flex/build-recipes.php` — see
  [FLEX_RECIPE.md](FLEX_RECIPE.md) for the full layout and the
  `.claude/scripts/flex-smoke.sh <branch>` verification helper (requires the
  branch to already be pushed, since the endpoint is served from
  `raw.githubusercontent.com`).
- Extension points for third-party code (settings fields, style presets,
  video providers, style-preset mockups, fixtures) are documented in
  [EXTENDING.md](EXTENDING.md).
