# Symfony Flex Recipe Endpoint

This repository ships its own private Flex endpoint, so `composer require
vanssa/sylius-slider-plugin` can auto-register the bundle, config and routes.

## Layout

- `flex/index.json` — the endpoint itself (served raw from GitHub).
- `flex/vanssa.sylius-slider-plugin.<version>.json` — **archived recipes** in
  the format Flex downloads (`manifests.<package>.{manifest,files,ref}`).
  These are generated — do not edit by hand.
- `flex/recipes/vanssa/sylius-slider-plugin/<version>/` — human-readable
  recipe sources (manifest + files). Edit these, then regenerate:

  ```bash
  php flex/build-recipes.php
  ```

The version directory follows the Flex convention: the `2.3` recipe applies to
plugin releases `>= 2.3` until a newer version directory is added.

## How it resolves

Flex fetches the endpoint URL (the index), then resolves each recipe through
`_links.recipe_template_relative` — i.e. relative to wherever the index was
served from. That makes the endpoint branch-agnostic: raw URLs for any branch
or tag work, as long as the archived JSONs sit next to `index.json`.

## What it automates

On `composer require vanssa/sylius-slider-plugin`:

- Bundle registration in `config/bundles.php`
- Plugin config import (`config/packages/vanssa_sylius_slider.yaml`)
- Admin (`/admin`-prefixed) + shop route imports (`config/routes/vanssa_sylius_slider.yaml`)
- Two `add-lines` patches into the consumer's per-context controller
  manifests: the storefront pair (`slider`, `slide-video`) into
  `assets/shop/controllers.json`, and the full 14-controller set into
  `assets/admin/controllers.json` (the admin build fetches `slider` and
  `slide-video` eagerly too, since the admin previews render the storefront
  slider inside turbo-frames/iframes)
- Two `add-lines` patches prepending the plugin's UX-package entrypoints to the
  consumer's own — `import '@vanssa/sylius-slider-plugin/admin/entrypoint';`
  into `assets/admin/entrypoint.js`, and the `shop` counterpart into
  `assets/shop/entrypoint.js` (see [Entrypoint patches](#entrypoint-patches))
- `post-install-output` printing the remaining manual steps

The two controller patches are Flex `add-lines`, inserted right after the
`"controllers": {` line of each target file. That is line-based, not JSON-aware:
if a consumer's `assets/shop/controllers.json` or `assets/admin/controllers.json`
has an *empty* `"controllers": {}` object, the block lands as a top-level key
instead of inside `controllers` — the file stays valid JSON, but the bridge
silently ignores the entries. If the file or the `"controllers": {` anchor is
missing entirely, Flex prints the block (`warn_if_missing: true`) and writes
nothing. Stock Sylius ships both files non-empty, so this only affects
projects that changed them; the fix is pasting the block into `controllers`
by hand.

### Entrypoint patches

The admin entrypoint is not optional decoration: it carries the Turbo Drive
opt-out (`Turbo.session.drive = false`), the sidebar-focus behavior and the
five admin stylesheets. Without it the admin workspace renders with the
settings panel inline and expanded instead of as an overlay, every sidebar
group expanded, and Turbo Drive hijacking every admin navigation — the
controllers all load and run, so nothing errors, which makes it an easy
misconfiguration to miss. Until 2.3.4 this was a manual step printed by
`post-install-output`; it is now patched in.

Two details of these patches are load-bearing:

- **`"position": "top"`, not `"after_target"`.** The controller patches anchor
  on `"controllers": {`, a string that exists in the target JSON. An
  `entrypoint.js` has no comparable stable anchor — a consumer's file may start
  with `import './bootstrap.js';`, a comment, or other plugins' imports. With
  `after_target` and a target that is not found, Flex does not fail: it prints
  the lines and writes nothing, so the install looks clean and the admin stays
  broken. `top` needs no anchor and only requires the file to exist.
- **The npm package name, not a webpack alias.** `@vanssa/sylius-slider-plugin`
  is guaranteed present in the consumer's `package.json` because core Flex's
  `PackageJsonSynchronizer` puts it there (see below), and the package root
  maps to the plugin's `assets/` directory — so `admin/entrypoint` resolves to
  `assets/admin/entrypoint.js`. A `@vendor/...`-style alias would be wrong:
  `@vendor` is a project-local Encore alias whose meaning differs per app
  (`<root>/vendor` in `sylius-standard`, but `../..` in
  `vendor/sylius/test-application`), and nothing guarantees a consumer defines
  it at all.

Both patches are idempotent — `AddLinesConfigurator` skips a file that already
contains the exact content string — and `composer remove` strips the lines
again. A consumer who instead wires the entrypoints as separate Encore entries
(the pre-2.3.4 instructions, still valid) should delete the injected imports,
or the styles and the sidebar handler load twice.

These six are what *this* endpoint/recipe adds. Separately — and
independently of this endpoint — `composer.json` also carries the
`symfony-ux` keyword, which Symfony Flex recognizes natively (this is core
Flex's `PackageJsonSynchronizer`, not something the recipe defines) — it is
also what makes Flex add the `file:` dependency and peerDependencies below.
The recipe deliberately does not patch `package.json` itself; Flex manages it
via `JsonManipulator` and cleans it up again on `composer remove`. On any
`composer require vanssa/sylius-slider-plugin`, whether or not the endpoint
above is configured, Flex reads `assets/package.json`'s `symfony.controllers`
section and:

- adds `"@vanssa/sylius-slider-plugin": "file:vendor/vanssa/sylius-slider-plugin/assets"`
  plus the plugin's peerDependencies to the consumer's root `package.json`
- seeds the consumer's root `assets/controllers.json` with all 14
  controllers, but with `enabled` set to what `assets/package.json` now
  declares as the seed value: only the storefront pair (`slider`,
  `slide-video`) `true`, the 12 admin controllers `false`

Both `sylius/sylius-standard` and `vendor/sylius/test-application` build
their Stimulus bridge manifest by merging
`[assets/controllers.json, assets/<context>/controllers.json]` with a shallow
spread per package key, so a per-context file's `@vanssa/sylius-slider-plugin`
object **replaces** the whole one from the root file rather than merging into
it. That is why the recipe's per-context patches above take precedence over
the root seed in both builds — the root manifest only matters as a fallback
for a project with no per-context files.

The only remaining manual step is building the assets (`yarn install --force`,
`yarn build`, `bin/console assets:install`). Registering the plugin's
entrypoints used to be manual too; the recipe now patches them in (see
[Entrypoint patches](#entrypoint-patches)).

## Consumer wiring

In the consuming project, before requiring the plugin:

```bash
composer config --json extra.symfony.endpoint \
    '["https://raw.githubusercontent.com/vanssata/sylius-slider-plugin/2.3/flex/index.json","flex://defaults"]'
composer require vanssa/sylius-slider-plugin
```

## Verifying before a release

Prove on a throwaway project that `composer require` against the in-repo endpoint
actually applies the recipe. Push the branch first — the endpoint is served from
raw.githubusercontent.com — then run (takes several minutes, needs network):

```bash
BRANCH=2.3
ENDPOINT="https://raw.githubusercontent.com/vanssata/sylius-slider-plugin/${BRANCH}/flex/index.json"

docker run --rm composer:2 sh -ec "
    apk add --no-cache jq >/dev/null
    composer create-project sylius/sylius-standard smoke --no-interaction --no-scripts --quiet
    cd smoke
    composer config --json extra.symfony.endpoint '[\"${ENDPOINT}\",\"flex://defaults\"]'
    composer require vanssa/sylius-slider-plugin --no-interaction --no-scripts

    grep -F 'VanssaSyliusSliderPlugin' config/bundles.php
    ls -la config/packages/vanssa_sylius_slider.yaml config/routes/vanssa_sylius_slider.yaml

    jq -e '.controllers[\"@vanssa/sylius-slider-plugin\"] | length == 2' assets/shop/controllers.json
    jq -e '.controllers[\"@vanssa/sylius-slider-plugin\"] | length == 14' assets/admin/controllers.json
    jq -e '[.controllers[\"@vanssa/sylius-slider-plugin\"][] | select(.enabled)] | length == 2' assets/controllers.json
    grep -F 'file:vendor/vanssa/sylius-slider-plugin/assets' package.json

    echo 'SMOKE TEST PASSED'
"
```

The first three assertions are the recipe's own three writes: the bundle
landed in `config/bundles.php`, and both the package config and the routes
file were copied. The four `jq`/`grep` checks after them are the
controller-manifest split from 2.3.2 — a plain "is it valid JSON" check does
**not** catch the empty-`controllers` failure mode described above, so these
assert on the actual entry counts: exactly 2 controllers patched into the
shop manifest, exactly 14 into the admin manifest, exactly 2 left `enabled`
in the root manifest Flex seeds, and the `file:` dependency present in
`package.json`.
