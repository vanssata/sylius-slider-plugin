# Extending Vanssa Sylius Slider Plugin

## Domain model

Main entities:

- `Vanssa\SyliusSliderPlugin\Entity\Slider`
- `Vanssa\SyliusSliderPlugin\Entity\Slide`
- `Vanssa\SyliusSliderPlugin\Entity\SliderTranslation`
- `Vanssa\SyliusSliderPlugin\Entity\SlideTranslation`
- `Vanssa\SyliusSliderPlugin\Entity\StylePreset` (admin-managed style
  presets; M2M to `Slide` for slider-preset source slides)

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
  `data-slider-settings-*-only` container handled by the
  `vanssa-slider-settings` Stimulus controller (`data-controller`,
  `data-action="vanssa-slider-settings#..."`; the literal gating attributes
  like `data-slider-settings-*-only` and `data-slider-settings-item-guard`
  keep their original names — only the controller identifier itself carries
  the `vanssa-` prefix).

Recipe for a new one-click style preset (the "Preset" dropdown in the
admin live-preview panels):
- Add (or override) an entry under `vanssa_sylius_slider.style_presets.slide`
  or `.slider` in project config — `label` + a `settings` map whose keys are
  dot paths relative to the form root (e.g.
  `settings.responsive.desktop.textColor`, `settings.autoplay.enabled`).
  Shipped defaults live in `Configuration::defaultSlideStylePresets()` /
  `defaultSliderStylePresets()`.
- `Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider` converts dot paths
  to bracket field names; `assets/admin/controllers/preset_applier_controller.js`
  fills the fields client-side and dispatches `input`/`change` so pickers,
  gating and the live preview react. Only scalar values are supported.

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

5. Override a Stimulus controller:
- The plugin registers all 14 controllers through the
  `@symfony/stimulus-bridge` manifest (`assets/package.json`'s
  `symfony.controllers`, mirrored by your project's
  `assets/controllers.json` once Flex seeds it — see
  [docs/FLEX_RECIPE.md](FLEX_RECIPE.md)); the plugin's own entrypoints never
  call `startStimulusApp()`/`app.register()`.
- To replace one, set that entry to `"enabled": false` in **your** project's
  `assets/controllers.json` and register your own class under the same
  Stimulus identifier (e.g. `vanssa-slider`) in your own entrypoint —
  templates and other controllers only ever reference the identifier, so
  they keep working unchanged against the replacement.

## Data migration strategy

When changing settings schema:

- Keep backward compatibility in normalizers.
- Add migration for structural DB changes.
- Avoid breaking existing JSON keys without fallback logic.

## Admin preview

The channel/locale/resolution preview is composed of:

- `Vanssa\SyliusSliderPlugin\Controller\Admin\SliderPreviewController` and
  `SlidePreviewController` (routes
  `vanssa_sylius_slider_admin_slider_preview` / `..._slide_preview`) —
  accept `locale`, optional `channel`, a `breakpoint` query parameter and
  draft `overrides` POSTed from the workspace
- `Vanssa\SyliusSliderPlugin\Preview\PreviewBreakpointFlattener` — bakes the
  requested breakpoint into the settings server-side (desktop → tablet →
  mobile cascade merged with the locale's overrides). The preview renders
  inside a turbo-frame in the admin page, so real media queries and the
  shop's matchMedia JS can never select tablet/mobile there.
- `Vanssa\SyliusSliderPlugin\Context\Admin\PreviewChannelContext`
  (tagged `sylius.context.channel`, resolves the requested preview channel)
- `templates/admin/slider/preview.html.twig` /
  `templates/admin/slide/preview.html.twig` (standalone pages loaded into
  the workspace turbo-frame; they include the storefront entrypoints
  configured under `vanssa_sylius_slider.preview.shop_entrypoints`)
- `assets/admin/controllers/preview_frame_controller.js` (toolbar-driven
  locale/breakpoint switching, draft snapshots, preset try-on, drawer +
  fullscreen editing mode)

To make the preview match a custom theme, point `shop_entrypoints` at the
theme's Encore build (format `"build:entry"`).

## Style presets

- Config presets and admin (database) presets are merged by
  `Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider` (database preset
  with the same code wins). Decorate it to change merge semantics.
- Bundled mockup images are listed by
  `Vanssa\SyliusSliderPlugin\Preset\MockupCatalog` (a deliberate static
  list, files in `Resources/public/preset-mockups/`). Decorate/replace the
  service to add mockups, and ship the images via your own bundle's public
  assets; `defaultMockupFor()` maps shipped config presets to mockups.
- `Vanssa\SyliusSliderPlugin\Preset\SettingsCapture` serializes an existing
  slide/slider into the flat dot-path map;
  `Vanssa\SyliusSliderPlugin\Preset\DotPathApplier` applies such a map onto
  a nested settings array (used by `SliderFactory::createFromStylePreset`).
- Slider-preset slides are cloned by
  `Vanssa\SyliusSliderPlugin\Cloner\SlideCloner` — a deep copy incl.
  translations and override flags; media paths are shared (files are not
  duplicated on disk). Decorate it to change cloning (e.g. duplicate
  files).
- Replace the `StylePreset` model/form/repository via the standard
  `sylius_resource` overrides for `vanssa_sylius_slider.style_preset`.

## Video providers

Slide video slots store either a self-hosted `/media/...` path or a
normalized external URL. External providers implement
`Vanssa\SyliusSliderPlugin\Video\VideoProviderInterface`
(`name()`, `supports()`, `normalize()`, `embedUrl()`) and are registered
with the `vanssa_sylius_slider.video_provider` tag — the
`VideoProviderRegistry` collects them; the first provider whose
`normalize()` accepts a pasted URL wins. YouTube ships as the reference
implementation (`Video\YouTubeVideoProvider`, privacy-enhanced
`youtube-nocookie` embeds with `enablejsapi=1`).

To add e.g. Vimeo: implement the interface, tag the service, and extend
`assets/shop/controllers/slide_video_controller.js` if the provider's
iframe messaging differs from YouTube's (`playVideo`/`pauseVideo` commands
and `playerState` events are used for visibility gating and the
autoplay-until-ended behavior).

## Fixture extension

- Add/modify fixture in `src/Fixture/SliderDemoFixture.php`.
- Keep bundled demo media under `assets/fixtures/images` and
  `assets/fixtures/videos` (uploaded through `UploadedMediaStorage` at load
  time; the fixture copies files so the bundled assets are never consumed).
- Keep explicit license documentation for all fixture assets
  (`assets/fixtures/LICENSE.md`).
