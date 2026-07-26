# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

> **Everything runs in containers.** This machine has no PHP and no Node
> installed, so a bare `php` / `composer` / `vendor/bin/*` / `yarn` / `npx`
> command fails (and `.claude/hooks/container-guard.sh` denies it with the
> right replacement). Use a `make` target, `docker compose run --rm php …`,
> `docker compose exec -T php …`, or `docker compose run --rm nodejs "<one
> string>"`.

### Environment
```bash
make init             # build images, install deps, create compose.override.yml
make up / make down   # start / stop the stack (nginx on http://localhost)
make clean            # down -v

make database-init    # create the DB and run migrations
make database-reset   # drop + create + migrate
make load-fixtures            # full Sylius fixtures
make load-slider-fixtures     # vanssa_sylius_slider_demo only

make php-shell / make node-shell
make cc / make mig
```

The stack runs with `APP_ENV=${ENV:-prod}` (see `compose.override.dist.yml`),
so by default there is **no web debug toolbar** — which is what the docs-media
generators rely on. Start with `ENV=dev make up` when you want the profiler.

### Testing
```bash
make verify        # the fast loop: ECS --fix, PHPStan, PHPUnit (APP_ENV=test)
make phpunit       # PHPUnit only, APP_ENV=test
make behat         # Behat; the @javascript leg drives the `chrome` service
make e2e           # Playwright suite (tests/e2e) in the `playwright` service
make e2e-check SPEC=tests/e2e/shop/slider-behavior.spec.ts   # one spec, 3 viewports
make e2e-down      # stop the Playwright service when the task ends
```

`make verify` is `composer ai:verify` inside the container — that is the
command the `sylius-quality` skill means when it says "run `composer
ai:verify`". There is no `composer ai:e2e`: composer runs in the `php`
container and Playwright lives in a different one, so `make e2e` is the entry
point. See `docs/dev/testing.md` for when to reach for Behat vs Playwright.

### Code Quality
```bash
make phpstan       # level max + baseline (phpstan.neon)
make ecs           # Easy Coding Standard
make rector        # dry-run
make rector-fix    # apply
```

### Docker Compose command quoting

`docker compose run --rm nodejs <cmd>` — the `nodejs` service already has `entrypoint: ["/bin/sh","-c"]`, so passing `sh -lc "..."` as `<cmd>` double-wraps it and the command silently does nothing useful. Pass the raw shell command as a single string argument instead:

```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
```

### Composer Scripts
```bash
# Database reset with fixtures
composer run database-reset

# Frontend rebuild
composer run frontend-clear

# Complete test app initialization
composer run test-app-init
```

## Architecture

A Sylius 2.x plugin for storefront sliders and banners. Full map:
`docs/dev/architecture.md`. The essentials:

- **Plugin class**: `src/VanssaSyliusSliderPlugin.php` (`SyliusPluginTrait`)
- **DI extension**: `src/DependencyInjection/VanssaSyliusSliderExtension.php` —
  service loading and Doctrine migration namespace
- **Services**: `config/services.xml`; **routes**: `config/routes/{admin,shop}.yaml`
- **Templates**: `templates/` — admin workspace, Twig components, Twig hooks
- **Migrations**: `src/Migrations/` (not `tests/`)

**The bootable kernel is `vendor/sylius/test-application`**, not
`tests/TestApplication`. `tests/TestApplication/` only contributes
config/templates/src that are merged into it, and `composer.json`
`extra.public-dir` points at the vendor path. Any tooling that assumes
`tests/TestApplication/public` is a document root is wrong here — that includes
the `sylius-dev` skill and `sylius-quality`'s stock Playwright config, both of
which are written for an application layout rather than a plugin one.

The console binary is `vendor/bin/console`; the PHP namespace is
`Vanssa\SyliusSliderPlugin\`.

### Database Configuration
Database credentials should be configured in:
- `tests/TestApplication/.env` (for development)
- `tests/TestApplication/.env.test` (for testing)

The compose stack overrides `DATABASE_URL` per environment
(`mysql://root@mysql/sylius_%kernel.environment%`), so `sylius_dev`,
`sylius_test` and `sylius_prod` are three separate databases. A test run
without `APP_ENV=test` hits the wrong one — `make phpunit` and `make verify`
set it for you.

## AI tooling

`.claude/`, `mcp.json` / `.mcp.json`, `mate/` and `CLAUDE.local.md` are
**gitignored local tooling**: functional on disk, absent from the repository.
Nothing below is required to build or test the plugin.

| Layer | What it is | Entry point |
|---|---|---|
| `symfony-ai-mate` MCP | Introspects the running Sylius kernel (`sylius/sylius-ai-dev-tools`) | `.claude/scripts/mate-mcp.sh`, referenced from `mcp.json` |
| `playwright` MCP | Browser driving inside the `playwright` container | `.claude/scripts/playwright-mcp.sh` |
| `sylius-dev` skill | Official Sylius skill (plugin `sylius-dev@sylius-ai-dev-skills`) | enabled in `.claude/settings.json` |
| `symfony-ux-skills` | The seven Symfony UX skills (stimulus, turbo, twig-component, live-component, ux-icons, ux-map, symfony-ux) | enabled at user scope |
| `sylius-quality` | Local skill + `sylius-reviewer` / `sylius-bc-guard` / `sylius-e2e-author` agents | `.claude/skills`, `.claude/agents` |
| Guard hooks | `vendor-guard`, `container-guard`, `bash-guard`, `assets-guard` | `.claude/hooks/`, wired in `.claude/settings.json` |
| Frontend mate tools | Local mate extension: `frontend_map` (grouped index + build-integrity checks) and `frontend_read` (grouped file bodies) over `assets/`, `templates/`, `src/Twig/`, `config/twig_hooks/`, `tests/e2e/` | `mate/src/`, registered in `mate/config.php`; docs in `mate/INSTRUCTIONS.md` |

Both MCP launchers `docker compose exec` into an already-running service. Never
start them with `docker compose run` and never pass `mate serve
--force-keep-alive`: that combination creates a container per session and keeps
the process alive after the client closes stdin, which is what once leaked 22
`syliusslider-php-run-*` containers.

Regenerate the mate tree with `make mate-init` / `make mate-discover`.

## Stimulus Controller Manifests (Important Gotchas)

The plugin is a proper Symfony UX package now: controllers register **only**
through the `@symfony/stimulus-bridge` manifest, inside the consuming app's own
`startStimulusApp()`. When adding, renaming, or removing a Stimulus controller
in `assets/admin/controllers/` or `assets/shop/controllers/`, **four** places
must stay in sync (identical key sets, all 14 controllers), plus a **fifth**
— the Flex recipe, see below — or the webpack build breaks or runs stale
code:

- `assets/package.json`'s embedded `"symfony": { "controllers": {...} }`
  section — the authoritative source (`main` file path, registered `name`,
  `fetch`, `enabled`, `autoimport`) that Flex copies into a fresh consumer
  project and that `@symfony/stimulus-bridge` resolves for npm-package
  installs.
- `assets/controllers.json` — this repo's own top-level dev manifest
  (`enabled: true` throughout — see the `enabled` divergence noted below;
  this file no longer mirrors `assets/package.json`'s values one-for-one).
- `assets/admin/controllers.json` — per-context manifest for
  sylius/test-application's admin Encore build (all 14 enabled, all fetched
  eagerly for admin dev convenience).
- `assets/shop/controllers.json` — per-context manifest for the shop Encore
  build (shop `slider`/`slide-video` pair enabled+eager; the 12 admin
  controllers listed but `enabled: false`).

**Shallow-merge trap:** sylius/test-application's webpack merges each
`controllers.json` into the bridge manifest with a **shallow spread per
package key** — a per-context file's `@vanssa/sylius-slider-plugin` object
*replaces* the whole thing, it does not deep-merge per controller. That means
`assets/admin/controllers.json` and `assets/shop/controllers.json` must each
list **all 14** controllers (even the ones a context disables) — omitting one
silently drops it from that context's build instead of falling back to a
default. Nothing in the repository enforces this — check the four key sets after
every manifest edit (`keys` sorts, so the diff is order-independent). With the
local AI tooling present, `frontend_map`'s `manifest_sync` check does the same
comparison and names the diverging keys; the snippet below is the fallback:

```bash
for f in assets/controllers.json assets/admin/controllers.json assets/shop/controllers.json; do
  diff <(jq -r '.symfony.controllers | keys[]' assets/package.json) \
       <(jq -r '.controllers["@vanssa/sylius-slider-plugin"] | keys[]' "$f") >/dev/null \
    && echo "$f: in sync" || echo "$f: OUT OF SYNC"
done
```

**Entrypoints never start Stimulus:** `assets/admin/entrypoint.js` and
`assets/shop/entrypoint.js` must **never** import `@symfony/stimulus-bridge`,
call `startStimulusApp()`, or `app.register(...)` a controller — the bridge
manifest is the only registrar now. The shop entrypoint is comment-only (kept
only because sylius/test-application hard-codes it as the `plugin-shop-entry`
webpack entry); the admin entrypoint keeps only what must run eagerly outside
Stimulus: `Turbo.session.drive = false` (critical — without it `@hotwired/turbo`
Drive hijacks every Sylius admin navigation, since the admin isn't built with
Turbo navigation in mind), the sidebar-focus behavior, and the admin
stylesheet imports. If a controller class ever creeps back into an
`app.register(...)` call in either entrypoint, expect every action/event
handler on that controller to fire **twice** per page — see below for why.

**Why `enabled: true` is correct now (it wasn't before):** an earlier version
of this plugin's own manifest deliberately shipped `enabled: false` because
the entrypoints *also* called `startStimulusApp()` and registered controllers
explicitly — with the bridge manifest also enabled, sylius/test-application's
merged config for `app-admin-entry` + `plugin-admin-entry` created TWO
Stimulus applications, double-firing everything. Now that the entrypoints
never start a Stimulus app or register anything themselves, the bridge
manifest is the *only* registrar, so `enabled: true` is required, not
optional, for every controller in *this repo's own* manifests — see the
divergence from what Flex seeds into a fresh consumer project below. The
`live` controller (`@symfony/ux-live-component`, registered by the test app's
own `controllers.json`) is unaffected by any of this.

If the manifests disagree, expect "Controller ... does not exist in the
package" or "contains a reference to the file ..." build errors, or (worse) a
controller silently missing from one context's build because the shallow
merge dropped it.

**A fifth place, outside `assets/`:** the Flex recipe
(`flex/recipes/vanssa/sylius-slider-plugin/2.3/manifest.json`)'s `add-lines`
blocks patch the same controller keys into a *consumer's*
`assets/shop/controllers.json` and `assets/admin/controllers.json` on
`composer require`. Adding, renaming or removing a controller means updating
these blocks too (shop, admin, or both, depending on where the controller
belongs), then regenerating the archived recipe with `docker compose run
--rm php php flex/build-recipes.php`. See `docs/dev/adding-a-stimulus-controller.md`
and `docs/FLEX_RECIPE.md`.

**`enabled` in `assets/package.json` now deliberately diverges from this
repo's own `controllers.json` files.** Since 2.3.2 it is a *seed* value for a
fresh consumer's root `assets/controllers.json` — storefront controllers
`true`, admin-only controllers `false` — not a setting this repo's build
reads. The local build reads `enabled` from the merged `controllers.json`
files instead, where every entry stays `true` (see "Why `enabled: true` is
correct now" above). The `manifest_sync` check (and the `jq`/`diff` fallback)
compares key sets only, not `enabled` values, so this divergence does not
trip it.

**LiveComponent morphing:** ux-live-component 2.31 morphs with idiomorph, which matches nodes by real `id` attributes only — `data-live-id` does nothing. Any list a LiveComponent re-renders while outside code mutates its DOM (drag reorder, modals re-parented to `<body>`) needs a unique `id` on every row (and stable ids on sibling anchors), or re-renders duplicate rows. `data-model` selects also need explicit `selected` attributes rendered from the server prop.

**The `file:` dependency copy trap.** `vendor/sylius/test-application/package.json` depends on this plugin's assets via `"@vanssa/sylius-slider-plugin": "file:../../../assets"`. Yarn classic (v1) **copies** this into `node_modules/@vanssa/sylius-slider-plugin` rather than symlinking it, and a plain `yarn install` does **not** refresh that copy when only source files change (lockfile unaffected). This asymmetry is easy to miss: the webpack entries (`plugin-admin-entry`, `plugin-shop-entry`) point straight at `../../../assets/**/entrypoint.js`, so entrypoints and their SCSS are always live — but all 14 Stimulus controllers are pulled in by the bridge through the bare specifier `@vanssa/sylius-slider-plugin/...`, i.e. through the stale copy.

## Asset watch mode

Use a **watcher**, not one-off builds:

```bash
docker compose --profile watch up -d nodejs-watch     # start   (= make node-watch)
docker compose --profile watch logs -f nodejs-watch   # follow  (= make node-watch-logs)
docker compose --profile watch rm -sf nodejs-watch    # stop    (= make node-watch-stop)
```

The `nodejs-watch` service runs `encore dev --watch` and, on first start, replaces the yarn copy with a symlink to the real `assets/` tree — which is what makes controller edits visible to the watcher and retires the `yarn install --force` step. It sits behind the `watch` compose profile, so a plain `docker compose up -d` never starts it. `yarn build` and `yarn watch` are both `encore dev` in this app, so watch output is identical to what a one-off build produces.

Two things a running watcher does **not** handle:

- **Manifest changes need a restart.** `webpack.config.js` merges the `controllers.json` files into `var/cache/webpack/controllers.merged.*.json` at config-load time only. Edit any `controllers.json` or `assets/package.json` and the watcher keeps building the old controller set, silently — restart it.
- **Chrome caches bundles in memory.** The `chrome` service (Behat's `@javascript` leg) keeps compiled bundles in memory; restart it before a browser check: `docker compose restart chrome`. Playwright is unaffected — `make e2e` starts a fresh browser context each run.

If file events don't reach the watcher (edits never trigger a recompile), start it with `WATCHPACK_POLLING=true`.

**Fast visual loop while editing SCSS/CSS/JS:**

```bash
make node-watch                                              # once per task
make e2e-check SPEC=tests/e2e/shop/responsive-overrides.spec.ts
```

`make e2e-check` blocks until the compiled bundles are newer than the newest
`assets/` source (i.e. the watcher caught up), then runs that one spec on
desktop + tablet + mobile. Seconds, not a full suite.

Drive the watcher with the commands above — there is no wrapper script and no dedicated subagent, so starting it, waiting for the recompile to appear in `logs`, and stopping it when the task ends are all manual steps. Nothing stops it at session end either; a forgotten `nodejs-watch` keeps running until `rm -sf`.

Clean-room fallback when no watcher is running (e.g. reproducing a CI build):

```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force"
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
docker compose restart chrome
```

## Documentation layout

- `docs/usage/` — for someone using the plugin in their shop
  (`getting-started`, `admin-guide`, `style-presets`, `storefront`,
  `options-reference`).
- `docs/dev/` — for someone extending it (`architecture`,
  `adding-a-stimulus-controller`, `extending`, `style-presets`,
  `color-picker-type`, `testing`, `docs-media`, `contributing`).
- `docs/FLEX_RECIPE.md` stays where it is. `README.md` is a short index.
- Screenshots and GIFs under `docs/screenshots/` and `docs/media/` are
  **generated** — regenerate with `make docs-media`, never hand-edit
  (`docs/dev/docs-media.md`).

## Repository maintenance guides

- **CLEANUP_GUIDE.md** — cleaning up and organizing plugin code
- **RENAME_GUIDE.md** — renaming the plugin and its components
- **COMPATIBILITY_GUIDE.md** — compatibility across Sylius versions

# Symfony UX Frontend Stack

This project uses the Symfony UX frontend stack. Seven agent skills are installed to help you work with it.

## Which tool to use

- **Pure JS behavior, no server round-trip** -- use the `stimulus` skill
- **Navigation, partial page updates** -- use the `turbo` skill
- **Reusable static UI component** -- use the `twig-component` skill
- **Reactive component that re-renders on user input** -- use the `live-component` skill
- **SVG icons (local or Iconify)** -- use the `ux-icons` skill
- **Interactive maps (Leaflet / Google Maps)** -- use the `ux-map` skill
- **Not sure which one fits** -- use the `symfony-ux` skill (orchestrator / decision tree)

## Key rules

- Always render `{{ attributes }}` on the root element of a LiveComponent
- Prefer HTML syntax (`<twig:Alert />`) over Twig syntax (`{% component 'Alert' %}`)
- Use `data-model="debounce(300)|field"` for text inputs in LiveComponents
- Stimulus controllers must clean up listeners and observers in `disconnect()`
- Turbo Frame IDs must match between the page and the server response
- Use Turbo Streams when updating multiple page sections; use Frames for a single section
- `<twig:Turbo:Stream:Append>` syntax is available since Symfony UX 2.22+
- Prefer `<twig:ux:icon name="..." />` over `{{ ux_icon('...') }}` for consistency
- Map containers must have an explicit height (`style="height: 400px;"`)
- Use `fitBoundsToMarkers()` instead of manually calculating center/zoom
- Lock on-demand icons before deploying: `php bin/console ux:icons:lock`
- Use Playwright to get what how pages is like. 
- Use Playwright to get browsers test. 
