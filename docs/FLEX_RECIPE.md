# Symfony Flex Recipe Scaffold

This repository includes a recipe scaffold for installation automation:

- `flex/index.json`
- `flex/recipes/vanssa/sylius-slider-plugin/2.2/manifest.json`
- `flex/recipes/vanssa/sylius-slider-plugin/2.2/config/packages/vanssa_sylius_slider.yaml`
- `flex/recipes/vanssa/sylius-slider-plugin/2.2/config/routes/vanssa_sylius_slider.yaml`

The version directory (`2.2`) follows the Flex convention: the recipe applies
to plugin releases `>= 2.2` until a newer recipe version directory is added.

## What it automates

On `composer require vanssa/sylius-slider-plugin`:

- Bundle registration in `config/bundles.php`
- Plugin config import (`config/packages/vanssa_sylius_slider.yaml`)
- Admin/shop route imports (`config/routes/vanssa_sylius_slider.yaml`)

The frontend steps (yarn package, `assets/controllers.json`, build) remain
manual — see the README installation guide.

## Using it

For public recipe distribution, publish these files to your Flex recipes
source (e.g. a private Flex endpoint or the recipes-contrib workflow).

For private organizations, host `flex/index.json` (for example via a static
HTTP server or a dedicated recipes repository branch) and point Composer/Flex
at it:

```json
{
    "extra": {
        "symfony": {
            "endpoint": [
                "https://your-host/flex/index.json",
                "flex://defaults"
            ]
        }
    }
}
```
