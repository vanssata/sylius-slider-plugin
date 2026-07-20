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

These three are what *this* endpoint/recipe adds. Separately — and
independently of this endpoint — `composer.json` also carries the
`symfony-ux` keyword, which Symfony Flex recognizes natively (this is core
Flex's `PackageJsonSynchronizer`, not something the recipe defines). On any
`composer require vanssa/sylius-slider-plugin`, whether or not the endpoint
above is configured, Flex reads `assets/package.json`'s `symfony.controllers`
section and:

- adds `"@vanssa/sylius-slider-plugin": "file:vendor/vanssa/sylius-slider-plugin/assets"`
  plus the plugin's peerDependencies to the consumer's root `package.json`
- seeds the consumer's `assets/controllers.json` with all 14 controllers
  (shop `slider`/`slide-video` eager, the 12 admin controllers lazy, all
  `enabled: true`)

The remaining manual steps are building the assets (`yarn install`,
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

`.claude/scripts/flex-smoke.sh <branch>` (maintainer tooling) runs a throwaway
`composer create-project sylius/sylius-standard` container, points it at the
endpoint and asserts the recipe applied. The branch must be pushed first —
the endpoint is served from raw.githubusercontent.com.
