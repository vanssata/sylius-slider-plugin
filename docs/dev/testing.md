# Testing

The plugin has four test layers. Each one catches a different class of
failure, and they are ordered here by how fast they run. A problem that a
cheaper layer can catch must not be pushed to a more expensive one.

Everything runs in containers. This repository is developed on a host with
no PHP and no Node, so a bare `vendor/bin/phpunit`, `php`, `yarn` or `npx`
will not work. Use a `make` target, or one of:

```bash
docker compose run --rm php vendor/bin/phpunit
docker compose exec -T php vendor/bin/console cache:clear
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
```

The `nodejs` service already has `entrypoint: ["/bin/sh","-c"]`, so its
command is passed as a single string; wrapping it in another `sh -lc "..."`
silently does nothing useful.

The bootable kernel is `vendor/sylius/test-application` (see composer.json
`extra.public-dir`), not `tests/TestApplication` — the latter only
contributes `config/`, `templates/` and `src/` that are merged into it. Both
`phpunit.xml.dist` and `behat.yml` boot through
`vendor/sylius/test-application/config/bootstrap.php`, which additionally
loads `tests/TestApplication/.env` when it exists and redirects the cache and
log directories into the plugin's own `var/`.

## Which layer for what

| You changed | Reach for |
|---|---|
| A pure function, a value object, a settings merge | PHPUnit `tests/Unit` |
| A controller, a route, rendered server-side markup | PHPUnit `tests/Functional` |
| Admin CRUD, a domain rule, a storefront page without JS | Behat, non-JS |
| A form that only exists after JS boots the workspace | Behat `@javascript` |
| A Stimulus controller's runtime behaviour | Playwright |
| LiveComponent morphing, a modal re-parented to `<body>` | Playwright |
| A `<turbo-frame>` round-trip, Turbo Drive being on/off | Playwright |
| Per-viewport CSS, `@media` overrides, autoplay timing | Playwright |
| Types, style, dead code | PHPStan / ECS / Rector |

Do not port existing Behat coverage into Playwright. Two stacks covering the
same ground is a maintenance cost with no return: Behat owns the domain and
the CRUD, Playwright owns everything that needs a real browser engine.

## Layer 1 — static analysis and the verify loop

```bash
make verify        # ECS --fix, then PHPStan, then PHPUnit (APP_ENV=test)
make phpstan       # level max + baseline
make ecs           # Easy Coding Standard
make rector        # dry-run
make rector-fix    # apply
```

`make verify` runs `composer ai:verify` inside the `php` container with
`APP_ENV=test`. The composer script is:

```json
{
    "scripts": {
        "ai:verify": [
            "@php vendor/bin/ecs check --fix",
            "@php vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G",
            "@php vendor/bin/phpunit"
        ]
    }
}
```

There is no `composer ai:e2e`. Composer runs in the `php` container and
Playwright lives in a different one, so `make e2e` is the entry point for the
browser layer and it is deliberately not part of `make verify`.

PHPStan runs at `level: max` over `src` and `tests/Behat`
(`phpstan.neon`). `src/DependencyInjection/Configuration.php` is excluded
because it makes the analyser crash. Pre-existing findings live in
`phpstan-baseline.neon`; new code must analyse clean, so do not add entries
to the baseline to silence a fresh error.

ECS checks `src`, `tests/Behat` and `ecs.php` itself, importing
`vendor/sylius-labs/coding-standard/ecs.php`. Note the asymmetry: `make ecs`
runs `vendor/bin/ecs check src` (only `src`), while `composer ai:verify` and
CI run `vendor/bin/ecs check` over every configured path.

Rector targets PHP 8.3 across `src`, `tests/Behat`, `tests/Functional` and
`tests/Unit`. `AddOverrideAttributeToOverriddenMethodsRector` is skipped on
purpose — `#[\Override]` on a Sylius interface method would break the lower
bound of the supported Sylius range, because those interfaces evolve between
2.1 and 2.2.

## Layer 2 — PHPUnit

```bash
make phpunit                                        # every suite
docker compose run --rm -e APP_ENV=test php \
    vendor/bin/phpunit --testsuite=unit             # one suite
docker compose run --rm -e APP_ENV=test php \
    vendor/bin/phpunit --filter PreviewBreakpointFlattenerTest
```

`-e APP_ENV=test` is not optional. The `php` service sets
`APP_ENV: ${ENV:-prod}` (compose.override.yml) and PHPUnit's `<env>` element
does not overwrite a variable the process already has, so without the
explicit override the tests boot the wrong kernel environment. `make phpunit`
and `make verify` both pass it.

Five suites are declared in `phpunit.xml.dist`:

| Suite | Directories | Purpose |
|---|---|---|
| `all` | `tests` | default target |
| `unit` | `tests/Unit` | no kernel, no database |
| `functional` | `tests/Functional` | boots the kernel, hits the database |
| `integration` | `tests/Integration` | reserved; currently holds no tests |
| `non-unit` | `tests/Functional`, `tests/Integration` | what CI runs second |

`tests/Unit` mirrors the `src/` namespace layout (`Cloner`,
`DependencyInjection`, `Entity`, `Fixture`, `Form/Type`, `Menu`, `Preset`,
`Preview`, `Twig`, `Video`) and extends plain `PHPUnit\Framework\TestCase`:

```php
final class PreviewBreakpointFlattenerTest extends TestCase
{
    public function testSliderTabletOverlaysTopLevelKeys(): void
    {
        $flattened = (new PreviewBreakpointFlattener())->flattenSliderSettings(
            ['paginationStyle' => 'dots', 'responsive' => ['tablet' => ['paginationStyle' => 'lines']]],
            'tablet',
        );

        self::assertSame('lines', $flattened['paginationStyle']);
    }
}
```

`tests/Functional` extends
`Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase`, which wraps
`WebTestCase` and provides the four things every functional test here needs:
`entityManager()`, `logInAsAdmin()` (creates and logs in a `functional-admin`
user on the `admin` firewall), `ensureChannel()` (idempotent channel with
`localhost` as hostname, `USD`, `en_US`) and
`createSlider($code, $settings, $slideCount)` (idempotent slider plus slides).
Build the data you need per test rather than depending on the demo fixtures:

```php
final class SliderRenderingTest extends FunctionalTestCase
{
    public function testNonFirstSlideImagesAreLazyLoaded(): void
    {
        $this->ensureChannel();
        $this->createSlider('functional-lazy-slider', ['lazyLoadMedia' => true], 3);

        $crawler = $this->client->request('GET', '/slider/functional-lazy-slider');

        self::assertResponseIsSuccessful();
        $images = $crawler->filter('.vanssa-slide img.vanssa-slide__media');
        self::assertNull($images->eq(0)->attr('loading'), 'First slide image must load eagerly.');
        self::assertSame('lazy', $images->eq(1)->attr('loading'));
    }
}
```

Functional tests assert on rendered markup and on the JSON handed to the
Stimulus controller (`data-vanssa-slider-options-value`). They cannot assert
what the controller then *does* with it — that is the Playwright layer.

The functional suite needs a migrated database. Run `make database-init`
once, or `make database-reset` if the schema drifted.

## Layer 3 — Behat

```bash
make behat                                       # every suite
docker compose run --rm php \
    vendor/bin/behat --strict --tags='@slider_admin'
docker compose run --rm php \
    vendor/bin/behat --strict --tags='@slider_frontend'
```

`make behat` runs the container as root so Behat can write its debug
artifacts. `FriendsOfBehat\MinkDebugExtension` drops failure logs and
screenshots into `etc/build/` (which `.gitignore` keeps out of the
repository).

Features live in `features/admin/` and `features/shop/`; the step definitions
live in `tests/Behat/Context/Ui/{Admin,Shop}/` and are registered as public
services in `tests/Behat/Resources/services.xml`. The two suites are declared
in `tests/Behat/Resources/suites.yml` and selected by tag:

```yaml
default:
    suites:
        slider_admin:
            contexts:
                - vanssa_sylius_slider.context.ui.admin.slide_translations
                - vanssa_sylius_slider.context.ui.admin.slider_management
                - vanssa_sylius_slider.context.ui.admin.admin_browser
                - vanssa_sylius_slider.context.ui.admin.slider_editor_js
            filters:
                tags: "@slider_admin"

        slider_shop:
            contexts:
                - vanssa_sylius_slider.context.ui.shop.slider_frontend
            filters:
                tags: "@slider_frontend"
```

A new context class needs three edits or it will never run: the class in
`tests/Behat/Context/`, a `<service>` entry in
`tests/Behat/Resources/services.xml` (under `<defaults public="true" />`),
and its service id in the suite's `contexts` list.

Most contexts take the demo fixture as a constructor argument
(`Vanssa\SyliusSliderPlugin\Fixture\SliderDemoFixture`) and expose it as a
step, so a scenario seeds itself:

```gherkin
@slider_frontend
Feature: Rendering slider on storefront

    Scenario: Viewing the classic arrows slider
        Given slider demo fixtures are loaded
        When I visit the slider page for code "fashion-classic-arrows"
        Then I should see the storefront slider component
        And I should see slider text "New Collection"
```

### The two sessions

`behat.yml` configures Mink with `default_session: symfony` and
`javascript_session: chrome`:

- **non-JS scenarios** run through the `symfony` session: the kernel is
  booted in-process, no HTTP server and no browser are involved. This is the
  fast path and where most coverage belongs.
- **`@javascript` scenarios** run through the `chrome` session, which speaks
  CDP to `http://chrome:9222` — the `chrome` service in the compose stack
  (`zenika/alpine-chrome`, started by a plain `docker compose up -d`, port
  9222 published).

Two `@javascript` entry points exist today:
`features/admin/slider_editor_ux.feature` (whole file — the two-column
workspace, settings drawer, toolbar-driven locale/breakpoint editing) and one
scenario in `features/shop/slide_content_layout.feature`.

`behat.yml` is gitignored; Behat falls back to the committed `behat.yml.dist`
when it is absent. The only difference between the two is the CDP address:
`http://127.0.0.1:9222` in the `.dist`, `http://chrome:9222` for the
container stack. A `behat.yml` that predates the compose setup will still
point at `127.0.0.1` and every `@javascript` scenario will fail to reach a
browser.

The browser session also needs a real HTTP server, addressed by
`%env(BEHAT_BASE_URL)%`. The default in `tests/TestApplication/.env` is
`http://127.0.0.1:8080/`, which is only correct when the app and the browser
share a host — inside the compose stack the app is served by `nginx`. The
served app must also run in the same Symfony environment as the Behat
process: `behat.yml` pins the Behat kernel to `test`, so the fixtures a
scenario loads land in the test database, and a browser hitting an app served
in `dev` or `prod` would query a different one. The `php` service takes its
environment from `${ENV:-prod}` (compose.override.yml), so start the stack
with `ENV=test`:

```bash
ENV=test docker compose up -d php nginx chrome
docker compose run --rm -e APP_ENV=test -e BEHAT_BASE_URL=http://nginx \
    php vendor/bin/behat --strict --tags='@javascript'
```

Sylius resolves the shop channel from the request host, so the base URL's
hostname matters for shop scenarios. `SliderFrontendContext` handles this
inside the `slider demo fixtures are loaded` step: under the Chrome driver it
rewrites every channel's hostname to the host parsed out of `base_url`, and
under any other driver it puts `localhost` back — the test database is never
purged, so a previous `@javascript` run's hostname would otherwise stick and
break the non-JS scenarios.

Chrome keeps compiled bundles in memory. After any asset rebuild, restart it
before running a `@javascript` scenario:

```bash
docker compose restart chrome
```

## Layer 4 — Playwright

```bash
make e2e                                   # whole suite (starts the runner)
make e2e ARGS="tests/e2e/shop --project=desktop"
make e2e-check SPEC=tests/e2e/shop/slider-behavior.spec.ts
make docs-media                            # generators, not tests
make e2e-down                              # stop the runner when done
```

Specs live in `tests/e2e/`: `admin/`, `shop/`, shared helpers in `support/`,
and `docs/` for the screenshot and GIF generators (see
[docs-media.md](docs-media.md)).

Use Playwright only for what the other layers cannot reach:

- a Stimulus controller actually connecting and reacting (arrows, keyboard,
  pagination rebuild, video events);
- LiveComponent morphing, and any list that idiomorph re-renders while other
  code mutates the DOM;
- modals that `modal_portal_controller` re-parents to `<body>` on connect, so
  the element a test looks for is no longer where the template rendered it;
- `<turbo-frame>` round-trips, and proving Turbo Drive is *off* for admin
  navigation;
- per-viewport CSS: `@media` bands, per-slide breakpoint overrides;
- timing: autoplay advancing on its own, and — harder — *not* advancing.

### The runner

The `playwright` service sits behind the `e2e` compose profile, so a plain
`docker compose up -d` never starts it. `make e2e-up` starts it and installs
the Node dependencies on first use; `e2e`, `e2e-check` and `docs-media` all
depend on it, so you rarely call it directly.

```bash
docker compose --profile e2e up -d playwright
docker compose --profile e2e exec -T playwright npx playwright test
docker compose --profile e2e rm -sf playwright
```

Use `exec`, never `docker compose run -d` — a `run` with a never-exiting
command leaks an orphan container per session.

`network_mode: host` on that service is load-bearing, not a convenience.
Every Sylius channel in this project has hostname `localhost`; from inside
the compose network the app is only reachable as `http://nginx`, which
resolves to no channel and 404s on every shop page. Host networking makes
`http://localhost` hit the published nginx port so the channel resolves. This
is Linux-specific; on macOS or Windows swap it for `extra_hosts` plus
`http://host.docker.internal`. There is deliberately no `webServer` block in
`playwright.config.ts` — the docker stack *is* the server, and
`symfony server:start` is not an option on a host with no PHP.

The e2e dependencies live in the **root** `package.json`
(`@playwright/test`, `@axe-core/playwright`), which is separate from
`assets/package.json` — the latter is the published Symfony UX package
manifest and must not gain test dependencies. The image tag in
`docker/playwright/Dockerfile` must track the pinned `@playwright/test`
version; drift between the bundled browsers and the test library is the
classic "works locally, fails in the container" failure.

Generated output (`test-results/`, `playwright-report/`, `blob-report/`,
`playwright/.cache/`) is gitignored. Specs and snapshot baselines are not.

### The three projects

`playwright.config.ts` declares four projects:

| Project | Viewport | Matches |
|---|---|---|
| `desktop` | 1400x900 | everything except `docs/**` |
| `tablet` | 820x1180 | `shop/**/*.spec.ts` only |
| `mobile` | 390x844 | `shop/**/*.spec.ts` only |
| `docs-media` | 1600x950 | `tests/e2e/docs` only, excluded from `make e2e` |

The admin workspace is a desktop tool, so only storefront specs run at all
three breakpoints. The two smaller viewports are not arbitrary: 820px sits
inside the `@media (max-width: 1024px)` tablet band and 390px inside the
`@media (max-width: 767px)` mobile band of
`templates/components/vanssa_sylius_slider/shop/slide.html.twig`. The same
numbers are mirrored in `tests/e2e/support/data.ts` as `VIEWPORTS`.

A shop spec is written once and derives its expectations from
`page.viewportSize()`, so all three projects share it:

```ts
function currentBreakpoint(page: Page): Breakpoint {
    const viewport = page.viewportSize();
    expect(viewport, 'every project in playwright.config.ts pins a viewport').not.toBeNull();

    if (viewport!.width <= MOBILE_MAX_WIDTH) {
        return 'mobile';
    }

    return viewport!.width <= TABLET_MAX_WIDTH ? 'tablet' : 'desktop';
}
```

`tests/e2e/shop/responsive-overrides.spec.ts` also asserts that the browser
agrees with the breakpoint its project is meant to exercise. Keep that guard:
if the config's viewports and the template's media bands ever drift apart,
every other expectation in the file would quietly assert the wrong
breakpoint.

Determinism settings shared by all projects: `locale: 'en-US'`,
`timezoneId: 'UTC'`, `colorScheme: 'light'`, 60s test timeout, 10s expect
timeout, `trace: 'on-first-retry'`, `screenshot: 'only-on-failure'`. ARIA
snapshots are text- and order-sensitive and Sylius is multi-locale by design,
so none of those pins are cosmetic.

### The fixture-order trap

```ts
fullyParallel: false,
workers: 1,
```

This is not a performance oversight. The demo fixtures are a **single shared
dataset** and several specs mutate it — drag reordering slides, saving a
slide from the modal. Parallel workers would race on the same rows and
produce failures that do not reproduce in isolation. Do not raise `workers`
to speed a run up.

Two consequences for spec authors:

- Never hard-code a database id. Ids are not stable across a
  `make load-slider-fixtures`. Use the helpers in `tests/e2e/support/admin.ts`
  (`sliderIdByCode`, `slideIdByCode`) that resolve an id from the grid by
  code.
- A spec that dirties the dataset must be tolerable to the specs after it, or
  reset it. If a run leaves the data in a bad shape, reload:

```bash
make load-slider-fixtures
```

The fixture data contract is centralised in `tests/e2e/support/data.ts` —
slider codes, slide codes, admin credentials, viewports and route builders.
Import from it instead of repeating literals:

```ts
import { SLIDERS, SLIDES, routes } from '../support/data';

await page.goto(routes.shopSlider(SLIDERS.classicArrows));
```

### Support helpers

`tests/e2e/support/` holds the three shared pieces:

- `data.ts` — the fixture contract described above.
- `admin.ts` — `loginAsAdmin(page)`, `sliderIdByCode(page, code)`,
  `slideIdByCode(page, code)`. The login helper is the **only** place in the
  suite allowed to use id selectors (`#_username` / `#_password`): the Sylius
  admin login form has no reliable label wiring. Everything after login uses
  role and accessible name.
- `a11y.ts` — `expectNoA11yViolations(page, include, { disableRules })`.

### ARIA snapshots over CSS selectors

Assert against the accessibility tree, not Bootstrap class names. Class names
churn between Bootstrap and Sylius minor versions, so selector-based tests
break on `composer update` while the page is still correct. Role and
accessible name are far more stable, and a snapshot that is hard to write is
usually telling you the markup has no accessible names.

```ts
await expect(page.locator('.vanssa-slider__controls')).toMatchAriaSnapshot(`
    - button "Previous slide"
    - button "Next slide"
`);
```

Never write a snapshot from imagination. The accessible name Playwright
computes often differs from the visible text when `aria-label`, `alt` or
`aria-labelledby` are in play, and a hand-written snapshot encodes the wrong
one. Upstream Playwright suggests seeding from `playwright codegen`, but that
needs a headed browser and the runner here is a headless container — print
the real tree from a throwaway spec instead, then paste it:

```ts
test('seed', async ({ page }) => {
    await page.goto(routes.shopSlider(SLIDERS.classicArrows));
    console.log(await page.locator('.vanssa-slider__controls').ariaSnapshot());
});
```

Use **inline** snapshots for small regions where seeing the tree next to the
action is the point:

```ts
await expect(page.locator(`.vanssa-slide[data-slide-code="new-collection"]`)).toMatchAriaSnapshot(`
    - article:
      - img "New Collection"
      - heading "New Collection" [level=3]
      - text: Fresh looks for the season — dresses, denim and everyday essentials.
`);
```

Use an **external** baseline for large trees — admin grids, the full preview
panel — where a YAML diff reviews better than a wall of template literal:

```ts
await expect(page.getByRole('main')).toMatchAriaSnapshot({ name: 'slider-grid.aria.yml' });
```

External baselines land in a `<spec-file>-snapshots/` directory next to the
spec. Commit them.

Dynamic text (ids, dates, counts) goes through a regex rather than a literal,
and the scope stays tight:

```ts
await expect(row).toMatchAriaSnapshot(`
    - cell:
      - link:
        - /url: /\\/admin\\/sliders\\/\\d+\\/edit/
`);
```

CSS selectors are still correct where no role can express the subject. The
accepted hooks in this suite, each with a reason:

| Selector | Why not a role |
|---|---|
| `[data-controller~="vanssa-slider"]` | the assertion *is* "this controller is mounted here" |
| `.vanssa-slide[data-slide-code="..."]` | slides expose no accessible name; this is also what the per-slide `<style>` block scopes on |
| `.vanssa-slide.is-active` | controller state class, not mirrored into ARIA |
| `[data-vanssa-slider-target="liveUpdate"]` | visually-hidden live region |
| `#vanssa-slider-preview-frame-{id}` | `<turbo-frame>` has no role and no name |
| `#vanssa-slide-preview-modal-{id}` | the grid's edit action targets it by id |
| `#_username`, `#_password` | admin login form, see above |

### Never regenerate a baseline to make a test pass

```bash
# The maintainer's command. Not an agent's, and not a way out of a red test.
docker compose --profile e2e exec -T playwright \
    npx playwright test --update-snapshots=changed --update-source-method=3way
```

`changed` touches only genuinely differing snapshots; `all` rewrites
everything and destroys the signal. If a snapshot fails, report the diff and
decide whether the change was intended — an updated baseline is a decision,
not a fix. Commit updated baselines in the same change that justified them,
otherwise the next machine to run the suite compares fresh markup against
stale committed YAML.

### Accessibility scans

ARIA snapshots verify that the structure you expect is present and stable.
They do not check colour contrast, invalid ARIA or duplicate ids. Pair them
with axe in the same test.

Scans are **scoped to the plugin's own markup**. The surrounding Sylius
admin and shop chrome carries pre-existing violations this plugin cannot fix;
a page-wide scan would fail on them and teach everyone to ignore the check.

```ts
import { expectNoA11yViolations } from '../support/a11y';

await expectNoA11yViolations(page, '[data-controller~="vanssa-slider"]');
```

The helper runs `wcag2a`, `wcag2aa`, `wcag21a` and `wcag21aa` and asserts an
empty violation list. Rules may be switched off only for markup the plugin
genuinely violates today, one call at a time, with a comment naming each
rule's cause and the intention to remove it:

```ts
await expectNoA11yViolations(page, '.vanssa-preview-panel', {
    disableRules: ['aria-required-children', 'listitem', 'color-contrast'],
});
```

Rescan after a state change when the state change rewrites ARIA bookkeeping —
the slider rewrites `aria-hidden` on every transition, so the scan runs
before and after advancing a slide.

### The fast loop while editing SCSS or JS

```bash
make node-watch                                              # start the watcher
make e2e-check SPEC=tests/e2e/shop/responsive-overrides.spec.ts
make node-watch-stop                                         # when the task ends
```

`make e2e-check` blocks until the compiled bundles under
`vendor/sylius/test-application/public/build` are newer than the newest file
in `assets/` — that is, until the watcher has caught up — then runs that one
spec on desktop, tablet and mobile with the list reporter. It gives up
waiting after 180s and prints a warning; that warning almost always means the
watcher is not running.

Two things a running watcher does not handle:

- **Manifest edits need a watcher restart.** `controllers.json` files and
  `assets/package.json` are merged into `var/cache/webpack/` when webpack's
  config loads and never again, so the watcher keeps building the old
  controller set, silently.
- **Chrome caches bundles in memory.** That applies to the `chrome` service
  used by Behat; restart it with `docker compose restart chrome` before a
  `@javascript` run.

## What CI runs

`.github/workflows/build.yaml` (workflow name `Build`) builds the Sylius test
application against Sylius `~2.1.0` and `~2.2.0` and then runs, in order:

```bash
vendor/bin/phpunit --colors=always --testsuite=unit
composer validate --ansi --strict
vendor/bin/ecs check
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/console lint:container
vendor/bin/phpunit --colors=always --testsuite=non-unit
vendor/bin/behat --colors --strict -vvv --no-interaction -f progress
```

The Behat step retries once with `--rerun` before failing, and Behat logs are
uploaded as an artifact on failure. `make run-github-tests` runs the same
sequence locally, in the container.

CI does **not** run the Playwright suite. `playwright.config.ts` already
carries the CI branches (`forbidOnly`, 2 retries, the `github` reporter), but
until the workflow gains an e2e job, `make e2e` is a local gate — run it
before a release, and after any change to a Stimulus controller, a Twig
component template or the shop SCSS.

## Related

- [docs-media.md](docs-media.md) — the `docs-media` Playwright project that
  regenerates every screenshot and GIF under `docs/`.
- [contributing.md](contributing.md) — environment setup, asset workflow,
  pull request rules.
- [architecture.md](architecture.md) — what the code under test is made of.
