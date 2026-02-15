# Symfony Flex Recipe Scaffold

This repository includes a recipe scaffold for installation automation:

- `flex/recipes/vanssa/sylius-slider-plugin/1.0/manifest.json`
- `flex/recipes/vanssa/sylius-slider-plugin/1.0/config/packages/vanssa_sylius_slider.yaml`
- `flex/recipes/vanssa/sylius-slider-plugin/1.0/config/routes/vanssa_sylius_slider.yaml`

## What it automates

- Bundle registration
- Plugin config import
- Admin/shop route imports

## Using it

For public recipe distribution, publish these files to your Flex recipes source
(e.g. a private Flex endpoint or recipes-contrib workflow).

For private organizations, host this recipe index and configure Composer/Flex to use it.
