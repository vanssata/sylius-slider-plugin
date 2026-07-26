# Upgrade guide

## From 2.3.1 or older to 2.3.2

2.3.2 splits the Stimulus controller manifest a consumer's `composer
require` writes: the storefront pair (`slider`, `slide-video`) into
`assets/shop/controllers.json`, the full 14-controller set into
`assets/admin/controllers.json`. Before this, only the root
`assets/controllers.json` was seeded, with all 14 controllers `enabled:
true` — so a storefront build compiled the 12 admin controllers and the
Pickr stylesheet for nothing.

```bash
composer update vanssa/sylius-slider-plugin
composer recipes:update vanssa/sylius-slider-plugin
yarn install --force
yarn build
bin/console assets:install
```

`composer recipes:update` is the step that matters here: it re-runs the Flex
recipe, which is what writes the two per-context manifest patches. The recipe
`ref` changed in 2.3.2 specifically so that command has something new to
apply — a plain `composer update` does not re-run recipes at all, so it
leaves your manifests exactly as they were before.

## What does not change automatically

Your existing root `assets/controllers.json` **keeps its 14 `enabled: true`
entries**. Flex never overwrites an `enabled` value your file already has —
it only seeds a key that is not there yet — so `recipes:update` does not
narrow it down to the new seed (storefront pair `true`, admin controllers
`false`).

This is harmless: `assets/shop/controllers.json` and
`assets/admin/controllers.json` win over the root manifest in both
`sylius/sylius-standard` and `vendor/sylius/test-application` (shallow merge
per package key — the per-context file replaces the root entry wholesale).
If you want a clean root manifest anyway, delete the
`@vanssa/sylius-slider-plugin` block from `assets/controllers.json` by hand;
nothing reads it once the per-context files exist.

## Verify

```bash
jq -e '.controllers["@vanssa/sylius-slider-plugin"] | length == 2' assets/shop/controllers.json
jq -e '.controllers["@vanssa/sylius-slider-plugin"] | length == 14' assets/admin/controllers.json
jq -e '[.controllers["@vanssa/sylius-slider-plugin"][] | select(.enabled)] | length == 2' assets/controllers.json
grep -F 'file:vendor/vanssa/sylius-slider-plugin/assets' package.json
```

The first two and the `grep` should pass after `recipes:update` on any
project. The third one is the one this guide just told you not to expect on
an *existing* install: since Flex won't touch the `enabled` values your root
`assets/controllers.json` already has, it will likely still count 14, not 2,
until you edit that file by hand as described above — that failure is
expected and does not mean the upgrade went wrong. On a fresh install (no
prior `assets/controllers.json`) all four checks pass as written.

See [docs/FLEX_RECIPE.md](docs/FLEX_RECIPE.md) for how the recipe patches
work and the empty-`controllers` edge case, and
[docs/usage/getting-started.md](docs/usage/getting-started.md#frontend-assets)
for the full frontend-assets contract.
