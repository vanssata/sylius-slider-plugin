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

Docker's `make init` runs this same install+build. For ongoing plugin
development, don't rerun `yarn build` after every edit — use the watcher
described in [Frontend asset workflow](#frontend-asset-workflow-docker).

Optional demo data:

```bash
vendor/bin/console sylius:fixtures:load --suite=vanssa_sylius_slider_demo -n
```

## Frontend asset workflow (Docker)

A one-off build is fine for first-time setup or a clean-room check, but not
for iterating on Stimulus controllers or SCSS. For day-to-day plugin
development, start the long-lived watcher instead:

```bash
make node-watch          # start (or: docker compose --profile watch up -d nodejs-watch)
make node-watch-logs     # follow build output
make node-watch-stop     # stop when done
```

It's gated behind the `watch` Compose profile, so a plain `docker compose up
-d` / `make up` never starts it. On first start it replaces the yarn-classic
copy at `vendor/sylius/test-application/node_modules/@vanssa/sylius-slider-plugin`
with a symlink to the real `assets/` tree — this is what makes Stimulus
controller edits reach the watcher, and it retires the old `yarn install
--force` refresh step. `yarn watch` is `encore dev --watch`, the same
`encore dev` build as `yarn build` (only `build:prod` is `encore
production`), so the watcher produces the identical dev bundle a one-off
build would.

Two things it does **not** pick up automatically:

- **Manifest edits need a watcher restart.** The plugin's three
  `controllers.json` files and `assets/package.json` are merged into
  `var/cache/webpack/controllers.merged.*.json` only when webpack's config
  loads. Editing a manifest while the watcher is running silently leaves it
  building the old controller set — restart it (`make node-watch-stop &&
  make node-watch`).
- **Chrome caches bundles in memory.** Run `docker compose restart chrome`
  before re-verifying with Playwright or the `@javascript` Behat tag (see
  below).

The symlink survives a plain `yarn install` — `docker compose up -d`, `make
node-build`, and `composer run frontend-clear` all run it without `--force`
and won't undo the swap. If file changes never reach the watcher (e.g. some
bind-mount setups miss inotify events), recreate it with polling enabled —
the env var is only read when the container is created, so stop it first:
`make node-watch-stop && WATCHPACK_POLLING=true make node-watch`.

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
bundle in memory. When the watcher is running, also make sure it has
finished recompiling first, and remember that manifest edits need a watcher
restart — see [Frontend asset workflow](#frontend-asset-workflow-docker).

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
