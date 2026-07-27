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

A current `sylius/sylius-standard` ships a lock file with `api-platform` at
`4.3.x`, so a plain `composer require` stops with *"vanssa/sylius-slider-plugin
2.3.2 conflicts with api-platform/doctrine-orm >=4.3"*. Add `-W`
(`--with-all-dependencies`) so the resolver is allowed to move those
transitive packages down:

```bash
composer require vanssa/sylius-slider-plugin -W
```

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
    '["https://api.github.com/repos/Sylius/SyliusRecipes/contents/index.json?ref=flex/main","https://raw.githubusercontent.com/vanssata/sylius-slider-plugin/2.3/flex/index.json","flex://defaults"]'
composer require vanssa/sylius-slider-plugin -W
```

`composer config --json` **replaces** the whole array rather than appending to
it, so list every endpoint the project needs in one command. The first entry
above is Sylius-Standard's own recipe endpoint — omit it and Sylius's recipes
stop resolving. Keep `flex://defaults` last; the list is searched in order.

The recipe writes five things — the bundle entry in `config/bundles.php`,
`config/packages/vanssa_sylius_slider.yaml`,
`config/routes/vanssa_sylius_slider.yaml`, and the plugin's controller blocks
in `assets/shop/controllers.json` and `assets/admin/controllers.json` (see
[Frontend assets](#frontend-assets) below). How the endpoint resolves, how to
regenerate it and how to smoke-test it before a release is documented in
[../FLEX_RECIPE.md](../FLEX_RECIPE.md).

Continue with [Database](#database).

### Option B — wire it by hand

```bash
composer require vanssa/sylius-slider-plugin -W
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
(`src/Migrations`, namespace `VanssaSyliusSliderPluginMigrations`) through the
DI extension, so the standard command picks them up:

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

to your root `package.json` and seeds your root `assets/controllers.json`
with all 14 controllers — but only the storefront pair (`slider`,
`slide-video`) `enabled: true`; the 12 admin controllers seed as `enabled:
false`. This happens whether or not you configured the recipe endpoint in
Option A — see [../FLEX_RECIPE.md](../FLEX_RECIPE.md#what-it-automates).

If you used Option A, the recipe itself (independently of the Flex seeding
above) also patches two per-context files: the storefront pair into
`assets/shop/controllers.json`, and the full 14-controller set into
`assets/admin/controllers.json`. Both `sylius/sylius-standard` and
`vendor/sylius/test-application` build their Stimulus bridge manifest by
merging `[assets/controllers.json, assets/<context>/controllers.json]` with a
shallow spread per package key, so the per-context file **replaces** the
whole `@vanssa/sylius-slider-plugin` object from the root file rather than
merging into it — the per-context entries win in both builds, and the root
seed is only a fallback for a project that has no per-context files at all.

**If you used Option B**, nothing writes those per-context files for you, and
the root seed alone leaves the 12 admin controllers `enabled: false` — the
storefront works, the admin workspace does not. Either enable them in your
root `assets/controllers.json`, or (better, and what the recipe does) copy the
plugin's own `assets/admin/controllers.json` block from
`vendor/vanssa/sylius-slider-plugin/assets/admin/controllers.json` into your
`assets/admin/controllers.json`, and its `assets/shop/controllers.json` block
into yours.

Two of the controllers are the storefront pair (`slider` — Stimulus
identifier `vanssa-slider`, and `slide-video` — `vanssa-slide-video`); the
other twelve drive the admin workspace. The `slider` entry carries an
`autoimport` for `@vanssa/sylius-slider-plugin/shop/styles/slider.scss`,
which is how the storefront CSS reaches your build — you never import that
file yourself. Since 2.3.2 the storefront build only compiles this pair by
default, so a shop bundle no longer pulls in the 12 admin controllers or the
Pickr CSS (`rgba-color-picker`'s `autoimport`) it never used.

**Edge case:** the recipe's patches are line-based (Flex `add-lines`,
inserted right after the `"controllers": {` line). If your
`assets/shop/controllers.json` or `assets/admin/controllers.json` has an
*empty* `"controllers": {}` object, the inserted block lands as a top-level
JSON key instead of inside `controllers` — the file stays valid JSON, but the
bridge silently ignores the entries. If the file or the `"controllers": {`
anchor is missing entirely, Flex prints the block instead of writing it. Both
cases are easy to check for: the block should sit *inside* `controllers`, one
level down from where you'd expect a fresh entry. The fix in either case is
to paste the block into the `controllers` object by hand. Stock Sylius ships
both files non-empty, so this only affects projects that changed them.

Then build:

```bash
yarn install --force
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

#### Entrypoints

The plugin's `assets/admin/entrypoint.js` carries three things that have to run
outside Stimulus, so it has to reach your build one way or another:

- `Turbo.session.drive = false` — the plugin depends on `@hotwired/turbo` for
  its preview frames. Without this line Turbo Drive intercepts every link and
  form submit in the whole Sylius admin, which is not built for it.
- the sidebar behavior that keeps the Slider Management group open on
  `/admin/sliders`, `/admin/slides` and `/admin/style-presets`,
- the admin stylesheets (`accordion.scss`, `preview_panel.scss`,
  `preview_modal.scss`, `rgba_color_picker.scss`,
  `slider_slides_preview.scss`).

Skip it and nothing errors — every controller still loads and runs — but the
settings panel renders inline and expanded instead of as an overlay, every
sidebar group stays expanded, and Turbo Drive hijacks the admin. That silence
is why the recipe now wires it for you.

The **shop** entrypoint is a comment-only file. It registers nothing and
imports nothing — the storefront styles arrive through the `slider`
controller's `autoimport`. Including it is harmless and keeps the contract if a
future release adds eager shop-side code; leaving it out changes nothing today.

Pick **one** of the two wirings below. Doing both loads the admin styles and
the sidebar handler twice.

##### Option A — let the recipe do it (default since 2.3.4)

If you installed through the Flex endpoint, this already happened: the recipe
prepends the imports to your own entrypoints, and there is nothing to do.

```js
// assets/admin/entrypoint.js — added by the recipe
import '@vanssa/sylius-slider-plugin/admin/entrypoint';
```

```js
// assets/shop/entrypoint.js — added by the recipe
import '@vanssa/sylius-slider-plugin/shop/entrypoint';
```

The specifier is the npm package name, which core Flex's
`PackageJsonSynchronizer` guarantees is in your `package.json`; the package
root maps to the plugin's `assets/` directory. Don't rewrite these to a
`@vendor/...` alias — `@vendor` is a project-local Encore alias whose target
differs between applications, and not every project defines one.

`composer remove` strips the imports again, and re-running the recipe will not
duplicate them.

##### Option B — separate Encore entries

The pre-2.3.4 wiring, still supported, and the right choice if you'd rather
keep the plugin's CSS in its own bundle. Delete the imports from Option A
first.

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
