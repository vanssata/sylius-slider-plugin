
# Screenshots

This document shows plugin setup and usage screens from:

- Sylius Admin (slider management, settings accordion, channel/locale preview)
- Storefront (rendered slider page, desktop and mobile)

Screenshots are captured with **Symfony debug mode disabled** (no web debug
toolbar): start the stack with `ENV=prod` (the compose file passes
`APP_ENV: ${ENV:-prod}` to the php service) or set `APP_ENV=prod` in
`tests/TestApplication/.env.local`, warm the cache, then load demo fixtures:

```bash
docker compose exec -T php sh -lc "vendor/bin/console sylius:fixtures:load vanssa_sylius_slider_demo -n"
```

## Admin: Sliders list

Path: `/admin/sliders`

![Admin sliders list](./screenshots/admin-sliders-index.png)

## Admin: Slides list

Path: `/admin/slides`

![Admin slides list](./screenshots/admin-slides-index.png)

## Admin: Slider editing workspace

Path: `/admin/sliders/{id}/edit` (fixture slider: `fashion-classic-arrows`) — the
live preview is the main surface; the settings drawer (one flat accordion:
General, Layout & Spacing, Behavior, Arrows, Pagination, Autoplay,
Translations, Slides) opens fullscreen with Save/language/breakpoint always
visible:

![Admin slider edit](./screenshots/admin-slider-edit-homepage-main.png)

The preview toolbar (presets left; Settings + fullscreen right):

![Admin slider preview panel](./screenshots/admin-slider-preview-panel.png)

The Slides section's **Add** browser (search + membership filter +
pagination; checking marks pending changes, **Save changes** commits them,
Close discards):

![Add slides browser modal](./screenshots/admin-slider-add-slides-modal.png)

## Admin: Slide edit modal (grid row action)

The slides grid's pencil action opens the same live preview + real slide
form used on the full edit page, in a modal, without leaving the grid:

![Slide edit modal](./screenshots/admin-slide-edit-modal.png)

> **Pending capture** — `admin-slide-edit-modal.png` does not exist yet in
> `docs/screenshots/`; this section is wired up ahead of the capture. Follow
> the debug-off flow above, open a slide row's pencil action from
> `/admin/slides`, and save the screenshot under that filename.

## Admin: Slide edit (per-breakpoint media & settings)

Path: `/admin/slides/{id}/edit` — the Media & Settings card groups image,
video and layout settings per Desktop/Mobile/Tablet breakpoint:

![Slide media and settings](./screenshots/admin-slide-media-settings.png)

The live preview supports language selection (with default-language
fallback) and a resolution switcher:

![Slide live preview](./screenshots/admin-slide-live-preview.png)

## Admin: Slide translations

Per locale: Desktop/Mobile/Tablet tabs with Texts (always applied) and
checkbox-gated media/display-settings overrides:

![Slide translations](./screenshots/admin-slide-translations.png)

## Admin: Style presets

Path: `/admin/style-presets/` — admin-managed presets (merged with the
config-defined ones) with mockup thumbnails:

![Style presets](./screenshots/admin-style-presets-index.png)

The creation pages open a preset gallery first (Blank or a preset):

![Preset gallery](./screenshots/admin-preset-gallery.png)

## Frontend: Slider usage

Path: `/slider/fashion-classic-arrows`

![Frontend slider](./screenshots/frontend-slider-homepage-main.png)

Mobile viewport (390×844):

![Frontend slider mobile](./screenshots/frontend-slider-homepage-main-mobile.png)
