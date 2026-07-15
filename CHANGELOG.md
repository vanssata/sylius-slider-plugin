# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[2.2.8]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.7...2.2.8
[2.2.7]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.6...2.2.7
[2.2.6]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.5...2.2.6
[2.2.5]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.4...2.2.5
[2.2.4]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.3...2.2.4
[2.2.3]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.2...2.2.3
[2.2.2]: https://github.com/vanssa/sylius-slider-plugin/compare/2.2.0...2.2.2
