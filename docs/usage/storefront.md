# On the storefront

How a slider built in the admin reaches a visitor: the two built-in routes,
the Twig components you can place in your own templates, the Twig Hooks
wiring, the responsive breakpoints and video slides. If the plugin is not
installed yet, start with [getting-started.md](getting-started.md); for what
each individual setting does, see
[options-reference.md](options-reference.md).

![Storefront slider](../media/storefront-slider.gif)

## The two built-in routes

Both come from
`Vanssa\SyliusSliderPlugin\Controller\Shop\SliderController` and are imported
without a locale prefix, so the URLs are exactly:

| URL | Route name | Renders |
| --- | --- | --- |
| `/slider/{code}` | `vanssa_sylius_slider_shop_slider_show` | The whole slider on its own page |
| `/banner/{code}` | `vanssa_sylius_slider_shop_banner_show` | A single slide, wrapped in `<section class="sylius-banner">` |

Both pages extend `@SyliusShop/shared/layout/base.html.twig` and use the
localized slider/slide name as the page title. The locale comes from Sylius's
`LocaleContextInterface`; the fallback locale is the current channel's default
locale.

`/slider/{code}` returns 404 when the code does not exist, when the slider is
disabled, or when the slider has a channel list that does not contain the
current channel. An empty channel list means "every channel".

`/banner/{code}` returns 404 when the slide does not exist or is disabled. If
the slide is attached to at least one slider, it is only shown when **one of
those sliders** is available for the current channel; a slide attached to no
slider at all is always shown.

These routes are useful for previewing and for linking, but most shops render
sliders inside existing pages instead — read on.

## Placing a slider in your own templates

### The slider component

`vanssa_sylius_slider:shop:slider` takes an already-loaded `Slider` entity:

```twig
{{ component('vanssa_sylius_slider:shop:slider', {
    slider: slider,
    localeCode: app.request.locale,
    fallbackLocaleCode: sylius.channel.defaultLocale.code|default(null)
}) }}
```

| Prop | Type | Required |
| --- | --- | --- |
| `slider` | `Vanssa\SyliusSliderPlugin\Entity\Slider` | yes |
| `localeCode` | `string` | yes |
| `fallbackLocaleCode` | `string` or `null` | no (defaults to `null`) |

The component renders only the slides that are enabled **and** available for
the current channel, in the order configured on the slider.

To fetch the entity by code, use the plugin's Twig functions:

```twig
{% set slider = sylius_slider_by_code('homepage-main') %}
{% set slide = sylius_slide_by_code('season-sale') %}
```

Both return the enabled record or `null`. Note that
`sylius_slider_by_code()` does **not** apply the slider's channel
restriction — that check lives in the controller and in the homepage
component below. If you use the function directly on a multi-channel shop,
either restrict the slider per channel yourself or accept that it renders
everywhere.

There is also a thin include wrapper that defaults `localeCode` to
`app.request.locale`:

```twig
{% include '@VanssaSyliusSliderPlugin/shop/slider/_slider.html.twig' with {
    slider: slider
} only %}
```

### A single slide

```twig
{{ component('vanssa_sylius_slider:shop:slide', {
    slide: slide,
    index: 0,
    localeCode: app.request.locale,
    fallbackLocaleCode: sylius.channel.defaultLocale.code|default(null),
    sliderSettings: {},
    lazyLoad: false
}) }}
```

`sliderSettings` is how a slide inherits slider-level values (parallax
strength, the overlay flag, whether the slider autoplays). Rendered
standalone with `{}`, the slide falls back to its own settings only.
`index: 0` adds the `is-active` class — the class a surrounding slider uses
to mark the visible slide.

### The homepage LiveComponent

`vanssa_sylius_slider:shop:homepage_slider` resolves the slider itself from a
code, applying both the channel restriction and the current locale:

```twig
{{ component('vanssa_sylius_slider:shop:homepage_slider', {
    code: 'fashion-classic-arrows'
}) }}
```

The `code` prop defaults to `homepage-main`. When no enabled slider matches
the code and channel, the component renders nothing at all — no error, no
placeholder.

## Twig Hooks

The plugin ships `config/twig_hooks/shop.yaml` with an **empty** hook map: it
registers no storefront hookables of its own. Placing a slider in a Sylius
page is therefore your project's configuration, using Sylius's own hookables.

Homepage, replacing the core banner:

```yaml
# config/packages/vanssa_sylius_slider.yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.homepage.index':
            banner:
                enabled: false
            vanssa_sylius_slider_homepage:
                component: 'vanssa_sylius_slider:shop:homepage_slider'
                props:
                    code: 'fashion-classic-arrows'
                priority: 400
```

`sylius_shop.homepage.index` is Sylius core's hookable. Its own hookables are
`banner` (priority 300), `latest_deals` (200), `new_collection` (100) and
`latest_products` (0), so a priority above 300 puts the slider at the top of
the page. Disabling `banner` is optional — without it you get both.

The same pattern works on any Sylius hookable; use `component:` for the
components above, or `template:` if you wrote your own wrapper template.

## Sylius CMS Plugin block

If `sylius/cms-plugin` is installed, the bundled integration template renders
a slider inside a CMS block:

```twig
{{ sylius_cms_render_block(
    'homepage_slider',
    '@VanssaSyliusSliderPlugin/shop/integration/cms/slider_block.html.twig',
    {'slider_code': 'fashion-classic-arrows'}
) }}
```

The template reads the `slider_code` variable, looks the slider up with
`sylius_slider_by_code()` and renders nothing when it finds nothing — except
in debug mode, where it prints
`SyliusSlider: no slider found for <code>`, so a typo in the code is visible
while developing.

## What gets rendered

The slider root is a `<section>` carrying the behavior contract:

```html
<section class="vanssa-slider vanssa-slider--justify-height vanssa-slider--container-content
                vanssa-slider--arrows-overlay vanssa-slider--arrows-align-center
                vanssa-slider--pagination-bottom-inside"
         data-slider-code="fashion-classic-arrows"
         style="--vanssa-slider-speed: 500ms; --vanssa-slider-nav-size: 3rem; ..."
         role="region"
         aria-roledescription="carousel"
         aria-label="Fashion Classic Arrows"
         tabindex="0"
         data-controller="vanssa-slider">
```

The modifier classes reflect the slider's settings (`--container-content` or
`--container-full`, `--arrows-overlay|--arrows-outside|--arrows-bottom`,
`--arrows-align-*`, `--pagination-*`), plus `--parallax` and
`--custom-max-height` when those are configured. `tabindex="0"` is only
present when keyboard navigation is enabled.

Inside it: `.vanssa-slider__container`, an optional
`.vanssa-slider__header` with `.vanssa-slider__title` (rendered only when the
slider's *Show title* setting is enabled; the admin form enables it on new
sliders, the demo fixtures switch it off), then
`.vanssa-slider__inner` with `.vanssa-slider__wrapper` holding one
`<article class="vanssa-slide">` per slide. Arrows
(`.vanssa-slider__action--prev` / `--next`), pagination
(`.vanssa-slider__pagination` with `.vanssa-slider__bullet` children), the
autoplay progress bar (`.vanssa-slider__progress` with its
`__progress-bar`) and a visually hidden `aria-live="polite"` region
announcing "Slide 2 of 4" are siblings inside `__inner`. Arrows, pagination
and the progress bar are only rendered when the slider has more than one
slide.

A slider with no renderable slides prints `No active slides in this slider.`
(translatable) instead of the carousel.

Each slide renders its media (`img`, `video` or `iframe`, all
`.vanssa-slide__media`), an optional `.vanssa-slide__overlay`, and
`.vanssa-slide__content > .vanssa-slide__content-box` with
`.vanssa-slide__headline`, `.vanssa-slide__description` and — depending on
the button position — either an in-content button
(`.vanssa-slide__button-content`) or one anchored to the slider frame
(`.vanssa-slide__button-slot`). Both `data-slider-code` and
`data-slide-code` are stable hooks for your own CSS and tests.

Admin-configured values reach the browser as CSS custom properties —
`--vanssa-slider-*` in the section's inline `style`, `--vanssa-slide-*` in a
`<style>` block scoped to `.vanssa-slide[data-slide-code="..."]` — or, when
they are not visual, as data attributes on the slide:
`data-vanssa-headline-element`, `data-vanssa-animation`,
`data-vanssa-button-position`, `data-vanssa-button-appearance`,
`data-vanssa-button-size`, `data-vanssa-parallax-strength` and
`data-vanssa-video-playback`. That is what makes retheming possible without
touching the admin; the internals are described in
[../dev/architecture.md](../dev/architecture.md).

## Responsive breakpoints

Three bands, hard-coded in the templates, the SCSS and the controller:

| Breakpoint | Viewport |
| --- | --- |
| Desktop | wider than 1024px |
| Tablet | 768px to 1024px |
| Mobile | up to 767px |

In CSS this is expressed as a `@media (max-width: 1024px)` block followed by
a `@media (max-width: 767px)` block: below 768px both match and the second
one wins, which is the cascade below.

Values cascade **desktop → tablet → mobile**: desktop is the base, tablet
overlays desktop, mobile overlays tablet. Anything left empty at a
breakpoint inherits from the one above it, so a mobile-only tweak needs one
field, not a full copy of the desktop settings.

![Responsive breakpoints](../media/responsive-breakpoints.gif)

The cascade is applied in four different places, which is worth knowing when
something does not change where you expect:

- **Slide layout** (positions, text alignment, colors, radius, padding,
  fonts, blur, animation) is emitted as a per-slide `<style>` block with
  `@media (max-width: 1024px)` and `@media (max-width: 767px)` rules scoped
  to that slide's `data-slide-code`. Overrides never leak to a neighbouring
  slide.
- **Texts** are rendered three times, as
  `.vanssa-breakpoint-text--desktop` / `--tablet` / `--mobile` spans; CSS
  displays exactly one. A breakpoint with no text of its own repeats the
  inherited one.
- **Media** is resolved per breakpoint server-side: the breakpoint's own
  video wins, then the desktop video, then the breakpoint's own image, then
  the desktop image (and, only if nothing else exists, any tablet or mobile
  image). Identical files are emitted once; a file that does not cover all
  three breakpoints gets `.vanssa-slide__media--on-desktop`, `--on-tablet` or
  `--on-mobile` classes and is displayed only in that range.
- **Structural slider settings** (arrows, pagination, container width,
  effect, navigation colors and sizes) are shipped to the browser as three
  precomputed maps in the controller's `options.responsive` value and applied
  with `matchMedia` on load and on every breakpoint change. This is the one
  layer that needs JavaScript: without it the server-rendered desktop
  variant stays in place.

| Desktop | Mobile |
| --- | --- |
| ![Storefront desktop](../screenshots/frontend-slider-homepage-main.png) | ![Storefront mobile](../screenshots/frontend-slider-homepage-main-mobile.png) |

## Video slides

Every breakpoint of a slide has its own video slot, and each slot accepts
either an uploaded file or an external URL. The URL wins over the upload for
that slot; clearing the URL removes the external video again. A video always
replaces the image of its breakpoint.

### Self-hosted files

Uploads render as a muted, inline `<video>`:

```html
<video class="vanssa-slide__media" muted loop playsinline data-controller="vanssa-slide-video">
    <source src="/media/slider/base-cover-video/....mp4">
</video>
```

`muted` is not optional — browsers block autoplay of audible video. `loop` is
present only while the **slider's** autoplay is off: when the slider
autoplays, the video must be able to fire `ended` so the slider can advance
after it finishes.

### YouTube

Paste a YouTube link into the *External video URL* field of a slot. Accepted
forms are `youtube.com/watch?v=ID`, `/embed/ID`, `/shorts/ID`, `/live/ID` and
`youtu.be/ID` (with or without `www.`, `m.` or the `-nocookie` host).
Anything else is rejected with *"Unsupported video URL — only YouTube links
are accepted."*

The link is normalized to `https://www.youtube.com/watch?v=ID` on save and
rendered as a privacy-enhanced embed:

```html
<iframe class="vanssa-slide__media vanssa-slide__media--embed"
        src="https://www.youtube-nocookie.com/embed/ID?autoplay=0&mute=1&playsinline=1&controls=0&rel=0&enablejsapi=1"
        allow="autoplay; encrypted-media; picture-in-picture"
        referrerpolicy="strict-origin-when-cross-origin"
        data-controller="vanssa-slide-video"></iframe>
```

The embed starts paused (`autoplay=0`) and `enablejsapi=1` lets the plugin
drive it, so playback follows the same rules as a self-hosted file.

### When a video plays

*Video playback* is a slide-level setting (not per breakpoint) with two
values:

- **Start automatically** (default) — the video plays only while it is
  visible in the viewport *and* its slide is the active one; it pauses
  otherwise.
- **Play button (visitor starts it)** — nothing plays until the visitor
  clicks the overlay button; leaving the slide stops playback and brings the
  button back.

When the active slide auto-plays a video and slider autoplay is enabled, the
slider waits for the video's end instead of the fixed interval, and the
progress bar runs for the remaining video time. The configured interval stays
as a fallback in case the video never starts. Videos in play-button mode
never hold the rotation.

## Other behavior worth knowing

- **Autoplay** only runs while the slider is at least 20% in the viewport,
  and stops when it scrolls out. The *Autoplay* section's own **Pause on
  hover** (*"Pause autoplay while the cursor is over the slider."*) is the
  one that stops the timer on hover — the separate *Behavior & Effects*
  `pauseOnHover` value is exposed to CSS as `--vanssa-slider-pause-on-hover`
  but does not drive the timer.
- **Keyboard**: left/right arrows move between slides while the section has
  focus. The section only receives `tabindex="0"` when keyboard navigation is
  enabled.
- **Touch swipe** reacts to non-mouse pointers, ignores gestures that start
  on a link or button, and needs a mostly-horizontal drag of at least 40px.
- **Content animations** inside a slider wait for the slider to scroll into
  view and replay every time a slide becomes active. On the `/banner/{code}`
  page there is no slider around the slide, so its animation runs on load.
- **Lazy media**: with *Lazy load media* on (the default), everything after
  the first slide gets `loading="lazy"` plus `fetchpriority="low"` on
  images, `preload="none"` on self-hosted videos and `loading="lazy"` on
  embed iframes. The first slide is always loaded eagerly.
- **Parallax** is pointer-driven and is disabled entirely for coarse
  pointers (touch) and for visitors with `prefers-reduced-motion: reduce`. A
  per-slide strength overrides the slider's; a slide strength of `0` disables
  it for that slide only.

## Slide descriptions and HTML

Descriptions are printed through the `sylius_slider_render_content()` Twig
function. When `monsieurbiz/sylius-rich-editor-plugin` is installed, the
content is passed through its `monsieurbiz_richeditor_render_field` filter;
otherwise the stored string is emitted as-is, unescaped. Slide descriptions
are therefore trusted admin input — treat write access to slides as write
access to storefront HTML.
