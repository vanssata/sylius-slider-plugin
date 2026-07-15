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

## Admin: Slider edit (settings accordion + preview panel)

Path: `/admin/sliders/1/edit` (fixture slider: `homepage-main`)

![Admin slider edit](./screenshots/admin-slider-edit-homepage-main.png)

The edit page includes the channel/language preview panel with a
desktop/tablet/mobile resolution switcher.

## Frontend: Slider usage

Path: `/slider/homepage-main`

![Frontend slider](./screenshots/frontend-slider-homepage-main.png)

Mobile viewport (390×844):

![Frontend slider mobile](./screenshots/frontend-slider-homepage-main-mobile.png)
