# Extending Vanssa Sylius Slider Plugin

## Domain model

Main entities:

- `Vanssa\SyliusSliderPlugin\Entity\Slider`
- `Vanssa\SyliusSliderPlugin\Entity\Slide`
- `Vanssa\SyliusSliderPlugin\Entity\SliderTranslation`
- `Vanssa\SyliusSliderPlugin\Entity\SlideTranslation`

Slider ↔ Slide is many-to-many. Slider-local ordering is stored in `Slider.settings.slideOrder`.

## Common extension points

1. Add custom settings fields:
- Extend `SliderSettingsType` / `SlideSettingsType`.
- Persist to JSON settings arrays.
- Consume in Twig components or Stimulus controllers.

Recipe for a new slider option (this is how `arrowsPosition`, `paginationStyle`
etc. are built):
- Choice-typed options: add a preset node in
  `src/DependencyInjection/Configuration.php` under `presets.slider` (`values`
  + `default`) so projects can override it via
  `vanssa_sylius_slider.presets.slider.<name>`.
- Add the field in `SliderSettingsType` using
  `SettingsPresetProvider::values()` / `safeDefault()` and extend `empty_data`.
- Read it in `templates/components/vanssa_sylius_slider/shop/slider.html.twig`.
  For booleans defaulting to `true` use `settings.key ?? true` (never
  `|default(true)`, which coerces a stored `false` back to `true`).
- Pass behavioral options into the `sliderOptions` map consumed by the
  `vanssa-slider` Stimulus controller (`assets/shop/controllers/slider_controller.js`);
  visual options are better expressed as CSS custom properties or modifier
  classes.
- Render the field in the admin accordion
  (`templates/admin/slider/form/sections/general/settings.html.twig`) and, if
  it depends on another toggle, wrap it in a
  `data-slider-settings-*-only` container handled by the `slider-settings`
  Stimulus controller.

2. Extend admin UI:
- Add Twig Hook entries in `config/twig_hooks/admin/*.yaml`.
- Place templates in `templates/admin/...`.

3. Extend storefront rendering:
- Update Twig components in `src/Twig/Component/Shop/*`.
- Update templates under `templates/components/vanssa_sylius_slider/shop`.
- Add CSS/Stimulus logic in `assets/shop`.

4. Integrate external plugins:
- CMS: create reusable Twig partials for block rendering.
- Rich editor: detect class availability and switch form type.

## Data migration strategy

When changing settings schema:

- Keep backward compatibility in normalizers.
- Add migration for structural DB changes.
- Avoid breaking existing JSON keys without fallback logic.

## Admin preview

The channel/locale/resolution preview is composed of:

- `Vanssa\SyliusSliderPlugin\Controller\Admin\SliderPreviewController`
  (route `vanssa_sylius_slider_admin_slider_preview`)
- `Vanssa\SyliusSliderPlugin\Context\Admin\PreviewChannelContext`
  (tagged `sylius.context.channel`, resolves the requested preview channel)
- `templates/admin/slider/preview.html.twig` (standalone iframe page that
  includes the storefront entrypoints configured under
  `vanssa_sylius_slider.preview.shop_entrypoints`)
- `assets/admin/controllers/slider_preview_frame_controller.js`

To make the preview match a custom theme, point `shop_entrypoints` at the
theme's Encore build (format `"build:entry"`).

## Fixture extension

- Add/modify fixture in `src/Fixture/SliderDemoFixture.php`.
- Keep bundled demo media under `assets/fixtures/images` and
  `assets/fixtures/videos` (uploaded through `UploadedMediaStorage` at load
  time; the fixture copies files so the bundled assets are never consumed).
- Keep explicit license documentation for all fixture assets
  (`assets/fixtures/LICENSE.md`).
