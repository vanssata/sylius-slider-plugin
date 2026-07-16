# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.9] - 2026-07-16

### Added
- Per-breakpoint slide media and layout settings: the "Media & Settings"
  card groups each of Desktop/Mobile/Tablet into its own tab with cover
  image, optional **video per breakpoint** (new `slide_cover_video_mobile`
  and `slide_cover_video_tablet` columns) and a layout settings accordion
  (texts & typography, button/link, layout, colors, effects, visibility);
  Mobile/Tablet values left empty fall back to the desktop version.
- Base slide display settings now live on the main Slide entity and are
  edited on the main form; translations override texts (always applied),
  media and display settings per locale via explicit checkboxes, organized
  in the same Desktop/Mobile/Tablet structure (translations gained their
  own per-breakpoint video columns).
- "Add button/link" checkbox on the base form and per locale; unchecking
  clears the values and the storefront renders no button without a label.
- Slide live preview: real cover image/video backdrop per breakpoint, a
  language selector with default-language fallback, and a desktop/tablet/
  mobile resolution switcher like the slider preview.
- Slider preview panel: "Open in new tab" link; the standalone preview page
  shows an "Edit slider" bar when opened outside the iframe.
- Demo fixtures: Big Buck Bunny video slide (CC BY 3.0, Blender Foundation)
  on the service-ops slider.
- Migration `Version20260716045546` (new nullable video columns only).

### Changed
- Channel selection on slider and slide forms uses Sylius product-style
  checkboxes (`ChannelChoiceType`, multiple + expanded) instead of
  autocomplete selects.
- Storefront media markup renders one element per distinct breakpoint media
  toggled by CSS classes (replaces the single-video/`<picture>` markup).
- README restructured with a feature overview and embedded documentation
  screenshots; `docs/SCREENSHOTS.md` extended to nine captures.

### Fixed
- Content animations now start only when the slider scrolls into view and
  replay when a slide becomes active; autoplay pauses while the slider is
  off-screen.
- Slide videos play only while visible (viewport + active slide + active
  breakpoint variant) via the new `vanssa-slide-video` controller.
- Unmapped form fields (channels, add-button and override checkboxes) were
  reset by the data mapper after `PRE_SET_DATA`, so saved selections never
  showed on edit; they are now populated in `POST_SET_DATA`.
- Translation cover images were ignored by the storefront; localized media
  getters now honor the per-locale media override.
- Web debug toolbar no longer appears inside the slider preview iframe in
  dev environments.
- Slide "Media & Settings" card header showed the raw `sylius.ui.settings`
  translation key.

## [2.2.8] - 2026-07-15

### Added
- Eight new slider options (stored in the settings JSON, no migration needed):
  `arrowsPosition` (overlay/outside/bottom), `arrowsVerticalAlign`,
  `paginationPosition` (bottom-inside/bottom-outside/top/left/right),
  `paginationStyle` (dots/lines/numbers), `keyboardNavigation`, `touchSwipe`,
  `showProgressBar` (autoplay progress bar) and `lazyLoadMedia`.
- Admin slider preview panel: select a channel and a language, then preview the
  slider in an iframe rendered with that channel's storefront (theme) styles,
  with a desktop/tablet/mobile resolution switcher. Backed by a new admin-only
  route `/admin/sliders/{id}/preview` and configurable preview entrypoints
  (`vanssa_sylius_slider.preview.shop_entrypoints`).
- "Copy from desktop" buttons on the tablet/mobile responsive settings tabs of
  the slide form (`vanssa-responsive-copy` Stimulus controller).
- Real demo media bundled with the plugin: six CC0 photos (desktop + mobile
  variants) and two CC0 video clips under `assets/fixtures/`, uploaded by the
  demo fixture; see `assets/fixtures/LICENSE.md` for attribution.
- First functional test suite (admin index/form, preview route, shop
  rendering incl. lazy loading) plus new Behat scenarios and extended unit
  tests for the new options.
- Rector integration: `rector.php` for the plugin itself (`make rector`) and a
  consumer upgrade set `rector/sets/slider-plugin-2-2.php` for automated
  migration from the legacy `Acme\SyliusSliderPlugin` namespace.
- PHPStan baseline adoption and new CI gates for ECS and PHPStan.
- `CHANGELOG.md` (this file).

### Changed
- Admin slider settings form reorganized into accordion groups (Layout &
  Spacing, Behavior & Effects, Arrows & Navigation, Pagination, Autoplay) with
  dependent option groups hidden while their parent toggle is off.
- Demo fixture uploads media from a temporary copy so loading fixtures no
  longer moves bundled assets out of the plugin.
- CI matrix now tests Sylius `~2.1.0` and `~2.2.0`; dead `~2.0.0` exclude
  removed. README/system requirements aligned to `sylius/sylius: ^2.1`.
- Flex recipe scaffold moved to the `2.2` version directory.

### Fixed
- Boolean slider settings stored as `false` (rewind, navigation, arrows,
  pause-on-hover and the new toggles) were coerced back to `true` when
  rendering; disabling them now works.
- `composer.json`/lock desynchronization that failed `composer validate
  --strict`; `extra.ai-mate.instructions` pointed at a removed file.
- Invalid dotenv syntax for the test application admin credentials.
- Removed two dead admin form templates referencing a nonexistent form field.

## [2.2.7] - 2026-02-15

### Fixed
- Missing translations.

## [2.2.6] - 2026-02-15

### Fixed
- README corrections.

## [2.2.5] - 2026-02-15

### Added
- New slider features, more documentation and tests.

### Changed
- CSS migrated to SCSS; admin UI optimization.

## [2.2.4] - 2026-02-15

### Added
- Symfony UX integration for frontend assets.

### Fixed
- Plugin configuration fixes.

## [2.2.3] - 2026-02-15

### Fixed
- Composer version constraints; test and deployment fixes.

## [2.2.2] - 2026-02-15

### Fixed
- Package stability and README/encore fixes after initial release.

## [2.2.0] - 2026-02-15

### Added
- Initial release: Slider and Slide admin management with translations,
  image/video slides, Symfony UX storefront rendering, Twig Hooks
  integration, demo fixtures, Behat and PHPUnit test setup.

[2.2.9]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.8...2.2.9
[2.2.8]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.7...2.2.8
[2.2.7]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.6...2.2.7
[2.2.6]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.5...2.2.6
[2.2.5]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.4...2.2.5
[2.2.4]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.3...2.2.4
[2.2.3]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.2...2.2.3
[2.2.2]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.0...2.2.2
