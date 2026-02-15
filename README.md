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
| Sylius | `^2.2` |
| Symfony | `^7.4` |
| Node.js | `>= 20` |
| Yarn | `>= 1.22` |

## Installation in Sylius-Standard (2.2)

1. Install the package:

```bash
composer require vanssa/sylius-slider-plugin
```

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

This creates 3 sliders with shared and non-shared slides, including image and video examples.

Fixture media is shipped in:

- `tests/TestApplication/public/media/fixtures/automotive`

License for fixture media:

- `tests/TestApplication/public/media/fixtures/automotive/LICENSE.md`

## Testing

### Unit tests

```bash
vendor/bin/phpunit
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
- Symfony Flex recipe scaffold: `docs/FLEX_RECIPE.md` and `flex/recipes/vanssa/sylius-slider-plugin/1.0`
