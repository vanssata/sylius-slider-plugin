# Vanssa Sylius Slider Plugin

A Sylius plugin for building and managing rich storefront sliders with:

- Slider and Slide admin management
- Translatable slide content and settings
- Image and video slide support
- Symfony UX-based storefront rendering
- Twig Hooks integration for Sylius Admin/Shop
- Optional integrations with Sylius CMS Plugin and Sylius Rich Editor Plugin

## Requirements

- PHP 8.2+
- Sylius 2.x
- Symfony 7.4+

## Installation in a Sylius Project

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

6. Register plugin frontend entries in your Encore config (example):

```js
// webpack.config.js
const path = require('path');

// In your app.shop build:
.addEntry('plugin-shop-entry', path.resolve(__dirname, 'vendor/vanssa/sylius-slider-plugin/assets/shop/entrypoint.js'))

// In your app.admin build:
.addEntry('plugin-admin-entry', path.resolve(__dirname, 'vendor/vanssa/sylius-slider-plugin/assets/admin/entrypoint.js'))
```

7. Install additional frontend libraries required by plugin assets:

```bash
yarn add @hotwired/stimulus @symfony/stimulus-bridge @stimulus-components/color-picker @simonwep/pickr
```

8. Build frontend assets (if your project uses Encore build pipeline):

```bash
yarn install
yarn build
bin/console assets:install
```
9. Add slider on homepage (optional):
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
