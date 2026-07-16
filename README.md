# Vanssa Sylius Slider Plugin

A Sylius plugin for building and managing rich storefront sliders with:

- Slider and Slide admin management
- Translatable slide content and settings
- Image and video slide support
- Symfony UX-based storefront rendering
- Twig Hooks integration for Sylius Admin/Shop
- Optional integrations with Sylius CMS Plugin and Sylius Rich Editor Plugin

## System Requirements

| Dependency | Version |
| --- | --- |
| PHP | `>= 8.3` |
| Sylius | `^2.1` |
| Symfony | `^7.4` |
| Node.js | `>= 20` |
| Yarn | `>= 1.22` |

## Installation in Sylius-Standard

1. Install the package:

```bash
composer require vanssa/sylius-slider-plugin
```

If your project uses a Flex endpoint that serves this plugin's recipe (see
[Flex Recipe](docs/FLEX_RECIPE.md)), steps 2–4 are performed automatically
by Symfony Flex on `composer require`; continue with step 5.

2. Enable the bundle (if not handled by your Flex recipe):

```php
// config/bundles.php
return [
    // ...
    Vanssa\SyliusSliderPlugin\VanssaSyliusSliderPlugin::class => ['all' => true],
];
```

3. Import plugin configuration:

```yaml
# config/packages/vanssa_sylius_slider.yaml
imports:
    - { resource: '@VanssaSyliusSliderPlugin/config/config.yaml' }
```

4. Import routes:

```yaml
# config/routes/vanssa_sylius_slider.yaml
vanssa_sylius_slider_admin:
    resource: '@VanssaSyliusSliderPlugin/config/routes/admin.yaml'
    prefix: /admin

vanssa_sylius_slider_shop:
    resource: '@VanssaSyliusSliderPlugin/config/routes/shop.yaml'
```

5. Run database migrations:

```bash
bin/console doctrine:migrations:migrate -n
```

6. Add plugin frontend package in your Sylius-Standard project:

```bash
yarn add @vanssa/sylius-slider-plugin@file:vendor/vanssa/sylius-slider-plugin/assets
```

7. Register plugin Stimulus controllers in `assets/controllers.json`:

```json
{
  "controllers": {
    "@vanssa/sylius-slider-plugin": {
      "slider": {
        "enabled": true,
        "fetch": "eager",
        "autoimport": {
          "@vanssa/sylius-slider-plugin/shop/styles/slider.scss": true
        }
      },
      "slide-preview": {
        "enabled": true,
        "fetch": "eager",
        "autoimport": {
          "@vanssa/sylius-slider-plugin/admin/styles/slide_preview.scss": true
        }
      },
      "slider-settings": {
        "enabled": true,
        "fetch": "eager"
      },
      "slider-slides-preview": {
        "enabled": true,
        "fetch": "eager"
      },
      "responsive-copy": {
        "enabled": true,
        "fetch": "eager"
      },
      "slider-preview-frame": {
        "enabled": true,
        "fetch": "eager"
      },
      "rgba-color-picker": {
        "enabled": true,
        "fetch": "eager",
        "autoimport": {
          "@vanssa/sylius-slider-plugin/admin/styles/rgba_color_picker.scss": true,
          "@simonwep/pickr/dist/themes/classic.min.css": true
        }
      }
    }
  },
  "entrypoints": []
}
```

8. Build frontend assets:

```bash
yarn install
yarn build
bin/console assets:install
```

9. Clear cache:

```bash
bin/console cache:clear
```

10. Add slider on homepage (optional):
```yaml 
# config/packages/vanssa_sylius_slider.yaml
...
sylius_twig_hooks:
    hooks:
        'sylius_shop.homepage.index':
            banner:
                enabled: false
            vanssa_sylius_slider_homepage:
                component: 'vanssa_sylius_slider:shop:homepage_slider'
                props:
                    code: 'homepage-main'
                priority: 400
 
```

Notes for Sylius-Standard:

- Keep your existing controller entries (for example `@symfony/ux-live-component` and `@symfony/ux-autocomplete`) and only add the `@vanssa/sylius-slider-plugin` block.
- If your project customizes webpack configs, ensure `assets/controllers.json` is the file passed to `enableStimulusBridge(...)`.

## Upgrading with Rector

The plugin ships a [Rector](https://getrector.com) upgrade set for projects
migrating from pre-2.2 releases (the `Acme\SyliusSliderPlugin` namespace era):

```bash
vendor/bin/rector process src \
    --config vendor/vanssa/sylius-slider-plugin/rector/sets/slider-plugin-2-2.php
```

`sylius/sylius-rector` is already part of this plugin's dev toolchain
(`make rector` / `make rector-fix` inside this repository).

## Slider Options Reference

All options live on the slider (Admin → Sliders → edit → Settings) and are
stored in the settings JSON — existing sliders keep working and fall back to
the defaults below.

| Option | Values | Default | Description |
| --- | --- | --- | --- |
| `arrowsPosition` | `overlay`, `outside`, `bottom` | `overlay` | Placement of the prev/next arrows |
| `arrowsVerticalAlign` | `center`, `top`, `bottom` | `center` | Vertical alignment of overlay arrows |
| `paginationPosition` | `bottom-inside`, `bottom-outside`, `top`, `left`, `right` | `bottom-inside` | Placement of the pagination indicators |
| `paginationStyle` | `dots`, `lines`, `numbers` | `dots` | Indicator style |
| `keyboardNavigation` | bool | `true` | Left/right arrow keys switch slides (slider is a focusable region) |
| `touchSwipe` | bool | `true` | Swipe gestures on touch devices |
| `showProgressBar` | bool | `false` | Autoplay progress bar (only with autoplay enabled; respects reduced motion) |
| `lazyLoadMedia` | bool | `true` | `loading="lazy"` / `preload="none"` for non-first slides |

Additionally: effects (`slide/fade/zoom/lift/flip`), speed, rewind, autoplay,
parallax, arrow icon/size/colors/shadow and pagination shape/size/colors/shadow.
Content blur ("blur on content") is configured **per slide and breakpoint**
(Slide → Media & Settings → Effects: `backgroundBlurPreset`, `enableTextBlur`,
`contentBlurStrength`).

Slide media and layout settings are organized **per breakpoint** (Desktop /
Mobile / Tablet tabs): each version has its own image, optional video (the
video replaces the image when set) and layout settings including the heading
tag. Anything left empty on Mobile/Tablet falls back to the Desktop version,
so nothing needs to be duplicated. Translations can override texts directly
and media/display settings per locale via explicit checkboxes.

## Admin Slider Preview

The slider edit page includes a live preview panel: pick a **channel** and a
**language** first, then the slider renders in an iframe using the storefront
styles of that channel (its theme assets), with a desktop / tablet / mobile
resolution switcher.

The preview page includes your storefront Webpack Encore entrypoints. If your
project uses different build/entry names, configure them (format
`"build:entry"` or `"entry"`):

```yaml
vanssa_sylius_slider:
    preview:
        shop_entrypoints:
            - 'shop:shop-entry'
            - 'app.shop:app-shop-entry'
            - 'app.shop:plugin-shop-entry'
```

## Preset Configuration

The plugin exports slider/slide preset values and defaults via plugin config:

- File: `config/packages/vanssa_sylius_slider.yaml`
- Root key: `vanssa_sylius_slider.presets`

Each preset contains:

- `values`: allowed values used by form choices
- `default`: default value used when creating new entities

You can override any preset in your project config.

Color switcher presets are available under:

- `vanssa_sylius_slider.presets.color_switcher.theme`
- `vanssa_sylius_slider.presets.color_switcher.default_representation`
- `vanssa_sylius_slider.presets.color_switcher.swatches.text|neutral|accent`

Slider behavior presets also include:

- `vanssa_sylius_slider.presets.slider.parallax_strength`
  Optional parallax levels (for example: `0.5rem`, `1rem`, `2rem`, `3rem`, `4rem`). If not set, parallax is disabled.

## Reusable Color Picker Field

Use `Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType` in any form field.

- `picker_swatches`: predefined swatches shown in picker
- `picker_theme`: Pickr theme (`classic`, `monolith`, `nano`)
- `picker_options`: raw Pickr options map passed to `Pickr.create(...)`
- `picker_predefined_only`: allow only colors from `picker_swatches`

Because `picker_options` is passed through, you can configure any supported
Pickr option from https://github.com/simonwep/pickr.

## Detailed Guides

- [Color Picker Type](docs/COLOR_PICKER_TYPE.md)
- [Screenshots](docs/SCREENSHOTS.md)
- [Flex Recipe](docs/FLEX_RECIPE.md)
- [Extending](docs/EXTENDING.md)
- [Contributing](docs/CONTRIBUTING.md)

## Admin Usage

After installation, in admin you can manage:

- `Sliders`: `/admin/sliders`
- `Slides`: `/admin/slides`

Typical flow:

1. Create slides (media, translated text, styling/options).
2. Create a slider.
3. Assign/reorder slides in slider edit page.
4. Render slider in storefront by code.

## Storefront Usage

### Route-based rendering

- Full slider page: `/slider/{code}`
- Banner-like single slide page: `/banner/{code}`

### Twig usage

Use Twig component:

```twig
{{ component('vanssa_sylius_slider:shop:slider', {
    slider: slider,
    localeCode: app.request.locale,
    fallbackLocaleCode: sylius.channel.defaultLocale.code|default(null)
}) }}
```

Or homepage component:

```twig
{{ component('vanssa_sylius_slider:shop:homepage_slider', { code: 'homepage-main' }) }}
```

## Demo Fixtures

The plugin provides a dedicated fixtures suite:

- Suite name: `vanssa_sylius_slider_demo`
- Fixture name: `vanssa_slider_demo`

Load demo fixtures:

```bash
bin/console sylius:fixtures:load --suite=vanssa_sylius_slider_demo -n
```

This creates 3 sliders with shared and non-shared slides, including real
photo covers (desktop + mobile variants) and two video slides.

Fixture media (CC0 photos from Wikimedia Commons and generated video clips)
is bundled with the plugin in:

- `assets/fixtures/images` and `assets/fixtures/videos`

License and attribution for fixture media:

- `assets/fixtures/LICENSE.md`

## Testing

### PHPUnit (unit + functional)

```bash
vendor/bin/phpunit --testsuite=unit
vendor/bin/phpunit --testsuite=functional   # needs the test database
```

### Static analysis & coding standard

```bash
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/ecs check
```

### Behat

```bash
vendor/bin/behat --strict --tags='@slider_admin'
vendor/bin/behat --strict --tags='@slider_frontend'
```

For JS scenarios (if enabled in your CI/project):

```bash
vendor/bin/behat --strict --tags='@javascript,@mink:chromedriver'
```

## Optional Integrations

- Sylius CMS Plugin:
  - Use `@VanssaSyliusSliderPlugin/shop/integration/cms/slider_block.html.twig`
- Monsieur Biz Rich Editor Plugin:
  - Description fields use rich editor type automatically when installed.

## Publishing Notes

For package maintainers:

- Extension guide: `docs/EXTENDING.md`
- Contribution guide: `docs/CONTRIBUTING.md`
- Changelog: `CHANGELOG.md`
- Symfony Flex recipe scaffold: `docs/FLEX_RECIPE.md` and `flex/recipes/vanssa/sylius-slider-plugin/2.2`
