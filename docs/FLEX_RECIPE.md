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

These five are what *this* endpoint/recipe adds. Separately — and
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

The remaining manual steps are building the assets (`yarn install --force`,
`yarn build`, `bin/console assets:install`) and including the plugin's
`assets/admin/entrypoint.js` / `assets/shop/entrypoint.js` in your Encore
entries — see the README's Frontend setup section for the full contract
(Turbo drive opt-out, admin styles, sidebar behavior).

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
