# Options reference

Every option the plugin stores, where it is set in the admin, what values it
accepts, what it defaults to, and — for the storefront — which CSS custom
property it ends up as.

Two entities carry settings:

- **Slider** — one JSON column (`vanssa_sylius_slider.settings`) holding the
  whole settings tree, plus `code`, `name` and `enabled` columns. Per-locale
  overrides live in `vanssa_sylius_slider_translation.settings`.
- **Slide** — one JSON column (`vanssa_sylius_slide.slide_settings`) plus the
  media/identity columns. Per-locale overrides live in
  `vanssa_sylius_slide_translation.slide_settings`.

## How to read the tables

- **Setting** is the stored key. It is also the key you use in style-preset
  dot paths (`settings.showArrows`,
  `settings.responsive.desktop.textColor`, …) and the name the admin form
  field is built from. The visible admin label is the humanised key
  ("Container width", "Pagination active color"), except where a label is
  given explicitly — those are called out.
- **Values** is the exact set the form accepts. Choice fields are validated
  server-side with `Assert\Choice`; a value outside the list is rejected on
  save. The lists themselves are configurable — see
  [Changing the accepted values](#changing-the-accepted-values-and-defaults).
- **Default** is the value from `vanssa_sylius_slider.presets.*.default` (or,
  for booleans and numbers not in that catalogue, the form's own default).
  It is what a newly created slider/slide is saved with. It is *not* always
  what the storefront falls back to when a key is missing entirely — see
  [Fallbacks when a key is absent](#fallbacks-when-a-key-is-absent).
- **Per-breakpoint** says whether the setting has tablet/mobile override
  fields.

## Where the settings live

Slider settings — *Slider Management → Sliders → edit*. All sections are
items of one flat accordion in the right-hand drawer; the toolbar above the
preview (a language select plus Desktop / Mobile / Tablet buttons) chooses
which breakpoint and which language the visible fields apply to.

![Slider edit workspace](../screenshots/admin-slider-edit-homepage-main.png)

Slide settings — *Slider Management → Slides → edit* (or the **Edit** row
action in the slides grid, which opens the same form in a modal). The
"Media & Settings" card holds one accordion per breakpoint: Media,
Texts & Typography, Button / Link (Desktop only), Layout, Colors & Surface,
Effects, Visibility.

![Slide media and settings](../screenshots/admin-slide-media-settings.png)

Per-locale overrides — the "Translations" card on the same page. Titles and
descriptions exist *only* here; everything else in this card is gated behind
an explicit "Overwrite" checkbox.

![Slide translations](../screenshots/admin-slide-translations.png)

## The breakpoint fallback

Three breakpoints, resolved from the wider one down:

- **desktop** — the base values.
- **tablet** — every empty field inherits desktop.
- **mobile** — every empty field inherits tablet (and therefore desktop,
  when tablet is empty too).

Viewport ranges, used both by the emitted `<style>` blocks and by the
`vanssa-slider` Stimulus controller (`window.matchMedia`):

| Breakpoint | Viewport |
| --- | --- |
| mobile | `max-width: 767px` |
| tablet | `max-width: 1024px` (above 767px in practice, since mobile rules come later) |
| desktop | wider than 1024px |

Per-breakpoint *media* elements use disjoint queries instead:
`max-width: 767px` (mobile), `768px`–`1024px` (tablet), `min-width: 1025px`
(desktop).

Two structural differences between sliders and slides:

- A slider has **no desktop group**: the base settings *are* desktop, and
  only `responsive.tablet` / `responsive.mobile` exist as override groups.
  Their fields are optional, with an `Inherit` placeholder.
- A slide has an explicit **desktop group**: `responsive.desktop`,
  `responsive.tablet`, `responsive.mobile`. Desktop is where you set the
  actual values; tablet/mobile hold overrides only.

Slide media follows its own rule per breakpoint: the breakpoint's own video
wins over its own image, and anything missing falls back to the desktop
video/image. A slide with no media at all renders no media element.

The demo slide `new-collection` is the one fixture that carries breakpoint
overrides (tablet centres the content horizontally, mobile also centres it
vertically):

![Responsive breakpoints](../media/responsive-breakpoints.gif)

**Structural slider settings need JavaScript per breakpoint.** Arrows,
pagination, container width and slide effect are applied per breakpoint by
the `vanssa-slider` Stimulus controller at runtime (it reads a pre-resolved
map for each breakpoint from the component's `options` value). Only the
slider's margins, paddings, `maxHeight`, `navigationSize` and
`paginationSize` — and all slide settings — are emitted as CSS in media
queries and therefore work without JavaScript.

## Slider settings

### General

Section: *General* (open by default).

| Setting | Where stored | Values | Default | Notes |
| --- | --- | --- | --- | --- |
| `code` | `code` column | letters, digits, `-`, `_`; must start alphanumeric; max 64 chars; unique | — | Required. Disabled after creation — you cannot rename it later. |
| `name` | `name` column | free text | falls back to `code` when submitted empty | Rendered as the slider heading when `showTitle` is on; also the `aria-label` of the carousel region. Translatable (Translations → Name). |
| `enabled` | `enabled` column | `Yes` / `No` | `Yes` | A disabled slider 404s on `/slider/{code}` and is skipped by the shop components. |
| `channelCodes` | `settings.channelCodes` | list of channel codes | empty | Field label "Channels", help "Leave empty to display this slider on every channel." Empty means every channel. |
| `cssClasses` | `settings.cssClasses` | `[A-Za-z0-9-_ ]*` | empty | Appended verbatim to the `<section class="vanssa-slider …">` class list. Any other character fails validation. |

The order of slides inside a slider is *not* part of this form: it is stored
in `settings.slideOrder` (a list of slide ids) and saved immediately when you
drag a row in the *Slides* section — no form submit involved. Slides not
present in `slideOrder` sort after it by their own `position`, then id.

### Layout & Spacing

Section: *Layout & Spacing*.

| Setting | Values | Default | Per-breakpoint | CSS custom property |
| --- | --- | --- | --- | --- |
| `containerWidth` | `content`, `full` | `full` | yes | none — sets `vanssa-slider--container-content` / `--container-full`; `content` caps the container at `1320px` and centres it. Also mirrored to `--vanssa-slider-container-width`. |
| `maxHeight` | empty, or a CSS length matching `<number><px\|rem\|em\|vh\|vw\|%>`, max 32 chars | empty | yes | `--vanssa-slider-max-height` (plus the `vanssa-slider--custom-max-height` class, which turns the value into a fixed height) |
| `marginTop` | `0`, `0.5rem`, `1rem`, `1.5rem`, `2rem`, `4rem` | `0` | yes | `--vanssa-slider-margin-top` |
| `marginRight` | same as above | `0` | yes | `--vanssa-slider-margin-right` |
| `marginBottom` | same as above | `0` | yes | `--vanssa-slider-margin-bottom` |
| `marginLeft` | same as above | `0` | yes | `--vanssa-slider-margin-left` |
| `paddingTop` | same as above | `0` | yes | `--vanssa-slider-padding-top` |
| `paddingRight` | same as above | `0` | yes | `--vanssa-slider-padding-right` |
| `paddingBottom` | same as above | `0` | yes | `--vanssa-slider-padding-bottom` |
| `paddingLeft` | same as above | `0` | yes | `--vanssa-slider-padding-left` |

The tablet/mobile `maxHeight` override accepts a narrower set of units than
the base field: `px`, `rem`, `vh`, `%` only. `em` and `vw` pass on desktop
and are rejected on the breakpoint fields.

Without `maxHeight` the slide area is at least `320px` tall, or `560px` when
`justifySlideHeight` is on (`420px` below 768px).

### Behavior & Effects

Section: *Behavior & Effects*.

| Setting | Values | Default | Per-breakpoint | Effect |
| --- | --- | --- | --- | --- |
| `slideEffect` | `slide`, `fade`, `zoom`, `lift`, `flip` | `slide` | yes | Adds `is-effect-<value>` to every slide; also mirrored to `--vanssa-slider-effect`. An unknown value falls back to `slide`. |
| `speed` | integer, 100–10000 (ms) | `400` | no | `--vanssa-slider-speed`, used for the slide opacity/transform transition. |
| `parallax.strength` | empty, `0.5rem`, `1rem`, `2rem`, `3rem`, `4rem` | empty (parallax off) | no | `--vanssa-slider-parallax-strength` (emitted only when set) and the `vanssa-slider--parallax` class. Pointer-driven; the controller writes `--vanssa-slide-parallax-x` / `-y` on the active slide's media. Disabled automatically when the visitor prefers reduced motion or the pointer is coarse. |
| `rewind` | checkbox | on | no | Past the last slide, jump to the first (and vice versa). Off: stop at the ends. Mirrored to `--vanssa-slider-rewind` (`1`/`0`). |
| `pauseOnHover` | checkbox | on | no | Mirrored to `--vanssa-slider-pause-on-hover`. Autoplay has its own `autoplay.pauseOnHover`, which is what actually binds the mouse listeners. |
| `keyboardNavigation` | checkbox | on | no | Left/right arrow keys change slides; the section also gets `tabindex="0"`. Turning it off removes both. |
| `touchSwipe` | checkbox | on | no | Swipe gestures on touch devices. |
| `lazyLoadMedia` | checkbox | on | no | Every slide except the first gets `loading="lazy"` + `fetchpriority="low"` on images, `preload="none"` on `<video>`, `loading="lazy"` on external embeds. |
| `overlay` | checkbox | off | no | Renders `.vanssa-slide__overlay` on every slide even when the slide sets no overlay colour — the SCSS default is a left-to-right black gradient. Adds `vanssa-slider--overlay`; mirrored to `--vanssa-slider-overlay`. |
| `showTitle` | checkbox | on | no | Renders the slider name as an `<h2 class="vanssa-slider__title">` above the slides. |
| `justifySlideHeight` | checkbox | on | no | Adds `vanssa-slider--justify-height`, forcing a `560px` minimum slide height (`420px` below 768px) so slides of different content lengths do not resize the page. Mirrored to `--vanssa-slider-justify-height`. |

Only `slideEffect` has a tablet/mobile override in this group. Everything
else here is slider-global.

### Arrows & Navigation

Section: *Arrows & Navigation*. `showNavigation` gates this whole group and
Pagination: with it off, neither arrows nor bullets are shown. Arrows and
bullets are also suppressed when the slider has one slide or fewer.

| Setting | Values | Default | Per-breakpoint | CSS custom property |
| --- | --- | --- | --- | --- |
| `showNavigation` | checkbox (tri-state `Inherit`/`Yes`/`No` on tablet/mobile) | on | yes | none — controls element visibility |
| `showArrows` | checkbox (tri-state on tablet/mobile) | on | yes | none |
| `arrowsPosition` | `overlay`, `outside`, `bottom` | `overlay` | yes | class `vanssa-slider--arrows-<value>`; mirrored to `--vanssa-slider-arrows-position` |
| `arrowsVerticalAlign` | `center`, `top`, `bottom` | `center` | yes | class `vanssa-slider--arrows-align-<value>`; mirrored to `--vanssa-slider-arrows-align`. Has no effect with `arrowsPosition: bottom`, whose rules come later in the stylesheet. |
| `navigationIcon` | `chevron`, `angle`, `square` | `chevron` | yes | class `vanssa-slider__action-icon--<value>` and the button glyph (`‹ ›`, `❮ ❯`, `■`); mirrored to `--vanssa-slider-nav-icon` |
| `navigationSize` | `1.5rem`, `3rem`, `4rem`, `6rem` | `3rem` | yes | `--vanssa-slider-nav-size` — the button's `font-size`; its box is `1.75em` square |
| `navigationShadow` | `none`, `soft`, `medium`, `strong`, `glow` | `none` | yes | `--vanssa-slider-nav-shadow`, resolved to a `box-shadow` value (`none`; `0 3px 10px rgba(15,23,42,.25)`; `0 6px 16px rgba(15,23,42,.35)`; `0 10px 26px rgba(15,23,42,.46)`; `0 0 16px rgba(250,204,21,.55)`) |
| `navigationColor` | any CSS colour (`Assert\CssColor`) | `rgba(250, 204, 21, 1)` | yes | `--vanssa-slider-nav-color` |
| `navigationBackgroundColor` | any CSS colour | `rgba(17, 24, 39, 0.85)` | yes | `--vanssa-slider-nav-bg` |

`navigationColor` is restricted to the configured accent swatches
(`picker_predefined_only`), so a free-form value is rejected with "Please
choose one of predefined colors."; `navigationBackgroundColor` accepts any
colour.

Legacy stored sizes `sm` / `md` / `lg` are still accepted and mapped to
`1rem` / `1.5rem` / `2rem`.

### Pagination

Section: *Pagination*.

| Setting | Values | Default | Per-breakpoint | CSS custom property |
| --- | --- | --- | --- | --- |
| `paginationStyle` | `dots`, `lines`, `numbers` | `dots` | yes | class `vanssa-slider__bullet--style-<value>`; mirrored to `--vanssa-slider-pagination-style` |
| `paginationPosition` | `bottom-inside`, `bottom-outside`, `top`, `left`, `right` | `bottom-inside` | yes | class `vanssa-slider--pagination-<value>`; mirrored to `--vanssa-slider-pagination-position` |
| `paginationShape` | `circle`, `square` | `circle` | yes | class `vanssa-slider__bullet--circle` / `--square` (applied to `dots` only); mirrored to `--vanssa-slider-pagination-shape` |
| `paginationSize` | `0.5rem`, `0.625rem`, `0.8rem`, `1rem` | `0.625rem` | yes | `--vanssa-slider-pagination-size` — the bullet `font-size`; bullets are `1em` square (`2.2em × 0.4em` for `lines`) |
| `paginationShadow` | `none`, `soft`, `medium`, `strong`, `glow` | `none` | yes | `--vanssa-slider-pagination-shadow` (`none`; `0 2px 7px rgba(15,23,42,.22)`; `0 3px 9px rgba(15,23,42,.3)`; `0 6px 16px rgba(15,23,42,.4)`; `0 0 10px rgba(250,204,21,.55)`) |
| `paginationColor` | any CSS colour, restricted to the accent swatches | `rgba(250, 204, 21, 0.45)` | yes | `--vanssa-slider-pagination-color` |
| `paginationActiveColor` | any CSS colour, restricted to the accent swatches | `rgba(250, 204, 21, 1)` | yes | `--vanssa-slider-pagination-active` — also the fill of the autoplay progress bar |

Legacy stored sizes `sm` / `md` / `lg` map to `0.5rem` / `0.625rem` /
`0.8rem`.

`showProgressBar` also has a tablet/mobile override, rendered in this group
on those breakpoints (its desktop field lives under Autoplay).

### Autoplay

Section: *Autoplay*. The interval, pause-on-hover and progress-bar fields are
hidden until `autoplay.enabled` is checked.

| Setting | Values | Default | Per-breakpoint | Effect |
| --- | --- | --- | --- | --- |
| `autoplay.enabled` | checkbox | off | no | Starts rotation, but only while the slider is at least 20% visible in the viewport. Mirrored to `--vanssa-slider-autoplay`. |
| `autoplay.interval` | `2000`, `3000`, `5000`, `8000`, `10000` (ms, labelled in seconds) | `5000` | no | `--vanssa-slider-autoplay-interval`. When the active slide auto-plays a video, the slider waits for the video's `ended` event instead and uses the interval only as a fallback. |
| `autoplay.pauseOnHover` | checkbox | on | no | Binds `mouseenter`/`mouseleave` to stop and restart autoplay. |
| `showProgressBar` | checkbox | off | yes | Renders `.vanssa-slider__progress`, filled with `--vanssa-slider-pagination-active`. Requires autoplay: the setting is combined with `autoplay.enabled` server-side, so it does nothing on its own. The bar drops its transition under `prefers-reduced-motion: reduce`. Mirrored to `--vanssa-slider-progress`. |

### Per-locale slider overrides

Section: *Translations*, per locale. Contains the translated `name` plus a
full second copy of the override fields:

- Desktop → `translation.settings.base` — overrides the slider's base
  (desktop) settings for that locale.
- Tablet / Mobile → `translation.settings.responsive.tablet|mobile` —
  overrides that locale's breakpoint values.

The override set is the same for both: `showNavigation`, `showArrows`,
`showProgressBar`, `containerWidth`, `slideEffect`, `arrowsPosition`,
`arrowsVerticalAlign`, `navigationIcon`, `navigationSize`,
`navigationShadow`, `navigationColor`, `navigationBackgroundColor`,
`paginationStyle`, `paginationPosition`, `paginationShape`,
`paginationSize`, `paginationShadow`, `paginationColor`,
`paginationActiveColor`, the eight margin/padding fields, and `maxHeight`.

Every field is optional. Empty (`''`, `null` or `[]`) values are skipped when
the override is merged, so a locale can override one colour without
inheriting a frozen copy of everything else. Options not in that list —
autoplay, speed, parallax, `overlay`, `showTitle`, `cssClasses`,
`justifySlideHeight`, `rewind`, `pauseOnHover`, `keyboardNavigation`,
`touchSwipe`, `lazyLoadMedia` — cannot be varied per locale.

## Slide settings

### General

Section: *General* card.

| Setting | Where stored | Values | Default | Notes |
| --- | --- | --- | --- | --- |
| `code` | `code` column | letters, digits, `-`, `_`; must start alphanumeric; max 64; unique | — | Required, disabled after creation. Also the storefront hook: `data-slide-code` on the article, the selector the per-slide `<style>` block targets, and the `/banner/{code}` route parameter. |
| `name` | `name` column | free text | falls back to `code` when empty | Used as the `alt` and `title` of the slide image, and as the grid label. Translatable — a translation's name is set from its desktop title on save. |
| `sliders` | join table | any sliders | none | Multi-select with autocomplete; a slide may belong to several sliders. |
| `channelCodes` | `channel_codes` column | list of channel codes | empty | Label "Channels". Empty means every channel. |
| `position` | `position` column | integer | `0` | Only a tiebreaker: the per-slider drag order (`settings.slideOrder`) wins, and `position` orders whatever is not listed there. |
| `enabled` | `enabled` column | `Yes` / `No` | `Yes` | Disabled slides are dropped from every slider and 404 on `/banner/{code}`. |

### Media

Section: *Media & Settings* → breakpoint → *Media*. Each of the three
breakpoints has its own image and video slot.

| Setting | Where stored | Accepts | Per-breakpoint |
| --- | --- | --- | --- |
| Image | `slide_cover`, `slide_cover_tablet`, `slide_cover_mobile` | file upload | yes |
| Video (self-hosted upload) | `slide_cover_video`, `slide_cover_video_tablet`, `slide_cover_video_mobile` | file upload | yes |
| External video URL | same columns as the upload | a YouTube link | yes |

Details that decide what actually renders:

- Within one breakpoint, **video wins over image**.
- An **external URL wins over the upload** for its slot. Clearing the URL
  field removes the external video from that slot (an uploaded file is
  untouched by this).
- Only YouTube links are accepted: `youtube.com/watch?v=…`,
  `youtube.com/embed/…`, `/shorts/…`, `/live/…`, `youtu.be/…`, with or
  without `www.`/`m.`, and the `youtube-nocookie.com` variants. Anything else
  fails with "Unsupported video URL — only YouTube links are accepted."
  The link is normalised to `https://www.youtube.com/watch?v=<id>` on save
  and rendered as a `youtube-nocookie.com/embed/<id>` iframe
  (`mute=1`, `playsinline=1`, `controls=0`, `rel=0`, `enablejsapi=1`).
- Uploads are stored under `public/media/slider/…` with a random filename.
  No file-type or size constraint is applied by the plugin — PHP's own
  `upload_max_filesize` / `post_max_size` are the only limits.
- Identical media across breakpoints is rendered once and shown per
  breakpoint via `vanssa-slide__media--on-<breakpoint>` classes.

### Slide-global settings

Section: *Media & Settings*, above the breakpoint tabs. Neither is
per-breakpoint and neither can be overridden per locale.

| Setting | Label | Values | Default | Effect |
| --- | --- | --- | --- | --- |
| `parallax.strength` | "Parallax" | empty (inherit the slider), `0` (disabled for this slide only), `0.5rem`, `1rem`, `2rem`, `3rem`, `4rem` | empty | Written to `data-vanssa-parallax-strength` and, when effective, adds `vanssa-slide--parallax`. |
| `video.playback` | "Video playback" | `autoplay` ("Start automatically"), `click` ("Play button (visitor starts it)") | `autoplay` | Written to `data-vanssa-video-playback`. `click` renders a `.vanssa-slide__video-play` button and never gates slider autoplay; `autoplay` videos do — the slider advances on `ended`. |

### Button / Link

Section: *Media & Settings* → Desktop → *Button / Link*. Not per-breakpoint.
The fields are hidden until "Add button/link" is checked; unchecking it
clears the label and the URL on save.

| Setting | Where stored | Values | Default | Storefront effect |
| --- | --- | --- | --- | --- |
| `buttonLabel` | `button_label` column | free text, max 255 | empty | The button is rendered only when **both** label and URL are set. |
| `url` | `url` column | absolute URL, max 1024 (`Assert\Url`) | empty | Same. |
| `linking.buttonAppearance` | `slide_settings` | `primary`, `secondary`, `success`, `danger` | `primary` | class `vanssa-slide__button--<value>` (blue / grey / green / red) and `data-vanssa-button-appearance` |
| `linking.buttonSize` | `slide_settings` | `sm`, `md`, `lg` | `md` | class `vanssa-slide__button--<value>` (padding + font size) and `data-vanssa-button-size` |
| `linking.buttonPosition` | `slide_settings` | `content_left`, `content_center`, `content_right`, `slider_bottom_left`, `slider_bottom_left_2_12`, `slider_bottom_left_3_12`, `slider_bottom_left_4_12`, `slider_bottom_center`, `slider_bottom_right`, `slider_bottom_right_2_12`, `slider_bottom_right_3_12`, `slider_bottom_right_4_12` | `content_left` | `content_*` renders the button inside the content box (`vanssa-slide__button-content--left\|center\|right`); every `slider_bottom_*` renders it in a full-width strip pinned to the bottom of the slide (`vanssa-slide__button-slot--slider-bottom-…`), where the `_2_12` / `_3_12` / `_4_12` variants add a 16.666% / 25% / 33.333% inset. Also `data-vanssa-button-position`. |
| `linking.openExternal` | `slide_settings` | checkbox | off | Adds `target="_blank" rel="noopener noreferrer"`. |
| `linking.type` | `slide_settings` | `custom`, `product`, `category` | `custom` | Stored and shown in the admin. The shipped storefront templates do not read it — the link is always the `url` value. |
| `linking.overlay` | `slide_settings` | checkbox | off | Stored and shown in the admin. Not read by the shipped storefront templates; the full-slide overlay element is driven by `mediaOverlayColor` and the slider's `overlay` setting instead. |
| `linking.showProductFocusImage` | `slide_settings` | checkbox | on | Stored and shown in the admin. Not read by the shipped storefront templates. |

Legacy stored positions `content`, `bottom_left`, `bottom_center` and
`bottom_right` are still understood and mapped to their current equivalents.

### Texts & Typography

Section: *Media & Settings* → breakpoint → *Texts & Typography* for the
typography; *Translations* → locale → breakpoint → *Texts & Typography*
for the texts.

| Setting | Values | Default | Per-breakpoint | Per-locale | CSS custom property |
| --- | --- | --- | --- | --- | --- |
| `title` | free text, max 255 | empty | yes | **only** in translations | none — rendered inside `.vanssa-slide__headline` |
| `description` | free text, max 65535 | empty | yes | **only** in translations | none — rendered inside `.vanssa-slide__description`, unescaped, so HTML in the field is output as markup (and run through `monsieurbiz_richeditor_render_field` when that plugin is installed) |
| `headlineElement` | `div`, `h1`, `h2`, `h3`, `h4`, `h5`, `h6` | `h1` | yes | yes (always applied) | none — it is the tag of `.vanssa-slide__headline`; also `data-vanssa-headline-element` |
| `headlineFontSize` | `0.8rem`, `1rem`, `1.2rem`, `1.5rem`, `2rem`, `2.5rem`, `3rem` | `1.5rem` | yes | yes (always applied) | `--vanssa-slide-headline-size` |
| `descriptionFontSize` | same list | `1rem` | yes | yes (always applied) | `--vanssa-slide-description-size` |
| `buttonFontSize` | same list | `1.2rem` | yes | yes (always applied) | `--vanssa-slide-button-size` |

The base slide form deliberately has **no** title/description fields: those
two are translated content and exist only under Translations. A slide with no
translations therefore renders no headline and no description.

All three breakpoint texts are rendered into the markup at once, wrapped in
`.vanssa-breakpoint-text--desktop|tablet|mobile` spans, and switched by CSS.

### Layout

Section: *Media & Settings* → breakpoint → *Layout*.

| Setting | Values | Default | Per-breakpoint | CSS custom property |
| --- | --- | --- | --- | --- |
| `contentHorizontalPosition` | `start` ("Left edge"), `left_2_12`, `left_3_12`, `left_4_12`, `center`, `right_2_12`, `right_3_12`, `right_4_12`, `end` ("Right edge") | `start` | yes | `--vanssa-slide-content-pos-x` (`flex-start` / `center` / `flex-end`) plus `--vanssa-slide-content-shift-x` (`10%`, `16.666%`, `25%`, `33.333%`, and the negatives for the right-hand values) |
| `contentVerticalPosition` | `top` ("Top edge"), `top_1_5`, `top_2_5`, `top_3_5`, `top_4_5`, `center` ("Middle"), `bottom` ("Bottom edge") | `center` | yes | `--vanssa-slide-content-pos-y` (`flex-start` / `center` / `flex-end`) plus `--vanssa-slide-content-shift-y` (`10%`, `20%`, `40%`, `60%`, `80%`, `-10%`) |
| `contentTextAlign` | `left`, `center`, `right` | `center` | yes | `--vanssa-slide-text-align` |
| `contentPadding` | `0`, `0.75rem`, `1rem`, `1.5rem`, `2rem` | `1rem` | yes | `--vanssa-slide-padding` |
| `contentMargin` | `0`, `0.5rem`, `1rem`, `1.5rem`, `2rem` | `0` | yes | `--vanssa-slide-margin` |
| `borderRadius` | `0`, `6`, `10`, `16`, `24` (px) | `0` | yes | `--vanssa-slide-radius` |
| `contentWidth` | `boxed` ("Boxed (reading width)"), `full` ("Full width (edge to edge)") | `boxed` | yes | `full` sets `--vanssa-slide-content-max-width: 100%`, `--vanssa-slide-content-width: 100%`, `--vanssa-slide-content-gutter: 0px` and forces both position shifts to `0px` (a negative shift would push a full-bleed strip off-canvas). `boxed` restores `min(54ch, 100%)` / `auto` / `1.5rem`. |
| `contentMaxHeight` | `none` ("No limit"), `20%`, `30%`, `40%`, `50%`, `100%` ("Full slider height") | `none` | yes | `--vanssa-slide-content-max-height` (emitted only when not `none`); the box grows with its content up to that share of the slide height, then clips |
| `customCssClass` | `[A-Za-z0-9-_ ]*` | empty | yes | none — appended to the `<article class="vanssa-slide …">` class list. Only the **desktop** value is used; tablet/mobile values are stored but never reach the markup. |

### Colors & Surface

Section: *Media & Settings* → breakpoint → *Colors & Surface*. All five
accept any CSS colour (`Assert\CssColor`) and offer the configured swatches;
none has a stored default — leaving one empty means the stylesheet fallback
applies.

| Setting | Per-breakpoint | CSS custom property | Fallback when empty |
| --- | --- | --- | --- |
| `textColor` | yes | `--vanssa-slide-text-color` | `$vanssa-slide-text-color` (white) |
| `headlineColor` | yes | `--vanssa-slide-headline-color` | `inherit` |
| `descriptionColor` | yes | `--vanssa-slide-description-color` | `inherit` |
| `backgroundColor` | yes | `--vanssa-slide-background-color` | `transparent` |
| `mediaOverlayColor` | yes | `--vanssa-slide-overlay-color` | `linear-gradient(90deg, rgb(0 0 0 / 60%), rgb(0 0 0 / 25%))` |

`mediaOverlayColor` also decides whether the overlay element exists at all:
`.vanssa-slide__overlay` is rendered when the slide sets an overlay colour on
any breakpoint, or when the slider's `overlay` option is on. Setting the
colour on tablet/mobile only makes the template emit an explicit
`transparent` desktop value, so the gradient fallback does not surface on
desktop.

### Effects

Section: *Media & Settings* → breakpoint → *Effects*.

| Setting | Values | Default | Per-breakpoint | CSS custom property |
| --- | --- | --- | --- | --- |
| `contentAnimation` | `fade-up`, `fade-down`, `fade-left`, `fade-right`, `zoom-in`, `slide-up`, `flip-in`, `blur-in`, `bounce-in`, `none` | `fade-up` | yes | class `is-anim-<value>` (nothing for `none`); mirrored to `--vanssa-slide-animation-name` |
| `animationDuration` | `250`, `400`, `500`, `700`, `1000` (ms) | `500` | yes | `--vanssa-slide-animation-duration` |
| `animationDelay` | `0`, `100`, `200`, `350`, `500` (ms) | `0` | yes | `--vanssa-slide-animation-delay` |
| `backgroundBlurPreset` | `none`, `soft`, `medium`, `strong` | `none` | yes | `--vanssa-slide-bg-blur` → `0px`, `4px`, `8px`, `14px`; applied as `backdrop-filter` on the content box |
| `enableTextBlur` | checkbox | off | yes | class `is-blurred`, which swaps the backdrop filter to `--vanssa-slide-blur` and mixes the background colour to 78% opacity; mirrored to `--vanssa-slide-text-blur` (`1`/`0`) |
| `contentBlurStrength` | `4`, `8`, `12`, `16`, `24` (px) | `12` | yes | `--vanssa-slide-blur` — only in effect together with `enableTextBlur` |

Duration and delay are hidden behind a "Customize animation settings"
checkbox in the admin; picking an animation type applies that type's
preset values (500 ms for the fade family, 700 ms for `zoom-in`/`slide-up`,
700 ms + 100 ms delay for `flip-in`, 1000 ms for `blur-in`/`bounce-in`).

Inside a slider the animation does not run on page load: it waits for the
slider to scroll into view (the controller adds `is-in-view`) and replays
every time the slide becomes active. A slide rendered standalone on
`/banner/{code}` animates immediately.

### Visibility

Section: *Media & Settings* → breakpoint → *Visibility*.

| Setting | Values | Default | Per-breakpoint | Storefront effect |
| --- | --- | --- | --- | --- |
| `hideTitle` | checkbox | off | yes | adds `is-hide-title-<breakpoint>` |
| `hideDescription` | checkbox | off | yes | adds `is-hide-description-<breakpoint>` |
| `hideButton` | checkbox | off | yes | adds `is-hide-button-<breakpoint>` |

The class is emitted per breakpoint, but the shipped stylesheet declares the
three rules without media queries: **any** of `is-hide-title-desktop`,
`-tablet` or `-mobile` hides the headline at every viewport (the same holds
for description and button). Ticking "Hide title" on mobile only therefore
hides it on desktop too. Until that is fixed, use the breakpoint's own title
field (leave it empty) rather than the visibility flag when you want a
per-viewport difference.

### Per-locale slide overrides

Section: *Translations* → locale. Same accordion as Media & Settings, per
breakpoint, except that Texts & Typography comes first and is open by
default. On edit pages the "Overwrite for this locale" checkboxes sit in a
row above the accordion (they apply to the whole locale, not to one
breakpoint); on create pages they render inline in the Desktop accordion
headers. Media, layout, colours, effects and visibility are **only** applied
for that locale when their own checkbox is ticked; texts and typography
always apply.

| Overwrite checkbox | Stored flag | Covers |
| --- | --- | --- |
| (none — always applied) | — | `title`, `description`, `headlineElement`, `headlineFontSize`, `descriptionFontSize`, `buttonFontSize` |
| "Add button/link" / "Overwrite" (Button / Link) | `overrides.button` | `buttonLabel`, `url` and the whole `linking` group |
| Media | `overrides.media` | the six per-locale image/video slots |
| Layout | `overrides.layout` | `contentHorizontalPosition`, `contentVerticalPosition`, `contentTextAlign`, `contentPadding`, `contentMargin`, `contentWidth`, `contentMaxHeight`, `borderRadius`, `customCssClass` |
| Colors | `overrides.colors` | `textColor`, `headlineColor`, `descriptionColor`, `backgroundColor`, `mediaOverlayColor` |
| Effects | `overrides.effects` | `contentAnimation`, `animationDuration`, `animationDelay`, `backgroundBlurPreset`, `enableTextBlur`, `contentBlurStrength` |
| Visibility | `overrides.visibility` | `hideTitle`, `hideDescription`, `hideButton` |

Values are merged base → fallback-locale translation → current-locale
translation, so a missing translation falls back to the channel's default
locale before it falls back to the untranslated slide. `parallax.strength`
and `video.playback` are not part of the translation form and cannot vary
per locale.

Translations saved before the override flags existed keep working: with no
flag stored, a group counts as overridden when it actually holds data.

## CSS custom properties

Every admin-configurable value reaches the storefront as a CSS custom
property, a class, or a `data-` attribute — that is the whole theming
surface. Slider properties are written as an inline `style` attribute on
`<section class="vanssa-slider">`; slide properties are written into a
`<style>` block scoped by `.vanssa-slide[data-slide-code="…"]`, with the
tablet and mobile variants in `@media (max-width: 1024px)` and
`@media (max-width: 767px)` blocks after it.

### Consumed by the shipped stylesheet

Override these to retheme without touching the plugin's SCSS.

| Property | Fed by | Used for |
| --- | --- | --- |
| `--vanssa-slider-margin-top\|right\|bottom\|left` | slider `margin*` | outer spacing of `.vanssa-slider` |
| `--vanssa-slider-padding-top\|right\|bottom\|left` | slider `padding*` | inner spacing; `arrowsPosition: outside` raises the left/right ones to at least `3.25rem` |
| `--vanssa-slider-max-height` | slider `maxHeight` | `max-height` of `.vanssa-slider__wrapper`, and its fixed `height` when set |
| `--vanssa-slider-radius` | *(nothing)* | `border-radius` of `.vanssa-slider__inner` — theming-only hook, no admin option writes it |
| `--vanssa-slider-speed` | slider `speed` | slide opacity/transform transition |
| `--vanssa-slider-nav-size` | slider `navigationSize` | arrow `font-size` |
| `--vanssa-slider-nav-shadow` | slider `navigationShadow` | arrow `box-shadow` |
| `--vanssa-slider-nav-color` | slider `navigationColor` | arrow glyph colour |
| `--vanssa-slider-nav-bg` | slider `navigationBackgroundColor` | arrow background |
| `--vanssa-slider-pagination-size` | slider `paginationSize` | bullet `font-size` |
| `--vanssa-slider-pagination-shadow` | slider `paginationShadow` | bullet `box-shadow` |
| `--vanssa-slider-pagination-color` | slider `paginationColor` | inactive bullet |
| `--vanssa-slider-pagination-active` | slider `paginationActiveColor` | active bullet and progress bar |
| `--vanssa-slide-content-gutter` | slide `contentWidth` | padding of `.vanssa-slide__content` |
| `--vanssa-slide-content-pos-x\|-pos-y` | slide `contentHorizontalPosition` / `contentVerticalPosition` | flex alignment of the content box |
| `--vanssa-slide-content-shift-x\|-shift-y` | same two settings | `translate()` offset |
| `--vanssa-slide-content-max-width\|-width` | slide `contentWidth` | box width |
| `--vanssa-slide-content-max-height` | slide `contentMaxHeight` | box `max-height` |
| `--vanssa-slide-text-color` | slide `textColor` | content-box colour |
| `--vanssa-slide-background-color` | slide `backgroundColor` | content-box background |
| `--vanssa-slide-radius` | slide `borderRadius` | content-box `border-radius` |
| `--vanssa-slide-padding` | slide `contentPadding` | content-box padding |
| `--vanssa-slide-margin` | slide `contentMargin` | content-box margin |
| `--vanssa-slide-text-align` | slide `contentTextAlign` | content-box `text-align` |
| `--vanssa-slide-bg-blur` | slide `backgroundBlurPreset` | content-box `backdrop-filter` |
| `--vanssa-slide-blur` | slide `contentBlurStrength` | `backdrop-filter` while `.is-blurred` |
| `--vanssa-slide-animation-duration\|-delay` | slide `animationDuration` / `animationDelay` | content animation timing |
| `--vanssa-slide-headline-color\|-size` | slide `headlineColor` / `headlineFontSize` | headline |
| `--vanssa-slide-description-color\|-size` | slide `descriptionColor` / `descriptionFontSize` | description |
| `--vanssa-slide-button-size` | slide `buttonFontSize` | button `font-size` |
| `--vanssa-slide-overlay-color` | slide `mediaOverlayColor` | `.vanssa-slide__overlay` background |
| `--vanssa-slide-parallax-x\|-y` | *(the Stimulus controller at runtime)* | media `translate3d()` |

### Emitted but not consumed by the shipped stylesheet

These mirror the stored value so project CSS and JavaScript can react to it.
Changing them alone changes nothing on a stock install.

`--vanssa-slider-effect`, `--vanssa-slider-container-width`,
`--vanssa-slider-arrows-position`, `--vanssa-slider-arrows-align`,
`--vanssa-slider-pagination-position`, `--vanssa-slider-pagination-style`,
`--vanssa-slider-pagination-shape`, `--vanssa-slider-nav-icon`,
`--vanssa-slider-rewind`, `--vanssa-slider-pause-on-hover`,
`--vanssa-slider-autoplay`, `--vanssa-slider-autoplay-interval`,
`--vanssa-slider-progress`, `--vanssa-slider-overlay`,
`--vanssa-slider-justify-height`, `--vanssa-slider-parallax-strength`,
`--vanssa-slide-animation-name`, `--vanssa-slide-text-blur`.

### Build-time SCSS defaults

If you compile the plugin's SCSS yourself, each custom property's fallback is
a `!default` SCSS variable in `assets/styles/_tokens.scss`. Set them before
importing the plugin styles to change what an *unset* admin option looks
like:

| Variable | Default |
| --- | --- |
| `$vanssa-slider-speed` | `400ms` |
| `$vanssa-slider-nav-size` | `1.5rem` |
| `$vanssa-slider-nav-color` | `#facc15` |
| `$vanssa-slider-nav-bg` | `rgb(17 24 39 / 85%)` |
| `$vanssa-slider-pagination-size` | `0.625rem` |
| `$vanssa-slider-pagination-color` | `rgb(250 204 21 / 45%)` |
| `$vanssa-slider-pagination-active` | `rgb(250 204 21)` |
| `$vanssa-slide-text-color` | `#fff` |
| `$vanssa-slide-content-padding` | `1.25rem` |
| `$vanssa-slide-content-blur` | `12px` |
| `$vanssa-slide-animation-duration` | `500ms` |
| `$vanssa-slide-animation-delay` | `0ms` |
| `$vanssa-content-animations` | the nine animation names — the `is-anim-*` classes and `@keyframes` are generated from this list |

## Fallbacks when a key is absent

A slider or slide saved through the admin stores every setting, so these
fallbacks only matter for records created another way — fixtures, style
presets that set a subset of keys, or your own code. Where the storefront
fallback differs from the admin default, it is the storefront fallback that
applies to those records:

| Setting | Admin default | Fallback when the key is missing |
| --- | --- | --- |
| slider `containerWidth` | `full` | `content` |
| slider `navigationSize` | `3rem` | `1.5rem` |
| slider `showTitle` | on | off |
| slide `headlineElement` | `h1` | `h3` |
| slide `contentVerticalPosition` | `center` | `bottom` |
| slide `contentTextAlign` | `center` | `left` |
| slide `contentPadding` | `1rem` | `1.25rem` (the SCSS token) |
| slide `headlineFontSize`, `descriptionFontSize`, `buttonFontSize` | `1.5rem` / `1rem` / `1.2rem` | `inherit` — the theme's own font sizes |

Every other setting falls back to the same value it defaults to in the admin.

## Changing the accepted values and defaults

The choice lists and defaults above are not hard-coded: they come from the
`vanssa_sylius_slider.presets` config tree, which the plugin ships in
`config/config.yaml` (imported into your project by the Flex recipe — see
[FLEX_RECIPE.md](../FLEX_RECIPE.md)). Redefine any of them in
`config/packages/vanssa_sylius_slider.yaml`:

```yaml
vanssa_sylius_slider:
    presets:
        slider:
            spacing:
                values: ['0', '1rem', '2rem', '3rem', '6rem']
                default: '1rem'
        slide:
            headline_element:
                values: [h1, h2, h3]
                default: h2
```

Catalogue keys, all with a `values` list and a `default`:

- `presets.slider.*` — `container_width`, `spacing`, `slide_effect`,
  `arrows_position`, `arrows_vertical_align`, `pagination_position`,
  `pagination_style`, `navigation_icon`, `navigation_size`,
  `navigation_shadow`, `pagination_shape`, `pagination_size`,
  `pagination_shadow`, `autoplay_interval`, `parallax_strength` (whose
  default is `null`, i.e. parallax stays off until a strength is chosen).
- `presets.slide.*` — `headline_element`, `content_horizontal_position`,
  `content_vertical_position`, `content_text_align`, `content_animation`,
  `animation_duration`, `animation_delay`, `background_blur_preset`,
  `content_blur_strength`, `content_padding`, `content_margin`,
  `content_width`, `content_max_height`, `border_radius`,
  `headline_font_size`, `description_font_size`, `button_font_size`,
  `linking_type`, `button_appearance`, `button_size`, `button_position`.
- `presets.color_switcher.theme` (`classic`, `monolith`, `nano`),
  `.default_representation` (`HEX`, `RGBA`, `HSLA`, `HSVA`, `CMYK`) and
  `.swatches.text` / `.neutral` / `.accent` — the colour lists offered by
  every colour field. Slider navigation and pagination colours are
  restricted to `swatches.accent`, so shrinking that list narrows what an
  editor may pick.

A `default` that is not in its own `values` list is ignored and the first
entry of the list is used instead — no error is raised, the field simply
preselects something you did not ask for.

Three values differ between the plugin's `config/config.yaml` and the
compiled-in defaults of the configuration class, so a project that registers
the bundle **without** importing `config/config.yaml` gets `headline_element:
div`, `content_vertical_position: bottom` and `content_text_align: left`
instead of `h1`, `center` and `center`. Import the file (the Flex recipe does
it for you) if you want the documented defaults.

The admin preview iframe pulls in your storefront Encore entrypoints so it
looks like the real shop. Change them if your build uses other names
(`"build:entry"` or `"entry"`):

```yaml
vanssa_sylius_slider:
    preview:
        shop_entrypoints:
            - 'shop:shop-entry'
            - 'app.shop:app-shop-entry'
            - 'app.shop:plugin-shop-entry'
```

## Style presets

A style preset is a label plus a flat map of **dot paths to scalar values**,
applied to the form in one click. The path is relative to the form root, so
it is `settings.` followed by the keys used throughout this reference:

- Slider presets — `settings.<field>`, including
  `settings.autoplay.enabled|interval|pauseOnHover` and
  `settings.parallax.strength`.
- Slide presets — `settings.responsive.<desktop|tablet|mobile>.<field>` and
  `settings.linking.<field>`.

```yaml
vanssa_sylius_slider:
    style_presets:
        slider:
            brand_carousel:
                label: 'Brand Carousel'
                settings:
                    settings.slideEffect: 'fade'
                    settings.speed: 700
                    settings.autoplay.enabled: true
                    settings.autoplay.interval: 6000
        slide:
            brand_hero:
                label: 'Brand Hero'
                settings:
                    settings.responsive.desktop.headlineColor: 'rgba(250, 204, 21, 1)'
                    settings.responsive.desktop.backgroundColor: 'rgba(15, 23, 42, 0.75)'
                    settings.responsive.desktop.contentAnimation: 'fade-up'
                    settings.linking.buttonAppearance: 'primary'
```

Config presets are read-only in the admin and labelled *config*; presets
created under *Slider Management → Style Presets* are labelled *custom*, and
a database preset reusing a config preset's `code` replaces it.

![Style presets](../screenshots/admin-style-presets-index.png)

Shipped slide presets: `hero_dark`, `clean_light`, `minimal`, `bold_center`,
`split_left_light`, `gradient_overlay`, `glass_card`, `bottom_banner`,
`promo_badge_right`. Shipped slider presets: `classic_arrows`,
`minimal_fade`, `autoplay_showcase`, `fullscreen_hero`, `compact_banner`,
`parallax_showcase`.

A preset only writes the paths it lists; every other field keeps its current
value. Nothing is persisted until you save the form — except a slider created
from a database preset that carries source slides, which is built server-side
at `/admin/sliders/new/from-preset/{code}` with the slides cloned into
independent copies.
