# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Editing workspace**: the slider/slide edit pages are a two-column
  workspace — the live preview is the main surface; the settings live in a
  right-hand **drawer** (fixed to the viewport, independently scrolling,
  Save/language/breakpoint always visible in its head; one setting per
  row). Opening the settings and the fullscreen mode are ONE state: either
  button enters it, Escape leaves it. The slider drawer is one **flat
  accordion** (General, Layout & Spacing, Behavior & Effects, Arrows &
  Navigation, Pagination, Autoplay, Translations, Slides — no nested
  cards/accordions).
- **Per-breakpoint slider layout**: tablet/mobile overrides for the slider
  margins/paddings/max height ("Inherit" falls back to desktop), edited via
  the toolbar breakpoint like the slide settings and rendered through a
  scoped media-query block (`data-slider-code`).
- **Edge-to-edge slide content**: per-breakpoint `contentWidth`
  (boxed/full) and `contentMaxHeight` (up to 20–50% of the slide height)
  options + `bottom_banner` config preset.
- **More content animations** (`fade-down`, `fade-left`, `slide-up`,
  `flip-in`, `blur-in`, `bounce-in`): each type carries predefined
  duration/delay applied on selection; a "Customize animation settings"
  checkbox reveals exactly the tunables allowed for the selected type.
- **Preset try-on**: hovering a preset in the preview toolbar temporarily
  renders it on the banner (preview-only overrides; the form and draft stay
  untouched); click applies. Presets sit left in the toolbar; the accented
  Settings button + fullscreen sit right.
- **Preview loading overlay** shown from the moment a change is made until
  the refreshed preview renders.
- **Slides section tools**: one-line rows (thumbnail + title + icon
  actions), an explicit detach action (removes from the slider, never
  deletes), an **Add** browser modal listing every admin slide with search,
  membership filter (in this slider / not in it / all) and pagination —
  checking rows marks pending changes ("will be added/removed" badges) and
  an explicit **Save** in the footer commits them all at once (Close
  discards; the list re-syncs every time the modal opens) — and a
  **Create** modal running the full slide creation flow (presets included)
  pre-attached to the slider.
- **Preview renders the selected breakpoint AND language faithfully**: the
  preview endpoints accept a `breakpoint` parameter and flatten the
  effective settings server-side (desktop → tablet → mobile cascade merged
  with the locale's overrides) — switching the toolbar breakpoint or
  language re-renders the frame exactly as the shop would show that
  combination (the embedded preview cannot rely on media queries or the
  shop's matchMedia JS, which see the admin viewport). Unsaved per-locale
  slider overrides preview live too.
- **Full per-breakpoint slider settings with per-locale overrides**: every
  slider option group (layout, behavior, arrows, pagination) can be
  overridden per breakpoint, and each language can override any of it
  fully or partially per breakpoint (empty = inherit), applied in the shop
  via cascaded structural maps and matchMedia.
- **Preset-first creation from the grid**: the slides grid's Create button
  opens the preset gallery first — picking **Blank** or a preset then
  opens the create form (`?preset=<code>` auto-applies the preset's fields
  instead of re-opening the gallery).
- Pagination bullets are server-rendered (visible without JavaScript,
  including in the admin preview); the shop controller still rebuilds them
  per breakpoint at runtime.
- **Slide edit modal** mirrors the workspace: preview left, the form in an
  independently scrolling right column; Update sits in the footer next to
  Close.
- **External video providers**: video slots accept a YouTube URL
  (normalized; privacy-enhanced `youtube-nocookie` embed driven over the
  IFrame API) besides self-hosted uploads; extensible via
  `VideoProviderInterface` + the `vanssa_sylius_slider.video_provider` tag.
- **Video playback mode** (slide-global): autoplay or a visitor play
  button; **slider autoplay waits for videos** — the active slide's video
  `ended` event advances the slider instead of the fixed interval.
- **Admin-managed style presets split** into Slider presets / Slide presets
  menu entries (`/new?type=…` preselects the type).
- **Browser E2E suite**: first `@javascript` Behat scenarios (drawer,
  toolbar-driven editing, live draft preview, presets, fullscreen,
  scale-to-fit) + a chrome-compatible admin form-login step.
- Feature-tour GIFs in the README (`docs/media/`) and refreshed
  documentation screenshots (captured with the debug toolbar off).

### Fixed
- The storefront CSS loaded for the admin preview no longer bleeds into the
  admin chrome: it is imported into a CSS cascade layer, with a targeted
  shield for Bootstrap's `.dropdown-toggle::after` caret that was breaking
  the sidebar chevrons on plugin pages.
- Reordering, attaching and detaching slides no longer duplicates rows in
  the Slides section: the LiveComponent morph (idiomorph) matches nodes by
  real `id` attributes only, so every row/list/anchor carries one
  (`data-live-id` is ignored by ux-live-component 2.31).
- Every admin Stimulus/LiveComponent action fired **twice** in the test
  application (two Stimulus applications both registered the plugin's
  controllers via the merged bridge manifest) — the plugin's
  `controllers.json` entries are now `enabled: false` and the entrypoints'
  explicit `register()` calls are the single source of registration.
- Invalid create submissions (e.g. blank slider/slide/preset code) render
  form errors instead of a 500 (`empty_data` guards for strict-typed
  setters).
- On Slider Management pages the plugin's sidebar group stays open while
  the other admin sections collapse.
- **Two-column editing workspace**: the slider/slide edit pages render the
  live preview as the main surface with the settings form in a right-hand
  **drawer** — hidden by default, toggled from the preview toolbar,
  independently scrollable, with Save + language + breakpoint controls
  always visible in its head. Falls back to a stacked layout on narrow
  screens; fullscreen mode still applies.
- **Edge-to-edge slide content**: new per-breakpoint options `contentWidth`
  (boxed reading width / full-width strip — suppresses the position shift
  and overlay gutter so e.g. a bottom banner truly touches the edges) and
  `contentMaxHeight` (the content box grows with its content up to
  20–50% of the slide height). New `bottom_banner` config preset shows the
  combination.
- **Preset try-on**: hovering a preset in the preview toolbar dropdown
  temporarily renders it on the actual banner (overrides POSTed to the
  preview only — the form and draft stay untouched); mouse-out reverts,
  click applies as before.
- **Preview loading overlay**: a spinner covers the preview from the moment
  a change is made until the refreshed frame renders (debounce window +
  Turbo `[busy]` fetch state).
- **External video providers**: each video slot accepts a **YouTube URL**
  (any watch/short/embed form, normalized on save; URL wins over the file
  upload, clearing it removes the video) rendered as a privacy-enhanced
  `youtube-nocookie` embed the slide-video controller drives over the
  IFrame API. Extensible via `VideoProviderInterface` +
  `vanssa_sylius_slider.video_provider` tag.
- **Video playback mode** (slide-global): videos either start automatically
  (default) or show a **play button** the visitor clicks; deactivating the
  slide pauses and restores the button.
- **Autoplay waits for videos**: with slider autoplay on, a slide showing an
  auto-playing video advances on the video's `ended` event instead of the
  fixed interval (self-hosted videos drop `loop` in that case, the interval
  stays as a fallback, and the progress bar tracks the video's remaining
  time). Click-mode videos never hold the rotation.
- **Browser E2E suite**: first `@javascript` Behat scenarios
  (`features/admin/slider_editor_ux.feature`) driving headless Chrome over
  CDP — drawer, toolbar-driven locale/breakpoint editing, live draft
  preview, preset click-apply, fullscreen + Escape, scale-to-fit — plus a
  chrome-compatible admin form-login step and shop scenarios for the new
  content-layout options.
- **Preview-first edit pages**: both the slider and slide edit pages now
  pin an always-visible sticky live-preview panel (language, breakpoint
  tabs, collapse toggle) above the form; every change refreshes the
  preview live. The header "Preview" button and its modal are gone.
- **Direct editing from grid previews**: the slide preview modal (grid
  rows and the slider's slide lists) loads the real slide edit form from
  the new `/admin/slides/{id}/edit-panel` endpoint into a turbo-frame —
  edit and Update without leaving the grid, with the preview reacting
  live to unsaved changes.
- **One-click style presets** (`vanssa_sylius_slider.style_presets`,
  project-overridable): slide styles `hero_dark`, `clean_light`,
  `minimal`, `bold_center` and slider presets `classic_arrows`,
  `minimal_fade`, `autoplay_showcase`, offered in a "Preset" dropdown in
  the preview panels; applying fills the mapped fields and updates the
  live preview instantly.
- Slides grid: cover thumbnail column; sliders grid: slides count column.
- **Admin-managed style presets**: new `StylePreset` resource
  (`vanssa_sylius_style_preset` table, migration `Version20260718050000`)
  with full CRUD under *Slider Management → Style Presets* — code, type
  (slide/slider), label, dot-path settings JSON, enabled/position, a
  **mockup image** (seven bundled SVG mockups with a gallery picker, or a
  custom upload) and, for slider presets, **source slides**. Database
  presets merge with the configuration presets everywhere (same code =
  database wins); a "capture settings from existing slide/slider" helper
  bootstraps the settings from a resource that already looks right.
- **Preset gallery on create pages**: creating a slider or slide opens a
  modal gallery — start Blank or pick a preset card (mockup + config/custom
  badge). Slider presets with source slides create the slider server-side
  via `/admin/sliders/new/from-preset/{code}`, **cloning** the slides into
  independent copies (new `SlideCloner` service, deep copy incl.
  translations and override flags).
- **Toolbar-driven editing** on slider/slide edit pages: the preview
  panel's language dropdown and breakpoint tabs are now the single source
  of truth for which form sections are visible — an override made while
  `en_US` + Tablet are selected applies to exactly that locale and
  breakpoint. The form's own breakpoint tabs and per-locale accordions are
  gone from edit pages (create pages keep them); the translation overwrite
  checkboxes moved to an always-visible row.
- **Preview scale-to-fit + fullscreen workspace**: the previewed slide is
  transform-scaled so it is entirely visible inside the panel at every
  breakpoint, and a toolbar icon expands the edit form + preview to the
  full viewport (Escape exits).
- **Slide-level parallax**: new *Parallax* setting on the slide's Media &
  Settings card — empty inherits the slider's strength, "Disabled" turns
  parallax off for that slide only, a value overrides the slider.
- Four more shipped slide presets (`split_left_light`, `gradient_overlay`,
  `glass_card`, `promo_badge_right`) and three slider presets
  (`fullscreen_hero`, `compact_banner`, `parallax_showcase`).
- **All admin values exposed as variables**: additional
  `--vanssa-slider-*` / `--vanssa-slide-*` CSS custom properties (effect,
  container width, arrows/pagination position & style, autoplay/rewind/
  progress flags, animation name, text blur, parallax strength),
  `data-vanssa-*` attributes for non-visual values, `$vanssa-*` `!default`
  SCSS theme tokens in `assets/styles/_tokens.scss`, and a `cssVar(name)`
  helper on the shop slider controller.

### Changed
- The admin preview surfaces migrated from `<iframe>` to `<turbo-frame>`
  (`symfony/ux-turbo` + `@hotwired/turbo` are new dependencies; Turbo
  Drive stays disabled in the admin).
- The inline preview toolbar no longer duplicates the language/breakpoint
  controls (they live in the drawer head) and the obsolete "Collapse
  preview" chevron is gone from both the inline panel and the grid modals;
  grid preview modals keep their own full toolbar.
- All admin accordion/copy-button icons render again (replaced the
  never-bundled `bi-*` bootstrap-icons classes with inline Tabler icons).
- Plugin admin styles no longer bleed into the rest of the admin panel:
  every plugin SCSS rule (accordion chevron/disabled styling, Pickr button
  sizing) is scoped under a `vanssa-slider-admin` wrapper class.
- The slider settings Margin/Padding sub-headings use the Tabler `hr-text`
  divider style (translated labels) instead of ad-hoc muted `<h4>`s.

### Removed
- Misleading global `position` column from the slides grid (ordering is
  per-slider).
- Dead `general/name.html.twig` templates and the redundant slide list
  inside the slider preview modal.

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
