# Contributing

Everything in this repository runs in containers. The development host is not
expected to have PHP, Node, Composer or Yarn — every command below is either a
`make` target or an explicit `docker compose` invocation.

- [Bootstrap](#bootstrap)
- [What the app actually is](#what-the-app-actually-is)
- [Running commands](#running-commands)
- [Frontend assets](#frontend-assets)
- [Quality checks](#quality-checks)
- [Behat](#behat)
- [Playwright end-to-end tests](#playwright-end-to-end-tests)
- [Documentation media](#documentation-media)
- [Continuous integration](#continuous-integration)
- [Pull requests](#pull-requests)
- [Releasing](#releasing)

## Bootstrap

```bash
make init            # compose.override.yml, composer install, yarn install+build, docker compose up -d
make database-init   # doctrine:database:create --if-not-exists + doctrine:migrations:migrate
make load-fixtures   # Sylius' own default fixture suite
```

`make init` copies `compose.override.dist.yml` to `compose.override.yml` if you
do not have one, then runs `composer install --no-interaction --no-scripts
--no-plugins` in the `php` container, the one-shot `nodejs` container (which
does `yarn install && yarn build` inside `vendor/sylius/test-application`), and
finally `docker compose up -d`.

The demo data used by the screenshots, the Behat storefront suite and the
Playwright specs is a separate suite:

```bash
make load-slider-fixtures
# = docker compose run --rm php vendor/bin/console sylius:fixtures:load vanssa_sylius_slider_demo -n
```

It creates the slides `new-collection`, `summer-dresses`, `denim-essentials`,
`graphic-tees`, `street-caps`, `season-sale`, `runway-video` and the sliders
`fashion-classic-arrows`, `fashion-minimal-fade`, `fashion-autoplay-showcase`,
`fashion-fullscreen-hero`, `fashion-compact-banner`,
`fashion-parallax-showcase`. The fixture is idempotent — re-running it updates
those codes instead of duplicating them.

Other lifecycle targets: `make up`, `make down`, `make clean` (down `-v`, drops
the database volume), `make database-reset` (drop + create + migrate),
`make cc`, `make mig`, `make php-shell`, `make node-shell`.

Storefront and admin are served by the `nginx` container on
`http://localhost` (`/admin` for the backend). Mailhog is on
`http://localhost:8025`.

## What the app actually is

The bootable kernel is **`vendor/sylius/test-application`**. It is what
`composer.json`'s `extra.public-dir`, `phpunit.xml.dist`'s `KERNEL_CLASS`
(`Sylius\TestApplication\Kernel`), `behat.yml.dist` and the nginx
`WORKING_DIR` all point at.

`tests/TestApplication/` is **not** an application. It contributes
configuration, templates and `src/Entity` that are merged into that kernel —
`tests/TestApplication/config/config.yaml` imports
`@VanssaSyliusSliderPlugin/config/config.yaml`, adds a Twig path and registers
the homepage hook that renders `fashion-classic-arrows` on the storefront home
page. Editing files there is how you change the dev app; running a console
command against that directory is not possible.

Database credentials live in `tests/TestApplication/.env` and
`tests/TestApplication/.env.test`; the container overrides `DATABASE_URL` in
`compose.override.yml`.

## Running commands

```bash
# console (the binary is vendor/bin/console, not bin/console)
docker compose run --rm php vendor/bin/console debug:router | grep vanssa
docker compose exec -T php vendor/bin/console cache:clear

# anything Node — the nodejs service's entrypoint is already ["/bin/sh","-c"],
# so pass ONE string, never `sh -lc "..."`
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
```

`compose.override.yml` sets `APP_ENV: ${ENV:-prod}` on the `php` service, and
every `make` target exports `ENV=dev` — so a target runs in `dev`, while a bare
`docker compose` call with no `ENV` in the environment runs in **prod**. Pass
the environment explicitly whenever it matters:

```bash
docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit
ENV=test docker compose up -d php nginx
```

## Frontend assets

Use the watcher, not repeated one-off builds:

```bash
make node-watch        # docker compose --profile watch up -d nodejs-watch
make node-watch-logs   # follow the compile output
make node-watch-stop   # docker compose --profile watch rm -sf nodejs-watch
```

The service is behind the `watch` Compose profile, so `make up` never starts
it — and nothing stops it either. Stopping it is part of finishing a task.

On first start it replaces
`vendor/sylius/test-application/node_modules/@vanssa/sylius-slider-plugin` with
a symlink to the real `assets/` tree. That is load-bearing: yarn classic
*copies* a `file:` dependency, and all 14 Stimulus controllers resolve through
that copy, so without the symlink a controller edit is silently ignored while
entrypoints and SCSS (which webpack references by path) do update. The symlink
survives a plain `yarn install`, so `make up`, `make node-build` and
`composer run frontend-clear` do not undo it.

In `vendor/sylius/test-application/package.json`, `build` is `encore dev` and
`watch` is `encore dev --watch` (only `build:prod` is `encore production`), so
the watcher produces exactly the bundle a one-off `yarn build` would.

Two things the watcher does not handle:

- **Manifest changes need a restart.** `webpack.config.js` merges the
  `controllers.json` files into `var/cache/webpack/controllers.merged.*.json`
  while the config loads, once per process. Edit any manifest — one of the
  plugin's three `controllers.json` files or `assets/package.json` — while the
  watcher runs and it keeps building the old controller set without saying so:
  `make node-watch-stop && make node-watch`.
- **Chrome caches bundles in memory.** Run `docker compose restart chrome`
  before re-checking anything in a browser or with the `@javascript` Behat tag.

If file events never reach the watcher (some bind-mount setups miss inotify),
recreate it with polling. The variable is read at container creation, so it has
to be stopped first:

```bash
make node-watch-stop && WATCHPACK_POLLING=true make node-watch
```

Clean-room build — for first-time setup, or to rule out a stale yarn copy as
the cause of a bug:

```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force"
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
docker compose restart chrome
```

Stop the watcher first. `make node-build`, `yarn build` and the watcher all
write `vendor/sylius/test-application/public/build`, and Encore cleans that
directory before every build (`cleanupOutputBeforeBuild()`).

## Quality checks

Run this before every push:

```bash
make verify
```

It is `docker compose run --rm -e APP_ENV=test php composer ai:verify`, which
runs, in order:

```bash
vendor/bin/ecs check --fix
vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G
vendor/bin/phpunit
```

Note that it **rewrites files** (`ecs check --fix`). Check `git diff` after it
passes.

Individual targets:

```bash
make ecs           # vendor/bin/ecs check src
make phpstan       # vendor/bin/phpstan analyse -c phpstan.neon
make phpunit       # APP_ENV=test vendor/bin/phpunit
make rector        # vendor/bin/rector process --dry-run
make rector-fix    # vendor/bin/rector process
make behat         # vendor/bin/behat, container user forced to root
```

`make ecs` narrows the scan to `src`. CI runs plain `vendor/bin/ecs check`,
which covers every path in `ecs.php` (`src`, `tests/Behat`, `ecs.php` itself) —
so a style violation in `tests/Behat` passes locally and fails in CI. Use the
full form when you touched Behat contexts:

```bash
docker compose run --rm php vendor/bin/ecs check
```

PHPStan runs at `level: max` over `src` and `tests/Behat`, with
`src/DependencyInjection/Configuration.php` excluded (it crashes the analyser)
and a baseline in `phpstan-baseline.neon` for pre-existing findings. New code
must analyse clean — do not add entries to the baseline.

PHPUnit test suites (`phpunit.xml.dist`): `all`, `unit` (`tests/Unit`),
`functional` (`tests/Functional`), `integration` (`tests/Integration`) and
`non-unit` (functional + integration). The non-unit suites need the test
database:

```bash
docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit --testsuite=unit
docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit --testsuite=non-unit
```

`phpunit.xml.dist` declares `<env name="APP_ENV" value="test"/>` without
`force`, so it does not override an `APP_ENV` that the container already
exports. Always pass `-e APP_ENV=test` (that is what `make phpunit` and
`make verify` do) — otherwise the kernel boots in whatever `ENV` resolved to.

Rector is configured for PHP 8.3 over `src`, `tests/Behat`, `tests/Functional`
and `tests/Unit`. It deliberately skips
`AddOverrideAttributeToOverriddenMethodsRector` — `#[\Override]` would break
the Sylius 2.1 lower bound, where some interfaces still differ — and
`src/Migrations`.

## Behat

Two suites are defined in `tests/Behat/Resources/suites.yml`:

| Suite | Tag | Features |
| --- | --- | --- |
| `slider_admin` | `@slider_admin` | `features/admin/*.feature` |
| `slider_shop` | `@slider_frontend` | `features/shop/*.feature` |

```bash
docker compose run --rm php vendor/bin/behat --strict --tags='@slider_admin'
docker compose run --rm php vendor/bin/behat --strict --tags='@slider_frontend'

# everything except the browser scenarios
docker compose run --rm php vendor/bin/behat --strict --tags='~@javascript'
```

### Browser (`@javascript`) scenarios

`features/admin/slider_editor_ux.feature` is tagged `@slider_admin @javascript`
and drives a real headless Chrome over CDP: settings drawer, toolbar-driven
locale/breakpoint editing, live draft preview, preset try-on, fullscreen,
scale-to-fit.

`behat.yml` is gitignored — copy it from `behat.yml.dist` and point the
`chrome` session at the container instead of `127.0.0.1`:

```yaml
# behat.yml
                chrome:
                    chrome:
                        api_url: http://chrome:9222
                        validate_certificate: false
```

Then serve the app in the test environment and run against it:

```bash
ENV=test docker compose up -d php nginx
docker compose run --rm -e APP_ENV=test -e BEHAT_BASE_URL=http://nginx \
    php vendor/bin/behat --strict --tags='@javascript'
```

If a browser scenario fails on markup you know you fixed, the `chrome`
container is holding a stale bundle: `docker compose restart chrome`. When the
watcher is running, also wait for its recompile to appear in
`make node-watch-logs` first — and remember that a manifest edit needs a
watcher restart, not just a wait.

Failure screenshots and logs land in `etc/build/`
(`FriendsOfBehat\MinkDebugExtension`, `screenshot: true`). `make behat` runs
the container with `DOCKER_USER=root`.

## Playwright end-to-end tests

The Playwright runner is a profiled compose service (`playwright`, profile
`e2e`). Specs live in `tests/e2e/`.

```bash
make e2e                                                  # whole suite
make e2e ARGS="tests/e2e/shop/slider-behavior.spec.ts"    # one file
make e2e-check SPEC=tests/e2e/shop/responsive-overrides.spec.ts
make e2e-down                                             # remove the runner
```

`make e2e-check` is the fast loop while editing SCSS/JS: it blocks until the
compiled bundles under `vendor/sylius/test-application/public/build` are newer
than the newest file in `assets/` (i.e. the watcher caught up, giving up after
180 s with a warning), then runs that one spec on the `desktop`, `tablet` and
`mobile` projects.

Configuration notes from `playwright.config.ts` that will bite you otherwise:

- `BASE_URL` defaults to `http://localhost` and the service uses
  `network_mode: host`. Every Sylius channel here has hostname `localhost`, and
  Sylius resolves the channel from the request host — from inside the compose
  network the app is only reachable as `http://nginx`, which matches no channel
  and 404s on every shop page.
- `fullyParallel: false`, `workers: 1`. The specs share one fixture dataset and
  several of them mutate it (drag reorder, saving a slide).
- Projects: `desktop` (1400x900), `tablet` (820x1180) and `mobile` (390x844).
  Only `shop/**/*.spec.ts` runs on tablet and mobile; the admin workspace is a
  desktop tool. The viewports are chosen to sit inside the template's
  `max-width: 1024px` and `max-width: 767px` bands.
- There is no `webServer` block. The docker stack is the server, so
  `make up` (and loaded fixtures) is a precondition.

## Documentation media

Every screenshot under `docs/screenshots/` and every GIF under `docs/media/` is
generated by the `docs-media` Playwright project:

```bash
make docs-media
```

It is excluded from `make e2e` on purpose — those specs are generators, not
assertions. Regenerate them whenever a change alters what the admin or the
storefront looks like, and commit the regenerated files with the change. The
capture list, the determinism rules and how to add a new capture are in
[docs-media.md](docs-media.md).

## Continuous integration

`.github/workflows/build.yaml`, workflow name **Build**. It builds the Sylius
test application through `SyliusLabs/BuildTestAppAction@v4` with `e2e_js: yes`,
then runs, in order:

```text
vendor/bin/phpunit --testsuite=unit
composer validate --strict
vendor/bin/ecs check
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/console lint:container
vendor/bin/phpunit --testsuite=non-unit
vendor/bin/behat --strict            (retried once with --rerun)
```

Matrix: PHP 8.3, Symfony `^7.4`, Node 22.x, Sylius `~2.1.0` and `~2.2.0` on
MySQL 8.4, plus Sylius `~2.2.0` on MySQL 8.0 and MariaDB 11.4. `make
run-github-tests` runs the same sequence locally in the `php` container.

Playwright is not part of CI — `make e2e` is a local gate.

## Pull requests

- Keep changes focused and atomic; one task per commit.
- Add or update tests for behaviour changes: PHPUnit for units, Behat for admin
  and storefront flows, Playwright for anything that only shows up in a real
  browser at a specific viewport.
- Update the documentation the change affects — `README.md`, `CHANGELOG.md`,
  `docs/usage/` for admin-facing behaviour, `docs/dev/` for extension points.
- Evolve JSON settings backwards-compatibly: read with an explicit fallback,
  normalize legacy shapes in the form type's event listeners, never rename a
  key without keeping the old one readable. See
  [extending.md](extending.md#evolving-the-settings-schema).
- Compose admin UI through Twig Hooks (`config/twig_hooks/admin/*.yaml`), not by
  editing vendor templates.
- Keep fixture media license-safe and documented in
  `assets/fixtures/LICENSE.md`.
- When you add, rename or remove a Stimulus controller, update all four
  manifests — `assets/package.json`, `assets/controllers.json`,
  `assets/admin/controllers.json`, `assets/shop/controllers.json` — with the
  identical key set, and follow
  [adding-a-stimulus-controller.md](adding-a-stimulus-controller.md). The
  per-context files are shallow-merged per package, so omitting a controller
  from one of them drops it from that build instead of falling back to a
  default:

  ```bash
  for f in assets/controllers.json assets/admin/controllers.json assets/shop/controllers.json; do
    diff <(jq -r '.symfony.controllers | keys[]' assets/package.json) \
         <(jq -r '.controllers["@vanssa/sylius-slider-plugin"] | keys[]' "$f") >/dev/null \
      && echo "$f: in sync" || echo "$f: OUT OF SYNC"
  done
  ```

Before pushing: `make verify`. Before a release: `make e2e` as well.

## Releasing

1. `make verify` and `make e2e` both green.
2. Regenerate the docs media if the UI changed (`make docs-media`) and commit
   the result.
3. Update `CHANGELOG.md` (Keep a Changelog format) and cut the release section.
4. If the Flex recipe changed, edit the human-readable sources under
   `flex/recipes/vanssa/sylius-slider-plugin/<version>/` and rebuild the
   archived JSON — in the container, since the host has no PHP:

   ```bash
   docker compose run --rm php php flex/build-recipes.php
   ```

   Never hand-edit `flex/vanssa.sylius-slider-plugin.<version>.json`; it is
   generated. See [../FLEX_RECIPE.md](../FLEX_RECIPE.md) for the endpoint
   layout and the pre-release smoke test that proves the recipe applies to a
   clean project — that test needs the branch pushed first, because the
   endpoint is served from `raw.githubusercontent.com`.
5. Tag the release and push the tag. The `Build` workflow also runs on
   `release: created`, so the tag is covered by the same matrix.

Extension points third-party code depends on — settings fields, style presets,
video providers, preset mockups, fixtures, Stimulus identifiers — are
documented in [extending.md](extending.md). Treat a change to any of them as a
breaking change and say so in the changelog.

## See also

- [architecture.md](architecture.md) — what lives where and why.
- [testing.md](testing.md) — choosing between PHPUnit, Behat and Playwright.
- [docs-media.md](docs-media.md) — the screenshot and GIF pipeline.
- [extending.md](extending.md) — the public extension points.
- [adding-a-stimulus-controller.md](adding-a-stimulus-controller.md) — the
  manifest rules in full.
