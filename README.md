# Vanssa Sylius Slider Plugin

A Sylius plugin for building and managing rich storefront sliders with:

- Slider and Slide admin management
- Per-breakpoint media and layout settings (Desktop / Mobile / Tablet) with
  image **and video** per breakpoint — empty breakpoints fall back to desktop
- Translatable slide content with explicit per-locale overrides for texts,
  media and display settings
- Live admin previews: per-slide (language + resolution switcher) and
  per-slider (channel theme, language and resolution)
- Viewport-aware storefront: content animations start and videos play only
  while the slider is visible
- Symfony UX-based storefront rendering (Stimulus + Twig Components)
- Twig Hooks integration for Sylius Admin/Shop
- Optional integrations with Sylius CMS Plugin and Sylius Rich Editor Plugin

![Storefront slider](docs/screenshots/frontend-slider-homepage-main.png)

## Feature Tour

Editing workspace — live preview with the settings drawer (toolbar-driven
breakpoints and languages, live draft refresh):

![Editing workspace](docs/media/admin-workspace.gif)

Style preset try-on — hover previews the preset on the actual banner, click
applies it:

![Preset try-on](docs/media/preset-tryon.gif)

Preset gallery on the create pages:

![Preset gallery](docs/media/preset-gallery.gif)

Storefront slider:

![Storefront slider](docs/media/storefront-slider.gif)

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

> The canonical controller list ships in
> `vendor/vanssa/sylius-slider-plugin/assets/controllers.json`. There the
> entries are `"enabled": false` because the plugin's bundled test
> application loads TWO Stimulus applications and registers the controllers
> explicitly in its entrypoints — in a normal Sylius-Standard project (one
> Stimulus application) register them from the manifest with
> `"enabled": true` as below, and register each controller only once.

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
      "slide-video": { "enabled": true, "fetch": "eager" },
      "slider-settings": { "enabled": true, "fetch": "eager" },
      "slider-slides-preview": { "enabled": true, "fetch": "eager" },
      "responsive-copy": { "enabled": true, "fetch": "eager" },
      "preview-frame": { "enabled": true, "fetch": "eager" },
      "preset-applier": { "enabled": true, "fetch": "eager" },
      "preset-gallery": { "enabled": true, "fetch": "eager" },
      "form-context": { "enabled": true, "fetch": "eager" },
      "animation-settings": { "enabled": true, "fetch": "eager" },
      "mockup-picker": { "enabled": true, "fetch": "eager" },
      "modal-portal": { "enabled": true, "fetch": "eager" },
      "rgba-color-picker": {
        "enabled": true,
        "fetch": "eager",
        "autoimport": {
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

### Slide videos

Each video slot accepts either a **self-hosted upload** or an **external
video URL** — currently YouTube (any watch/short/embed link; it is
normalized and stored as a canonical URL, and the storefront renders a
privacy-enhanced `youtube-nocookie` embed). The URL wins over the upload for
its slot; clearing it removes the external video. The provider architecture
is extensible — see docs/EXTENDING.md to add another provider.

Video behavior:

- **Video playback** (Media & Settings, slide-global): *Start automatically*
  (default — the video plays while its slide is visible) or *Play button* —
  the visitor starts it via an overlay button; rotating away pauses it and
  brings the button back.
- **Autoplay waits for videos**: when the slider autoplays and the active
  slide shows an auto-playing video, the slider advances when the video
  **ends** instead of after the fixed interval (self-hosted videos drop
  their loop in that case; the interval remains a fallback if the video
  never starts). Click-mode videos never hold the rotation.

## Admin Editing Workspace

The slider and slide edit pages are a two-column **workspace**: the live
preview is the main surface, and the settings form lives in a right-hand
**drawer** — hidden by default, opened with the *Settings* toolbar button.
The drawer scrolls independently while its head keeps the **Save** button,
the **language** switcher and the desktop / tablet / mobile **breakpoint**
switcher always visible. Those toolbar controls **drive the form**: picking
`en_US` + *Tablet* and overriding an option stores that override for exactly
that locale and breakpoint.

The previewed slide is **scaled to fit** entirely inside the panel (no
clipping/scrolling); while a change is being applied a **loading overlay**
covers the preview until it refreshes. The **fullscreen icon** expands the
whole workspace to the full viewport (Escape exits). In the *Preset*
dropdown, **hovering** a preset temporarily shows it on the actual banner —
without touching the form — and clicking applies it.

The preview always shows **exactly the selected language and breakpoint**:
the preview endpoint receives the toolbar's breakpoint and flattens the
effective settings server-side (desktop → tablet → mobile cascade merged
with the language's overrides), so tablet/mobile variants and per-locale
differences render faithfully even though the embedded preview cannot use
real media queries. Unsaved per-language overrides preview live as well.

![Admin slider preview panel](docs/screenshots/admin-slider-preview-panel.png)

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

## Style Presets

Style presets are one-click style bundles for sliders and slides. They come
from two sources, merged together everywhere presets are offered:

1. **Configuration presets** — defined under
   `vanssa_sylius_slider.style_presets` (read-only in the admin, labeled
   *config* in the gallery).
2. **Database presets** — created and managed in the admin under
   *Slider Management → Style Presets* (labeled *custom*). A database preset
   with the same code as a configuration preset **overrides** it.

Presets are offered in two places:

- The **"Preset" dropdown** of the live-preview panels on the edit pages —
  applying one fills the mapped form fields (the live preview updates
  instantly); nothing persists until the form is saved.
- The **preset gallery modal** that opens automatically on the slider/slide
  *create* pages: start **Blank** or pick a preset card (each shows its
  mockup image). Slide presets and configuration slider presets pre-fill the
  form; database slider presets that carry source slides navigate to
  `/admin/sliders/new/from-preset/{code}` where the server **clones** those
  slides into independent copies (editing the clones never touches the
  preset's source slides).

### Managing presets in the admin

*Slider Management → Style Presets* provides full CRUD. A preset has:

- `code` (unique, fixed after creation) and `type` (slide or slider)
- `label`, `enabled`, `position`
- **Mockup image** — pick one of the bundled mockups (dark, light,
  with-text, center-bold, minimal, gradient, glass) or upload a custom
  image; it represents the preset in the selection gallery.
- **Settings** — a flat JSON map of dot-paths to values (same shape as
  configuration presets, see below).
- **Capture from an existing slide/slider** — leave the settings empty and
  pick a resource; its current style is serialized into the preset on save.
- **Slides** (slider presets only) — the source slides cloned when a slider
  is created from this preset.

### Defining a preset in configuration

Configuration presets live in your project config. Each preset is a `label`
plus a flat map of **dot-paths** (relative to the form root) to scalar
values — the dot-path mirrors the form's field structure exactly:

```yaml
# config/packages/vanssa_sylius_slider.yaml
vanssa_sylius_slider:
    style_presets:
        slide:
            brand_hero:
                label: 'Brand Hero'
                settings:
                    # slide[settings][responsive][desktop][headlineColor]
                    settings.responsive.desktop.headlineColor: 'rgba(250, 204, 21, 1)'
                    settings.responsive.desktop.backgroundColor: 'rgba(15, 23, 42, 0.75)'
                    settings.responsive.desktop.contentAnimation: 'fade-up'
                    settings.responsive.desktop.animationDuration: 700
                    # button/link fields live under settings.linking
                    settings.linking.buttonAppearance: 'primary'
        slider:
            brand_carousel:
                label: 'Brand Carousel'
                settings:
                    settings.slideEffect: 'fade'
                    settings.speed: 700
                    settings.autoplay.enabled: true
                    settings.autoplay.interval: 6000
                    settings.parallax.strength: '1rem'
```

Valid dot-path groups:

- **Slide presets** — `settings.responsive.<desktop|tablet|mobile>.<field>`
  where `<field>` is any per-breakpoint form field (texts & typography:
  `title`, `description`, `headlineElement`, `headlineFontSize`,
  `descriptionFontSize`, `buttonFontSize`; layout:
  `contentHorizontalPosition`, `contentVerticalPosition`,
  `contentTextAlign`, `contentPadding`, `contentMargin`, `contentWidth`
  (`boxed` reading width or `full` edge-to-edge strip), `contentMaxHeight`
  (content box grows with its content up to `20%`–`50%` of the slide
  height), `borderRadius`,
  `customCssClass`; colors: `textColor`, `headlineColor`,
  `descriptionColor`, `backgroundColor`, `mediaOverlayColor`; effects:
  `contentAnimation`, `animationDuration`, `animationDelay`,
  `backgroundBlurPreset`, `enableTextBlur`, `contentBlurStrength`;
  visibility: `hideTitle`, `hideDescription`, `hideButton`), plus
  `settings.linking.<field>` (`type`, `buttonAppearance`, `buttonSize`,
  `buttonPosition`, `overlay`, `openExternal`, `showProductFocusImage`).
- **Slider presets** — `settings.<field>` for any slider settings field
  (see the Slider Options Reference above), nested groups as
  `settings.autoplay.<enabled|interval|pauseOnHover>` and
  `settings.parallax.strength`.

The plugin ships slide presets `hero_dark`, `clean_light`, `minimal`,
`bold_center`, `split_left_light`, `gradient_overlay`, `glass_card`,
`promo_badge_right` and slider presets `classic_arrows`, `minimal_fade`,
`autoplay_showcase`, `fullscreen_hero`, `compact_banner`,
`parallax_showcase`. Projects can override any of them by reusing the code.

Slide presets target the desktop breakpoint — use the "Copy settings from
desktop" button afterwards to propagate to mobile/tablet.

## Parallax

Parallax (cursor-driven media shift) can be configured at two levels:

- **Slider**: *Behavior & Effects → Parallax strength* — applies to every
  slide in the slider.
- **Slide**: *Media & Settings → Parallax* — overrides the slider setting
  for that slide only. Empty = inherit from the slider; **Disabled** turns
  parallax off for that slide even inside a parallax slider.

## CSS & JS Variables

Every admin-configured value is exposed on the storefront so themes and
custom JS can consume it:

- **CSS custom properties** — `--vanssa-slider-*` on the
  `<section class="vanssa-slider">` root (speed, effect, container width,
  margins/paddings, arrows/pagination position & style, navigation
  colors/sizes/shadows, autoplay/rewind/progress flags, parallax strength,
  max height) and `--vanssa-slide-*` on `.vanssa-slide__content` per
  breakpoint (colors, sizes, padding/margin, position, animation
  name/duration/delay, blur, text-blur).
- **Data attributes** on the slide root for non-visual values:
  `data-vanssa-headline-element`, `data-vanssa-animation`,
  `data-vanssa-button-position/-appearance/-size`,
  `data-vanssa-parallax-strength`.
- **SCSS build-time defaults** — `$vanssa-slider-*` / `$vanssa-slide-*`
  `!default` variables in `assets/styles/_tokens.scss`; override them before
  importing the plugin SCSS to retheme without touching the admin.
- **JS** — the `vanssa-slider` Stimulus controller receives all behavioral
  settings in its `options` value and exposes `cssVar(name)` to read any
  custom property from the slider root.

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

![Admin sliders list](docs/screenshots/admin-sliders-index.png)

Typical flow:

1. Create slides (media, translated text, styling/options).
2. Create a slider.
3. Assign/reorder slides in slider edit page.
4. Render slider in storefront by code.

### Slide editing

The "Media & Settings" card groups everything per breakpoint: each of the
Desktop / Mobile / Tablet tabs carries its own cover image, optional video
and layout settings accordion (texts & typography, button/link, layout,
colors, effects, visibility). Mobile/Tablet values left empty fall back to
the desktop version.

![Slide media and settings per breakpoint](docs/screenshots/admin-slide-media-settings.png)

The live preview below the form previews the slide with the selected
language (falling back to the default language) and a desktop / tablet /
mobile resolution switcher:

![Slide live preview](docs/screenshots/admin-slide-live-preview.png)

### Slide translations

Each locale follows the same structure — Desktop / Mobile / Tablet tabs with
Texts (always applied for the locale), plus media and display settings that
are only overridden after enabling the corresponding checkbox:

![Slide translations with per-locale overrides](docs/screenshots/admin-slide-translations.png)

### Slider editing

Slider settings are grouped into collapsible sections (layout & spacing,
behavior, arrows & navigation, pagination, autoplay); dependent options hide
while their parent toggle is off.

![Slider edit page](docs/screenshots/admin-slider-edit-homepage-main.png)

The **Slides** section lists the slider's slides as one-line rows (thumbnail,
title, preview / edit / detach icon actions — detaching removes the slide
from this slider without deleting it). **Add** opens a browser over every
slide in the admin with search, a membership filter (in this slider / not in
it / all) and pagination: checking rows marks pending changes ("will be
added" / "will be removed" badges) and **Save changes** commits them all at
once — closing the modal discards the marks. **Create** runs the full slide
creation flow in a modal, pre-attached to the slider.

![Add slides browser modal](docs/screenshots/admin-slider-add-slides-modal.png)

### Creating from the grid

The slides grid's **Create** button opens the preset gallery first: pick
**Blank** or any preset card, and the create form opens with that choice
already applied (`?preset=<code>`).

## Storefront Usage

Content animations start when the slider scrolls into view and replay on
slide changes; slide videos play only while their slide is visible. Each
breakpoint renders its own media (video wins over image, desktop fallback).

| Desktop | Mobile |
| --- | --- |
| ![Storefront desktop](docs/screenshots/frontend-slider-homepage-main.png) | ![Storefront mobile](docs/screenshots/frontend-slider-homepage-main-mobile.png) |

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

Browser (@javascript) scenarios drive a real headless Chrome over CDP
(`features/admin/slider_editor_ux.feature`: settings drawer, toolbar-driven
locale/breakpoint editing, live draft preview, preset try-on, fullscreen,
scale-to-fit). They need the app served under `APP_ENV=test` and a Chrome
reachable at the `chrome` session's `api_url` (defaults to
`http://127.0.0.1:9222`; in the Docker setup copy `behat.yml.dist` to
`behat.yml` and point it at `http://chrome:9222`):

```bash
# Docker: serve the test env, then run against it
ENV=test docker compose up -d php nginx
docker compose run --rm -e APP_ENV=test -e BEHAT_BASE_URL=http://nginx \
    php vendor/bin/behat --strict --tags='@javascript'
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
