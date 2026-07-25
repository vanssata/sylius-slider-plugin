# Getting started

This page installs the plugin into a Sylius 2.x shop, walks through building
one slider from an empty admin, and loads the demo data.

What you get after this page:

- three admin resources under a **Slider Management** menu group — Sliders,
  Slides and Style Presets,
- two storefront routes, `/slider/{code}` and `/banner/{code}`,
- Twig components you can place anywhere in your own templates (see
  [storefront.md](storefront.md)).

Once it runs, [admin-guide.md](admin-guide.md) covers every admin screen in
detail, [options-reference.md](options-reference.md) lists every setting, and
[style-presets.md](style-presets.md) explains the one-click preset bundles.

## Requirements

| Requirement | Value | Where it is declared |
| --- | --- | --- |
| PHP | `>= 8.3` | `composer.json` `require` |
| Sylius | `^2.1` | `composer.json` `require` |
| `symfony/ux-turbo` | `^2.22` | `composer.json` `require` |

The build matrix in `.github/workflows/build.yaml` runs PHP 8.3, Symfony
`^7.4`, Sylius `~2.1` and `~2.2`, Node 22.x, on MySQL 8.0/8.4 and MariaDB
11.4.

The storefront and admin JavaScript is built with **Webpack Encore plus
`@symfony/stimulus-bridge`** (the Sylius-Standard default). The plugin ships
no AssetMapper importmap entries, so an AssetMapper-only project cannot load
its controllers.

`composer.json` also carries a `conflict` block pinning
`api-platform/metadata`, `api-platform/symfony`,
`api-platform/doctrine-common` and `api-platform/doctrine-orm` below `4.3`.
Composer will refuse to install those packages at `4.3.x` alongside this
plugin; that is deliberate and not an error on your side.

## Install the package

The commands below are the ones you run **in your own shop**. If you are
working inside this repository instead, everything runs in containers — the
development host has neither PHP nor Node — and the console binary is
`vendor/bin/console`:

```bash
docker compose run --rm php vendor/bin/console <command>
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
```

Most of those have a `make` target (`make init`, `make database-init`,
`make load-slider-fixtures`, `make node-build`, `make cc`); they are listed
in [../dev/architecture.md](../dev/architecture.md).

### Option A — the plugin's Flex recipe endpoint

The repository serves its own Flex endpoint, so `composer require` registers
the bundle, the config import and the route imports for you:

```bash
composer config --json extra.symfony.endpoint \
    '["https://raw.githubusercontent.com/vanssata/sylius-slider-plugin/2.3/flex/index.json","flex://defaults"]'
composer require vanssa/sylius-slider-plugin
```

The recipe writes exactly three things — the bundle entry in
`config/bundles.php`, `config/packages/vanssa_sylius_slider.yaml` and
`config/routes/vanssa_sylius_slider.yaml`. How the endpoint resolves, how to
regenerate it and how to smoke-test it before a release is documented in
[../FLEX_RECIPE.md](../FLEX_RECIPE.md).

Continue with [Database](#database).

### Option B — wire it by hand

```bash
composer require vanssa/sylius-slider-plugin
```

```php
// config/bundles.php
return [
    // ...
    Vanssa\SyliusSliderPlugin\VanssaSyliusSliderPlugin::class => ['all' => true],
];
```

```yaml
# config/packages/vanssa_sylius_slider.yaml
imports:
    - { resource: '@VanssaSyliusSliderPlugin/config/config.yaml' }
```

That import pulls in the plugin's grids, admin Twig hooks, the fixture suite
definition, the `color_picker` form theme and the `sylius_resource`
definitions for `vanssa_sylius_slider.slider`, `.slide` and `.style_preset`.
Skip it and the admin pages will not exist.

```yaml
# config/routes/vanssa_sylius_slider.yaml
vanssa_sylius_slider_admin:
    resource: '@VanssaSyliusSliderPlugin/config/routes/admin.yaml'
    prefix: /admin

vanssa_sylius_slider_shop:
    resource: '@VanssaSyliusSliderPlugin/config/routes/shop.yaml'
```

The admin resource is imported under your admin prefix (`/admin` in a stock
Sylius application). If you use a different prefix, the plugin's admin pages
still work, but the sidebar behavior shipped in its admin entrypoint only
recognises paths starting with `/admin/sliders`, `/admin/slides` or
`/admin/style-presets`.

The shop resource is imported without a prefix: the two shop routes are
declared as plain `/slider/{code}` and `/banner/{code}` attributes on
`Vanssa\SyliusSliderPlugin\Controller\Shop\SliderController`, so they are not
locale-prefixed like the rest of the storefront.

### Database

The bundle registers its own migrations directory
(`src/Migrations`, namespace `DoctrineMigrations`) through the DI extension,
so the standard command picks them up:

```bash
bin/console doctrine:migrations:migrate -n
```

The migrations create `vanssa_sylius_slider`, `vanssa_sylius_slide`, their
`_translation` tables, the `vanssa_sylius_slide_slider` join table and
`vanssa_sylius_style_preset`. Without them every admin page throws a Doctrine
"table not found" error.

### Frontend assets

`assets/package.json` is a Symfony UX package: it declares all 14 Stimulus
controllers under `"symfony": { "controllers": { ... } }`, and
`composer.json` carries the `symfony-ux` keyword. On `composer require`,
core Flex's `PackageJsonSynchronizer` therefore adds

```json
{
    "dependencies": {
        "@vanssa/sylius-slider-plugin": "file:vendor/vanssa/sylius-slider-plugin/assets"
    }
}
```

to your root `package.json` and seeds your `assets/controllers.json` with the
plugin's controller block. This happens whether or not you configured the
recipe endpoint in Option A — see
[../FLEX_RECIPE.md](../FLEX_RECIPE.md#what-it-automates).

Two of the controllers are the storefront pair (`slider` — Stimulus
identifier `vanssa-slider`, and `slide-video` — `vanssa-slide-video`); the
other twelve drive the admin workspace. The `slider` entry carries an
`autoimport` for `@vanssa/sylius-slider-plugin/shop/styles/slider.scss`,
which is how the storefront CSS reaches your build — you never import that
file yourself.

Then build:

```bash
yarn install
yarn build
bin/console assets:install
bin/console cache:clear
```

`assets:install` matters even though the plugin has no classic asset
pipeline: it publishes `Resources/public/preset-mockups/*.svg` to
`public/bundles/vanssasyliussliderplugin/preset-mockups`, which is where the
preset gallery reads its thumbnails from
(`Vanssa\SyliusSliderPlugin\Preset\MockupCatalog`). Without it the gallery
cards render with a placeholder icon instead of a mockup.

Yarn Classic **copies** `file:` dependencies into `node_modules` instead of
symlinking them, and a plain `yarn install` does not refresh that copy when
only the plugin's source files changed. After upgrading the plugin, run
`yarn install --force` before `yarn build` if a change does not show up.

#### Encore entries

A Sylius `webpack.config.js` exports several Encore configurations (one per
build). Add the plugin's entrypoints next to your own admin and shop entries,
in the same configuration:

```js
// webpack.config.js — in the configuration that produces the admin build
const path = require('path');

Encore
    .addEntry(
        'app-admin-entry',
        path.resolve(__dirname, './assets/admin/entrypoint.js'),
    )
    .addEntry(
        'plugin-admin-entry',
        path.resolve(__dirname, 'vendor/vanssa/sylius-slider-plugin/assets/admin/entrypoint.js'),
    )
;
```

Then render them where your project already renders its own admin/shop
entries — in a Sylius 2 application those are the templates hooked into
`sylius_admin.base#stylesheets` / `sylius_admin.base#javascripts` and
`sylius_shop.base#stylesheets` / `sylius_shop.base#javascripts`:

```twig
{# templates/admin/javascripts.html.twig #}
{{ encore_entry_script_tags('plugin-admin-entry', null, 'app.admin') }}
```

```twig
{# templates/admin/stylesheets.html.twig #}
{{ encore_entry_link_tags('plugin-admin-entry', null, 'app.admin') }}
```

The third argument is the Encore *build* name the entry belongs to. Sylius
splits its Encore output into several builds — `shop`, `admin`, `app.shop`
and `app.admin` under `webpack_encore.builds` — so the entry name alone is
not enough; use the same build name you registered the entry in.

The **admin** entry is the one you cannot skip. It carries three things that
have to run outside Stimulus:

- `Turbo.session.drive = false` — the plugin depends on `@hotwired/turbo` for
  its preview frames. Without this line Turbo Drive intercepts every link and
  form submit in the whole Sylius admin, which is not built for it.
- the sidebar behavior that keeps the Slider Management group open on
  `/admin/sliders`, `/admin/slides` and `/admin/style-presets`,
- the admin stylesheets (`accordion.scss`, `preview_panel.scss`,
  `preview_modal.scss`, `rgba_color_picker.scss`,
  `slider_slides_preview.scss`).

The **shop** entry is a comment-only file. It registers nothing and imports
nothing — the storefront styles arrive through the `slider` controller's
`autoimport`. Including it is harmless; leaving it out changes nothing.

Neither entrypoint calls `startStimulusApp()` or registers a controller: the
bridge manifest is the only registrar. If you register one of the plugin's
controllers a second time in your own entrypoint, every action on it fires
twice.

## Check the install

```bash
bin/console debug:router | grep vanssa_sylius_slider
```

You should see the three resource route groups plus the custom admin routes
(`..._slider_preview`, `..._slide_preview`, `..._slide_edit_panel`,
`..._slide_create_panel`, `..._slide_create_for_slider`,
`..._slider_create_from_preset`) and the two shop routes.

In the admin, a **Slider Management** group appears in the sidebar with four
items: *Sliders* (`/admin/sliders/`), *Slides* (`/admin/slides/`), *Slider
Presets* and *Slide Presets* (both `/admin/style-presets/`, pre-filtered by
type).

![Admin sliders list](../screenshots/admin-sliders-index.png)

## Your first slider

### 1. Create the slider

*Slider Management → Sliders → Create*. The create page opens the preset
gallery ("Start from a preset") once: pick **Blank** to fill everything
yourself, or a preset card to prefill the form. Config-defined presets carry
a `config` badge, admin-managed ones a `custom` badge. The **Choose preset**
button in the toolbar re-opens the gallery.

![Preset gallery](../screenshots/admin-preset-gallery.png)

Fill in the *General* section:

- **Code** — required, at most 64 characters, must match
  `^[A-Za-z0-9][A-Za-z0-9_-]*$`. It is the `{code}` in the storefront URL and
  becomes read-only after the first save, so pick it deliberately.
- **Enabled** — `Yes` or `No`. A disabled slider returns 404 on the
  storefront and renders nothing where it is embedded.
- **Channels** — checkboxes. *"Leave empty to display this slider on every
  channel."*
- **Css classes** — *"Additional CSS classes applied to the slider root."*
  Only letters, digits, `-`, `_` and spaces pass validation; the value is
  appended to the `class` attribute of the rendered `<section>`.

The remaining settings live in the same accordion: *Layout & Spacing*,
*Behavior & Effects*, *Arrows & Navigation*, *Pagination*, *Autoplay*,
*Translations* and *Slides*. The slider name is a translated field, so it is
edited under *Translations* — if you leave it empty, the code is stored as
the name on save.

Save. You land on the edit workspace: the live preview is the main surface
and the settings open in a drawer, with Save plus the language and
breakpoint switchers always visible in the toolbar.

![Editing workspace](../media/admin-workspace.gif)

### 2. Attach slides

The *Slides* accordion section only works on a saved slider — before the
first save it shows *"Save slider first to preview related slides."*

- **Add** opens a browser over every slide in the admin, with search, a
  membership filter and pagination. Ticking a row marks a pending change;
  **Save changes** commits all of them at once and **Close** discards them.
  Nothing is deleted here — a slide can belong to several sliders.
- **Create** runs the full slide-creation form in a modal, pre-attached to
  this slider.

![Add slides browser](../screenshots/admin-slider-add-slides-modal.png)

You can also work the other way round: *Slider Management → Slides →
Create* opens the slide preset gallery before the form exists, so the choice
travels to the create page as `?preset=<code>` and is applied there. The
slide form itself has a **Sliders** field (autocomplete, multiple) for
attaching the slide to one or more sliders.

### 3. Fill in the slide

The **Media & Settings** card is organised per breakpoint — a Desktop, a
Mobile and a Tablet tab, each with its own cover image, its own optional
video and its own layout settings. Two fields sit above the tabs because
they are slide-global rather than per breakpoint: *Parallax* and *Video
playback*.

![Slide media and settings](../screenshots/admin-slide-media-settings.png)

Each breakpoint tab is itself an accordion: *Media*, *Texts & Typography*,
*Layout*, *Colors & Surface*, *Effects*, *Visibility*, plus a *Button / Link*
item on the Desktop tab (the button is slide-global, not per breakpoint).

Three rules decide what a visitor actually sees:

- Media and layout values left empty on Mobile/Tablet fall back to the
  Desktop values. The **Copy settings from desktop** button in each
  non-desktop tab pre-fills them so you can then edit a single value.
- A video set on a breakpoint replaces that breakpoint's image.
- **The headline and description are not on this form.** *Texts &
  Typography* here holds only the typography (headline element, font sizes);
  the texts themselves are per locale and live under *Translations*. A slide
  saved without opening *Translations* renders as a bare image with no
  headline on the storefront.

![Slide translations](../screenshots/admin-slide-translations.png)

Inside one locale, texts and typography always apply. Media, layout, colors,
effects, visibility and the button are only taken from that locale when you
tick the matching **Overwrite** checkbox — so you can translate colors
without also freezing the layout.

Set *Enabled* to `Yes` on the slide as well: the storefront filters out
disabled slides and slides whose channel list excludes the current channel.

### 4. Put it on the storefront

The quickest check is the built-in route — with a slider coded
`homepage-main`:

```text
/slider/homepage-main
```

For placing the slider inside your own pages (homepage hook, Twig component,
CMS block), continue with [storefront.md](storefront.md).

## Demo fixtures

The plugin ships a fixture suite named `vanssa_sylius_slider_demo`
(`config/fixtures.yaml`), themed to match the fashion catalogue of
`sylius/test-application`:

```bash
bin/console sylius:fixtures:load vanssa_sylius_slider_demo -n
```

`sylius:fixtures:load` takes the suite name as a **positional argument** —
there is no `--suite` option, and passing one aborts the command.

In this repository's Docker environment, the host has neither PHP nor Node,
so use the make target instead (it runs the same command in the `php`
container):

```bash
make load-slider-fixtures
```

The suite creates six sliders — one per shipped slider style preset, each
seeded from that preset's settings and with its heading hidden
(`showTitle: false`):

| Slider code | Style preset |
| --- | --- |
| `fashion-classic-arrows` | `classic_arrows` |
| `fashion-minimal-fade` | `minimal_fade` |
| `fashion-autoplay-showcase` | `autoplay_showcase` |
| `fashion-fullscreen-hero` | `fullscreen_hero` |
| `fashion-compact-banner` | `compact_banner` |
| `fashion-parallax-showcase` | `parallax_showcase` |

They share a pool of seven slides — `new-collection`, `summer-dresses`,
`denim-essentials`, `graphic-tees`, `street-caps`, `season-sale` and
`runway-video` (a self-hosted video slide, Big Buck Bunny). Each slide gets
a desktop and a mobile image from the media bundled under
`assets/fixtures/`; attribution is in `assets/fixtures/LICENSE.md`.

`new-collection` is the one demo slide that carries per-breakpoint layout
overrides: tablet centres the content box horizontally, mobile also centres
it vertically. Every other slide cascades from desktop unchanged, so putting
`new-collection` next to one of them is the quickest way to see the
breakpoint cascade at work — that is exactly what the responsive GIF in
[storefront.md](storefront.md) shows.

Re-running the suite updates the existing sliders and slides by code instead
of duplicating them, but it also **resets each demo slider's slide list** to
the fixture's own list.

## Where uploads go

Images and videos uploaded in the admin are stored by
`Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage` under
`%kernel.project_dir%/public/media/slider/...` and referenced in the database
as `/media/slider/...`. That directory must be writable by the web user, and
it must survive deployments — the database keeps only the path.

In this repository the bootable kernel is `vendor/sylius/test-application`
(`composer.json`, `extra.public-dir`), so demo uploads land in
`vendor/sylius/test-application/public/media/slider/`.

## Upgrading

```bash
composer update vanssa/sylius-slider-plugin
bin/console doctrine:migrations:migrate -n
yarn install --force
yarn build
bin/console assets:install
bin/console cache:clear
```

The `--force` is not optional after an upgrade: without it Yarn Classic keeps
serving the previously copied version of the plugin's controllers. Check
[../../CHANGELOG.md](../../CHANGELOG.md) for new config keys and migrations
before deploying.
