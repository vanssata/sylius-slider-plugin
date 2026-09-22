# Task: Make plugin installation zero-touch — no file changes in consumer projects

## Problem

Installing `vanssa/sylius-slider-plugin` currently modifies files in the consuming Sylius project, and the plugin's Stimulus controllers don't work until the consumer runs a webpack build:

1. Symfony Flex's `PackageJsonSynchronizer` writes all 14 controllers into the consumer's `assets/controllers.json` and adds `"@vanssa/sylius-slider-plugin": "file:vendor/vanssa/sylius-slider-plugin/assets"` to the consumer's `package.json`.
2. The flex recipe (`flex/recipes/vanssa/sylius-slider-plugin/2.3/manifest.json`) has an `add-lines` section that patches the consumer's `assets/shop/controllers.json` and `assets/admin/controllers.json`.
3. Even after all that, the consumer must run `yarn install && yarn build` before anything works.

Goal: `composer require vanssa/sylius-slider-plugin` on a stock Sylius 2 standard app must produce a fully working plugin (admin UI + shop rendering) with **zero writes** to the consumer's `package.json`, any `controllers.json`, `bootstrap.js`, or webpack config, and **no node/yarn step** in the consumer. The only acceptable consumer changes are the unavoidable flex basics: `composer.json`/`composer.lock`/`symfony.lock`, bundle registration in `config/bundles.php`, and the routes import file.

## Verified facts about Flex (v2.11 — read the code, don't trust memory beyond this)

- `PackageJsonSynchronizer::resolvePackageJson()` **only processes composer packages whose `composer.json` `keywords` contain `"symfony-ux"`**. Removing that keyword from this plugin's `composer.json` (currently line ~8) makes Flex skip it entirely: no controllers.json sync, no package.json dependency link. This is the master opt-out.
- Independently, `"symfony": { "needsPackageAsADependency": false }` in `assets/package.json` disables only the package.json dependency link.
- The `add-lines` blocks live in `flex/recipes/vanssa/sylius-slider-plugin/2.3/manifest.json`. On `composer recipes:update`, Flex's `AddLinesConfigurator::update()` automatically *removes* previously added lines when the new recipe no longer contains them — so shipping a recipe without `add-lines` cleanly un-patches existing consumers when they update the recipe.
- `php flex/build-recipes.php` flattens `flex/recipes/<vendor>/<pkg>/<version>/` into the `flex/<vendor>.<pkg>.<version>.json` files served by the endpoint; the `ref` is a content sha1, so any change produces a new ref automatically. Recipe versions are discovered from the directory names.
- Consumer-side gotcha for testing: plain filesystem paths as flex endpoints are **silently ignored** (Flex only accepts responses with HTTP status 200). To test the recipe against a consumer before pushing to GitHub, serve the flex dir over HTTP: `php -S 127.0.0.1:8879 -t flex/` and set `SYMFONY_ENDPOINT=http://127.0.0.1:8879/index.json`, with a throwaway `COMPOSER_HOME` containing `{"config":{"secure-http":false}}` (secure-http blocks plain http even for localhost).

## Implementation plan

### 1. Self-contained frontend bundle

Keep the controllers exactly where they are: the main `assets/` directory (`assets/package.json` → `symfony.controllers`) remains the single source of truth — do not move, rename, or restructure the controller sources or their definitions.

Add a build step **inside the plugin** (esbuild recommended: fast, minimal config; needs a sass plugin for `assets/shop/styles/slider.scss`) that generates from those definitions one prebuilt, minified bundle:

- `slider.js` + `slider.css` — bundles **all** controllers listed in `assets/package.json` `symfony.controllers` (shop + admin), their dependencies, the compiled `slider.scss`, and vendored CSS (`@simonwep/pickr/dist/themes/classic.min.css`). Generate the entry point from the `symfony.controllers` map (don't hand-maintain a duplicate list).

*Optional, not required:* splitting into separate `shop`/`admin` bundles (by each controller's `main` path prefix) is a size optimization that can be done later; a single combined bundle loaded on both shop and admin pages is acceptable.

The bundle creates its **own** Stimulus `Application` (`Application.start()` from `@hotwired/stimulus`, bundled in) and registers the controllers on it. Multiple Stimulus applications coexist fine on one page — each only instantiates identifiers registered with it, so this cannot conflict with the host app's Stimulus instance.

**Critical:** register each controller under the exact identifier from the `name` field in `assets/package.json` → `symfony.controllers` (e.g. `slider` → `vanssa-slider`, `slide-video` → `vanssa-slide-video`, `slider-settings` → `vanssa-slider-settings`, …). Read every `name` from that file — existing Twig templates reference these `data-controller` values and must keep working unchanged.

Output to `Resources/public/dist/` (the bundle already ships `Resources/public/preset-mockups/`, so `assets:install` — which Sylius standard runs automatically as a composer auto-script — already copies/symlinks it to `public/bundles/vanssasyliusslider/`).

Because the dist bundle is generated from the same `symfony.controllers` definitions, the advanced manual-webpack path and the zero-touch path can never drift apart.

**Commit the built dist files to the repo** so `composer require` ships them without any node toolchain, and add a CI check that rebuilds and fails on diff (stale-dist guard). Wire the build as npm scripts in the plugin root `package.json` (currently only playwright scripts exist there).

### 2. Inject the bundle via Twig hooks

The plugin already uses `sylius_twig_hooks` (`config/twig_hooks/shop.yaml`, `config/twig_hooks/admin/*.yaml`). Add hook configuration + a tiny template rendering:

```twig
<link rel="stylesheet" href="{{ asset('bundles/vanssasyliusslider/dist/slider.css') }}">
<script src="{{ asset('bundles/vanssasyliusslider/dist/slider.js') }}" defer></script>
```

hooked into both the shop base layout's and the admin base layout's stylesheet/javascript hook points.

Discover the exact hook names from the installed Sylius 2 shop/admin bundles (`debug:twig-hooks` in the test app, or grep the sylius bundles' base templates) — do not guess them. Since this config ships inside the bundle's own `config/`, consumers get it automatically — no consumer file changes.

### 3. Flex opt-outs

- Remove `"symfony-ux"` from `composer.json` keywords (note in the commit message this is deliberate — it disables Flex's asset synchronizer; keep `sylius-plugin` etc.).
- Also set `"symfony": { "needsPackageAsADependency": false }` in `assets/package.json` as belt-and-braces.
- Keep `assets/package.json` and its `symfony.controllers` section intact: it still serves consumers who *opt in* to source-level webpack integration manually (document this as the "advanced" path).

### 4. Recipe update (stay on 2.3 — ship as patch release 2.3.1)

- **Do not create a new recipe version directory.** Flex recipe versions are keyed `major.minor` only (the installed version is normalized to `X.Y` before matching), so a `2.3.1/` recipe dir would never match — edit the existing `flex/recipes/vanssa/sylius-slider-plugin/2.3/manifest.json` in place: **remove the `add-lines` section**. Keep `bundles` and the routes import copy; if feasible, move the package config import into the bundle's DI extension (`PrependExtensionInterface`) so the recipe only ships the routes file. Leave the 2.2 recipe dir untouched.
- Release this as patch version **2.3.1** on the 2.3 line. `composer.json` `version` already reads `2.3.1` — check the git tags: if `2.3.1`/`v2.3.1` is already tagged, bump to `2.3.2` instead.
- Run `php flex/build-recipes.php` and commit the regenerated `flex/*.json` — the content-sha `ref` changes automatically, which is what makes `composer recipes:update` offer the new recipe (and strip the old `add-lines` blocks) to existing consumers.
- Push the branch GitHub serves as the endpoint (`raw.githubusercontent.com/vanssata/sylius-slider-plugin/2.3/flex/index.json`) — consumers only see the updated recipe after the push.

### 5. Documentation & migration

- README/docs: default install = zero-touch (nothing to do beyond `composer require`); advanced section = manual webpack integration (add the `file:` dep + per-app controllers.json entries by hand, skip loading the dist bundles via a documented switch if double-loading is a concern).
- `UPGRADE.md` + `CHANGELOG.md`: consumers that installed with the earlier 2.3 recipe should run `composer recipes:update vanssa/sylius-slider-plugin` (removes the old `add-lines` blocks automatically), delete any `@vanssa/sylius-slider-plugin` entries from their controllers.json files and the `file:` dep from `package.json`, and rebuild their assets once.

### 6. Verification (all must pass)

1. `php flex/build-recipes.php` runs clean; the regenerated 2.3 JSON contains no `add-lines` and has a new `ref`.
2. Plugin asset build produces `Resources/public/dist/slider.{js,css}`; committed dist matches a fresh build.
3. In `tests/TestApplication`: remove every plugin-related entry from its `controllers.json`/webpack wiring, run `assets:install`, and confirm via the existing Playwright e2e suite (`yarn e2e`) that admin slider CRUD/preview and shop slider rendering (including video slides and slider.scss styling) work purely from the dist bundles.
4. Grep the recipe + bundle config to confirm nothing writes to consumer `package.json`, `controllers.json`, `bootstrap.js`, or webpack config.
5. Optional end-to-end: against a consumer checkout, serve `flex/` over HTTP (see gotcha above), `composer require vanssa/sylius-slider-plugin`, and assert `git status` in the consumer shows only: `composer.json`, `composer.lock`, `symfony.lock`, `config/bundles.php`, `config/routes/vanssa_sylius_slider.yaml` (+ `config/packages/…` only if the DI-prepend move was not done).

## Constraints

- Do not rename/remove any Stimulus identifiers, Twig templates, routes, or config keys — existing installs must keep working.
- Do not touch the 2.2/2.3 recipe directories.
- Keep the source controllers in `assets/` as the single source of truth; dist is generated from them.
