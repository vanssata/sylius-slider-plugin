# Architecture

A map of the plugin for people who are going to change it. Every path,
class, service id, route name, config key and hook name below exists in the
repository; follow the paths if you want the detail.

The plugin is a Symfony bundle (`sylius-plugin` Composer type) plus an npm
package. Composer package `vanssa/sylius-slider-plugin`, PHP namespace
`Vanssa\SyliusSliderPlugin\` (PSR-4 over `src/`), npm package
`@vanssa/sylius-slider-plugin` (rooted at `assets/`).

## The bootable kernel is in vendor/, not in tests/

This is the single thing that trips people up, so read it before anything
else.

The application you boot — for the browser, PHPUnit, Behat and Playwright —
is `vendor/sylius/test-application`. It is a Composer dev dependency:

- `composer.json` sets `extra.public-dir` to
  `vendor/sylius/test-application/public` (that is where `assets:install`
  publishes), and the `nginx` compose service sets
  `WORKING_DIR=/srv/sylius/vendor/sylius/test-application`.
- `phpunit.xml.dist` bootstraps
  `vendor/sylius/test-application/config/bootstrap.php` and sets
  `KERNEL_CLASS=Sylius\TestApplication\Kernel`.
- `behat.yml.dist` points `FriendsOfBehat\SymfonyExtension` at the same
  bootstrap and kernel class.
- The console binary is `vendor/bin/console`.

`tests/TestApplication/` is **not** an application. It contains no kernel
and cannot be booted. It only contributes files that the vendor kernel
pulls in, via environment variables read from
`tests/TestApplication/.env`:

| Variable | Value | Effect |
| --- | --- | --- |
| `SYLIUS_TEST_APP_BUNDLES_PATH` | `tests/TestApplication/config/bundles.php` | Bundles merged on top of the vendor `config/bundles.php` — this is what registers `VanssaSyliusSliderPlugin` and `TurboBundle`. |
| `SYLIUS_TEST_APP_CONFIGS_TO_IMPORT` | `@VanssaSyliusSliderPlugin/tests/TestApplication/config/config.yaml` | Extra container config imported by `Kernel::registerContainerConfiguration()`. |
| `SYLIUS_TEST_APP_ROUTES_TO_IMPORT` | `@VanssaSyliusSliderPlugin/tests/TestApplication/config/routes.yaml` | Extra routes imported by `Kernel::loadRoutes()`. |

`vendor/sylius/test-application/config/packages/twig.yaml` additionally
sets `twig.default_path` to
`%kernel.project_dir%/../../../tests/TestApplication/templates`, and
`tests/TestApplication/src/` is autoloaded under
`Tests\Vanssa\SyliusSliderPlugin\` (`autoload-dev` in `composer.json`) and
mapped as a Doctrine entity directory by
`tests/TestApplication/config/config.yaml`.

Consequences worth remembering:

- Editing `vendor/sylius/test-application/**` is editing a Composer
  dependency. It is wiped by `composer install`.
- Adding a bundle for the dev app means editing
  `tests/TestApplication/config/bundles.php`, not the vendor file.
- `tests/TestApplication/config/config.yaml` imports
  `@VanssaSyliusSliderPlugin/config/config.yaml`, so the plugin's own
  config tree is loaded through the test app, exactly the way a consuming
  project would load it.

Nothing on the dev host runs PHP or Node directly — everything goes through
containers (`docker compose run --rm php <cmd>`,
`docker compose run --rm nodejs "<one shell string>"`,
`docker compose exec -T php <cmd>`) or a `make` target. See
[Commands](#commands-all-containerised) at the end.

## Directory tree

```text
.
├── assets/                         npm package @vanssa/sylius-slider-plugin
│   ├── package.json                symfony.controllers manifest — 14 controllers
│   ├── controllers.json            repo-level mirror of the manifest defaults
│   ├── admin/
│   │   ├── controllers/            12 admin Stimulus controllers
│   │   ├── controllers.json        per-context manifest, admin Encore build
│   │   ├── entrypoint.js           Turbo drive opt-out, sidebar focus, admin SCSS
│   │   ├── styles/                 accordion / preview modal / preview panel / …
│   │   └── utils/                  apply_preset_fields.js (shared by 2 controllers)
│   ├── shop/
│   │   ├── controllers/            slider_controller.js, slide_video_controller.js
│   │   ├── controllers.json        per-context manifest, shop Encore build
│   │   ├── entrypoint.js           comment-only; kept as the plugin-shop-entry file
│   │   └── styles/slider.scss      storefront CSS, autoimported by "slider"
│   ├── styles/_tokens.scss         $vanssa-* !default build-time theme values
│   └── fixtures/                   demo images + video used by SliderDemoFixture
├── config/
│   ├── config.yaml                 plugin config, sylius_resource, form theme
│   ├── services.xml                service wiring (prototype + explicit services)
│   ├── services/fixtures.xml       SliderDemoFixture, sylius_fixtures.fixture tag
│   ├── fixtures.yaml               suite vanssa_sylius_slider_demo
│   ├── grids/admin/                slider.yaml, slide.yaml, style_preset.yaml
│   ├── routes/                     admin.yaml, shop.yaml
│   └── twig_hooks/                 admin/{slider,slide,style_preset}.yaml, shop.yaml
├── Resources/public/preset-mockups/  7 SVGs published by assets:install
├── src/
│   ├── VanssaSyliusSliderPlugin.php  Bundle class; getPath() returns the repo root
│   ├── DependencyInjection/        Extension (load + prepend), Configuration
│   ├── Entity/                     Slider, Slide, their translations, StylePreset
│   ├── Repository/                 3 Sylius resource EntityRepository subclasses
│   ├── Factory/                    SliderFactory, SlideFactory
│   ├── Form/                       Type/, Type/Settings/, Type/Translation/,
│   │                               DataTransformer/
│   ├── Twig/                       Twig functions + Component/{Admin,Shop}
│   ├── Controller/Admin/           4 invokable preview / panel controllers
│   ├── Controller/Shop/            SliderController (attribute routes)
│   ├── Context/Admin/              PreviewChannelContext
│   ├── Preview/                    PreviewBreakpointFlattener
│   ├── Preset/                     provider, catalog, capture, dot-path applier
│   ├── Renderer/                   SliderStructuralSettings
│   ├── Cloner/                     SlideCloner
│   ├── Video/                      provider interface, registry, YouTube provider
│   ├── Service/                    UploadedMediaStorage
│   ├── Menu/                       AdminMenuListener
│   ├── Fixture/                    SliderDemoFixture
│   └── Migrations/                 10 DoctrineMigrations\Version*.php files
├── templates/
│   ├── admin/                      hook templates, grid fields, preview pages
│   ├── components/vanssa_sylius_slider/  Twig component templates (admin + shop)
│   ├── shop/                       show/banner pages + CMS integration partial
│   └── form/theme/color_picker.html.twig
├── translations/messages.en.yaml   bundle translation catalogue
├── features/                       Behat feature files (admin/, shop/)
├── tests/
│   ├── Unit/                       pure PHPUnit, no kernel
│   ├── Functional/                 kernel-booting PHPUnit tests
│   ├── Behat/                      contexts + suite definitions
│   ├── e2e/                        Playwright specs (admin/, shop/, docs/)
│   └── TestApplication/            config + templates + src merged into the
│                                   vendor kernel (see the section above)
├── docs/                           this documentation
└── vendor/sylius/test-application/ THE BOOTABLE KERNEL (Composer dev dependency)
```

## Bundle class and DI extension

`src/VanssaSyliusSliderPlugin.php` is a plain `Bundle` using
`SyliusPluginTrait`. It overrides `getPath()` to return the repository root
(`\dirname(__DIR__)`), which is why `config/`, `templates/`, `translations/`
and `Resources/public/` sit at the top level instead of under `src/`. It is
also why templates resolve as
`@VanssaSyliusSliderPlugin/admin/...` (from `templates/admin/...`) and why
`Resources/public/preset-mockups/*.svg` is published by `assets:install` to
`/bundles/vanssasyliussliderplugin/preset-mockups` — the path
`Vanssa\SyliusSliderPlugin\Preset\MockupCatalog` hard-codes.

`src/DependencyInjection/VanssaSyliusSliderExtension.php` extends
`AbstractResourceExtension` (Sylius resource bundle) and implements
`PrependExtensionInterface`.

`load()` processes the config tree and sets three container parameters, then
loads `config/services.xml`:

- `vanssa_sylius_slider.presets`
- `vanssa_sylius_slider.style_presets`
- `vanssa_sylius_slider.preview.shop_entrypoints`

`prepend()` does three things:

1. Prepends `sylius_grid.templates.action` with three custom grid action
   types — `slide_edit_modal`, `slider_preview`, `slide_preset_create`.
   Grid action types are resolved from this **global** map; the
   `options.template` key inside `config/grids/admin/*.yaml` does not select
   a template, it only reaches the template as a Twig variable. The names
   are per-resource on purpose because the map has no per-grid scoping.
2. Prepends a Doctrine ORM mapping named `VanssaSyliusSliderPlugin`
   (`is_bundle: false`, attribute driver, dir `src/Entity`, prefix
   `Vanssa\SyliusSliderPlugin\Entity`).
3. Calls `prependDoctrineMigrations()` from
   `PrependDoctrineMigrationsTrait`, with namespace `DoctrineMigrations`,
   directory `@VanssaSyliusSliderPlugin/src/Migrations`, and
   `Sylius\Bundle\CoreBundle\Migrations` declared as executed before.

`src/DependencyInjection/Configuration.php` builds the
`vanssa_sylius_slider` tree:

- `preview.shop_entrypoints` — list of Encore entrypoints (`"build:entry"`
  or `"entry"`) injected into the admin preview so it looks like the
  storefront. Defaults to `shop:shop-entry`, `app.shop:app-shop-entry`
  and `app.shop:plugin-shop-entry`.
- `presets.slider.*`, `presets.slide.*`, `presets.color_switcher.*` — each
  node is a `values` list plus a `default`, feeding the form choices.
- `style_presets.slide` / `style_presets.slider` — one-click bundles, each
  `label` plus a `settings` map of dot paths relative to the form root
  (`settings.responsive.desktop.textColor`,
  `settings.autoplay.enabled`, …). Shipped defaults live in
  `Configuration::defaultSlideStylePresets()` and
  `defaultSliderStylePresets()`.

`config/config.yaml` re-declares most of the `presets` tree with the values
this repository ships to the dev app; it also imports
`grids/**/*.yaml`, `twig_hooks/**/*.yaml` and `fixtures.yaml`, and registers
`@VanssaSyliusSliderPlugin/form/theme/color_picker.html.twig` as a Twig form
theme.

## Service wiring

`config/services.xml` has one `defaults` block (autowire, autoconfigure,
private) with three bound arguments — `$projectDir`, `$sliderPresets`,
`$stylePresets` — and one `prototype` over `../src/*` excluding
`DependencyInjection`, `Entity`, `Factory`, `Migrations` and the bundle
class. Everything else in `src/` is therefore a service by convention.

Explicit definitions on top of the prototype:

| Service | Why it is explicit |
| --- | --- |
| `Controller\Shop\SliderController` | public (routed controller) |
| `Controller\Admin\SliderPreviewController` | public, plus `$profiler` with `on-invalid="null"` |
| `Controller\Admin\SlidePreviewController` | same |
| `Controller\Admin\SlideEditPanelController` | same |
| `Controller\Admin\SlideCreatePanelController` | same, plus `$slideFactory` bound to `vanssa_sylius_slider.factory.slide` |
| `Context\Admin\PreviewChannelContext` | tagged `sylius.context.channel` with priority `192` |
| `Repository\StylePresetRepository` | alias of `vanssa_sylius_slider.repository.style_preset` (the resource layer builds it; the prototype cannot) |
| `Video\YouTubeVideoProvider` | tagged `vanssa_sylius_slider.video_provider` |

`config/services.xml` also imports everything under `config/services/`.
The one file there, `config/services/fixtures.xml`, registers
`Fixture\SliderDemoFixture` with the tag `sylius_fixtures.fixture` and the
alias `vanssa_slider_demo`.

## Entities and Doctrine mapping

Mapping is attribute-based on the entity classes; the mapping is registered
by the extension's `prepend()` (not by bundle auto-detection).

| Class | Table | Notes |
| --- | --- | --- |
| `Entity\Slider` | `vanssa_sylius_slider` | `ResourceInterface`, `TranslatableInterface`, `Stringable`. `code` unique, `settings` JSON. |
| `Entity\SliderTranslation` | `vanssa_sylius_slider_translation` | unique on (`slider_id`, `locale_code`) |
| `Entity\Slide` | `vanssa_sylius_slide` | `ResourceInterface`, `TranslatableInterface`. `slide_settings` and `content_settings` JSON, `channel_codes` JSON. |
| `Entity\SlideTranslation` | `vanssa_sylius_slide_translation` | unique on (`slide_id`, `locale_code`); carries its own media columns and `slide_settings` JSON |
| `Entity\StylePreset` | `vanssa_sylius_style_preset` | `TYPE_SLIDER` / `TYPE_SLIDE` constants; `settings` JSON is the flat dot-path map |

Relations:

- `Slide` ↔ `Slider` is **many-to-many**, owning side on `Slide::$sliders`,
  join table `vanssa_sylius_slide_slider`. `Slider::$slides` is the inverse
  side, ordered by `position`, then `id`.
- `StylePreset` ↔ `Slide` is many-to-many through
  `vanssa_sylius_style_preset_slide`. These are the *source* slides of a
  slider preset; they are cloned, never linked, when a slider is created
  from the preset.
- `Slider::$translations` and `Slide::$translations` are `OneToMany` with
  `indexBy: 'localeCode'` and `orphanRemoval: true`, so a translation is
  addressable by locale and disappears with its parent.

Two pieces of behaviour live in the entity rather than in a service:

- **Slider-local ordering.** `Slider::getSlideOrder()` /
  `setSlideOrder()` read and write `settings['slideOrder']` (a list of slide
  ids). `Slider::getOrderedSlides()` sorts by that list first, then by
  `Slide::getPosition()`, then by id. The same slide can therefore appear in
  several sliders at different positions.
- **Locale overlay.** `Slider::getLocalizedSettings()` and
  `Slide::getLocalizedSlideSettings()` overlay the translation's settings on
  top of the base settings for a locale, with a fallback locale.

Media columns store either a self-hosted `/media/...` path (written by
`Service\UploadedMediaStorage`, which moves the upload under
`%kernel.project_dir%/public/media/<subdir>` with a random file name) or a
normalized external video URL (see [Video layer](#video-layer)).

## Resources, factories, repositories

Registered in `config/config.yaml` under `sylius_resource.resources`:

| Alias | Model | Repository | Factory | Form |
| --- | --- | --- | --- | --- |
| `vanssa_sylius_slider.slider` | `Entity\Slider` | `Repository\SliderRepository` | `Factory\SliderFactory` | `Form\Type\SliderType` |
| `vanssa_sylius_slider.slide` | `Entity\Slide` | `Repository\SlideRepository` | `Factory\SlideFactory` | `Form\Type\SlideType` |
| `vanssa_sylius_slider.style_preset` | `Entity\StylePreset` | `Repository\StylePresetRepository` | (default) | `Form\Type\StylePresetType` |

Repository methods used elsewhere:

- `SliderRepository::findEnabledOneByCode()` — joins and orders slides in
  one query; `findEnabledOneByCodeForChannel()` adds the channel/locale
  availability check via `Slider::isAvailableForChannel()`.
- `SlideRepository::findEnabledOneByCode()`.
- `StylePresetRepository::findEnabledByType()` and
  `findEnabledOneByCodeAndType()` — the latter is called from a route
  expression (see [Routes](#routes)).

Factories are constructed by the resource layer with only the model class
name, so they cannot take dependencies. Both create their helpers inline:

- `SlideFactory::createForSlider(Slider)` — new slide pre-attached to a
  slider.
- `SliderFactory::createFromStylePreset(StylePreset)` — applies the preset's
  dot-path settings with `Preset\DotPathApplier` and clones every source
  slide with `Cloner\SlideCloner`.

## Grids

Three grids in `config/grids/admin/`, all `doctrine/orm`:

- `vanssa_sylius_slider_admin_slider` — fields `code`, `name`,
  `slidesCount` (Twig field,
  `@VanssaSyliusSliderPlugin/admin/grid/field/slider_slides_count.html.twig`),
  `enabled`. Item actions: `slider_preview` (custom type), `update`,
  `delete`.
- `vanssa_sylius_slider_admin_slide` — fields `cover`, `code`, `name`,
  `slider`, `enabled` (three Twig field templates under
  `templates/admin/grid/field/`). Main action `slide_preset_create`, item
  actions `slide_edit_modal` and `delete` — there is no plain `update` item
  action, editing goes through the modal panel.
- `vanssa_sylius_slider_admin_style_preset` — fields `mockup`, `code`,
  `label`, `type`, `position`, `enabled`; a `select` filter on `type`
  (slide/slider) that the admin menu links into via `routeParameters`.

The three custom action types map to templates through the
`sylius_grid.templates.action` prepend described above; the templates are
`templates/admin/slide/grid/action/edit_modal.html.twig`,
`templates/admin/slider/grid/action/preview.html.twig` and
`templates/admin/slide/grid/action/preset_create.html.twig`.

## Routes

`config/routes/admin.yaml` is imported by the dev app under the `/admin`
prefix (`tests/TestApplication/config/routes.yaml`). It defines, in this
order — custom routes first, because the resource route imports would
otherwise swallow the paths:

| Route name | Path | Controller |
| --- | --- | --- |
| `vanssa_sylius_slider_admin_slider_preview` | `/sliders/{id}/preview` | `Controller\Admin\SliderPreviewController` |
| `vanssa_sylius_slider_admin_slide_preview` | `/slides/{id}/preview` | `Controller\Admin\SlidePreviewController` |
| `vanssa_sylius_slider_admin_slide_edit_panel` | `/slides/{id}/edit-panel` | `Controller\Admin\SlideEditPanelController` |
| `vanssa_sylius_slider_admin_slide_create_panel` | `/slides/create-panel/{sliderId}` | `Controller\Admin\SlideCreatePanelController` |
| `vanssa_sylius_slider_admin_slide_create_for_slider` | `/slides/new/{sliderId}` | resource `createAction` with `factory.method: createForSlider` |
| `vanssa_sylius_slider_admin_slider_create_from_preset` | `/sliders/new/from-preset/{presetCode}` | resource `createAction` with `factory.method: createFromStylePreset` |

The last two are Sylius resource routes with a `factory` block; the factory
argument is an expression, for example:

```yaml
factory:
    method: createFromStylePreset
    arguments:
        - 'expr:notFoundOnNull(service("vanssa_sylius_slider.repository.style_preset").findEnabledOneByCodeAndType($presetCode, "slider"))'
```

Then three `sylius.resource` route imports produce the CRUD routes
(`..._index`, `..._create`, `..._update`, `..._delete`; `show` is excluded,
`redirect: update`). They set `vars.hook_prefix` to
`vanssa_sylius_slider_admin.slider` / `.slide` / `.style_preset` and use
`@SyliusAdmin\shared\crud` templates. Resulting admin URLs: `/admin/sliders/`,
`/admin/slides/`, `/admin/style-presets/`.

`config/routes/shop.yaml` imports attribute routes from
`src/Controller/Shop/SliderController.php`:

| Route name | Path | Action |
| --- | --- | --- |
| `vanssa_sylius_slider_shop_slider_show` | `/slider/{code}` | renders `@VanssaSyliusSliderPlugin/shop/slider/show.html.twig` |
| `vanssa_sylius_slider_shop_banner_show` | `/banner/{code}` | renders `@VanssaSyliusSliderPlugin/shop/slider/banner.html.twig` |

Neither shop route is locale-prefixed. Both 404 when the resource is
missing, disabled, or unavailable for the current channel.

## Form types

`src/Form/Type/` holds the three resource forms plus the reusable colour
field; `src/Form/Type/Settings/` holds the nested settings forms that map
onto the JSON columns; `src/Form/Type/Translation/` holds the per-locale
forms.

- `SliderType` — `code`, `enabled`, `channels` (`ChannelChoiceType`),
  `settings` (`SliderSettingsType`), `translations`
  (`ResourceTranslationsType` → `SliderTranslationType`). `code` is
  re-added on `PRE_SET_DATA` with `disabled: true` when the slider already
  has an id, i.e. it is immutable on edit. A `POST_SUBMIT` listener copies
  the selected channels into `Slider::$channelCodes`.
- `SlideType` — `sliders` (`EntityType`), `channels`, six unmapped
  `FileType` upload fields (`slideCoverFile`, `slideCoverMobileFile`,
  `slideCoverTabletFile`, and the three video equivalents), three video URL
  text fields, `position`, `enabled`, `settings` (`SlideSettingsType`),
  `addButton`/`buttonLabel`/`url`, `translations`.
- `StylePresetType` — the `settings` child gets a
  `Form\DataTransformer\JsonArrayTransformer` model transformer, so the
  dot-path map is edited as JSON text.
- `ColorPickerType` — parent `TextType`, own block prefix, reads its
  defaults from `Preset\SettingsPresetProvider` (`color_switcher.theme`,
  `color_switcher.default_representation`). Rendered by
  `templates/form/theme/color_picker.html.twig` and driven by the
  `vanssa-rgba-color-picker` Stimulus controller.

Settings forms mirror the JSON structure exactly:

```text
SliderSettingsType                       (Slider::$settings)
├── autoplay          AutoplaySettingsType
├── parallax          ParallaxSettingsType
├── responsive        ResponsiveSliderSettingsWrapperType
│   └── tablet, mobile  SliderResponsiveBreakpointSettingsType
└── scalar options    containerWidth, slideEffect, arrows*, pagination*, …

SlideSettingsType                        (Slide::$slideSettings)
├── linking           SlideLinkingSettingsType
├── responsive        SlideResponsiveSettingsType
│   └── desktop, tablet, mobile  SlideResponsiveBreakpointSettingsType
├── parallax          ParallaxSettingsType   (only when include_parallax)
└── video             VideoSettingsType      (only when include_video)

SliderSettingsOverrideType               (SliderTranslation::$settings)
├── base              SliderResponsiveBreakpointSettingsType
└── responsive        ResponsiveSliderSettingsWrapperType
```

`SlideTranslationType` follows the same idea with explicit opt-in
checkboxes — `overrideMedia`, `overrideLayout`, `overrideColors`,
`overrideEffects`, `overrideVisibility` — deciding which parts of the base
slide the locale actually replaces.

Choice options are not hard-coded in the form types: they call
`SettingsPresetProvider::values()` and `safeDefault()` with the
`presets.slider.*` / `presets.slide.*` config node names, so a project can
change the offered values without touching PHP.

## Twig hooks

Admin CRUD pages are composed entirely with Sylius Twig Hooks
(`config/twig_hooks/admin/*.yaml`). `config/twig_hooks/shop.yaml` is
deliberately empty (`hooks: { }`) — the storefront is rendered by
components, not hooks.

The pattern per resource (`slider` and `slide`, with `style_preset` a
reduced version):

```text
sylius_admin.<res>.update.content
└── form            → admin/<res>/form/workspace.html.twig     (priority 150)
    └── sylius_admin.<res>.update.content.form
        ├── sections   disabled
        └── content    → admin/shared/form/content.html.twig
            └── …content.form.content
                └── sections → admin/<res>/form/sections.html.twig
                    └── …content.sections
                        ├── general      (400)
                        ├── settings     (300, slider only)
                        ├── media        (200, slide only)
                        ├── translations (200 / 100)
                        └── slides       (100, slider only)
```

Two details in those files are load-bearing and are commented in place:

- The workspace hookable is named `form` on purpose. It **overrides** the
  vendor's `sylius_admin.common.update.content` → `form` fallback. Any
  other name and both render, so the form appears twice.
- `sylius_admin.<res>.<create|update>.content.form.sections.general` is
  explicitly disabled (`default: { enabled: false }`) to kill the legacy
  fallback branch that would otherwise duplicate the fields.

The modal slide editor has its own hook tree that does not hang off the
Sylius admin prefix at all: `vanssa_sylius_slider.slide_edit_panel` with
children `.general` and `.translations`. It reuses the same section
templates as the full edit page.

## Twig components

Registered with attributes on the classes in `src/Twig/Component/`; the
templates live in `templates/components/vanssa_sylius_slider/`.

| Component name | Class | Kind |
| --- | --- | --- |
| `vanssa_sylius_slider:shop:slider` | `Component\Shop\SliderComponent` | Twig component |
| `vanssa_sylius_slider:shop:slide` | `Component\Shop\SlideComponent` | Twig component |
| `vanssa_sylius_slider:shop:homepage_slider` | `Component\Shop\HomepageSliderComponent` | **Live** component, `code` prop |
| `vanssa_sylius_slider:admin:slider_preview_panel` | `Component\Admin\SliderPreviewPanelComponent` | Twig component |
| `vanssa_sylius_slider:admin:slide_preview_panel` | `Component\Admin\SlidePreviewPanelComponent` | Twig component |
| `vanssa_sylius_slider:admin:preset_gallery` | `Component\Admin\PresetGalleryComponent` | Twig component |
| `vanssa_sylius_slider:admin:slider_slides_preview` | `Component\Admin\SliderSlidesPreviewComponent` | **Live** component |
| `vanssa_sylius_slider:admin:slider_slide_browser` | `Component\Admin\SliderSlideBrowserComponent` | **Live** component |

`SliderComponent::getEnabledSlides()` filters by `Slide::isEnabled()` and
channel availability and returns `Slider::getOrderedSlides()`;
`getSettings()` returns the locale-overlaid settings. The slide template
resolves those into `--vanssa-slide-*` custom properties and
`data-vanssa-*` attributes on `.vanssa-slide`.

The two admin Live components talk to each other with events:
`SliderSlideBrowserComponent` (search / membership filter / pagination /
staged `pending` attach-detach map, applied in one action) emits, and
`SliderSlidesPreviewComponent` listens with
`#[LiveListener('vanssa:slider-slides-changed')]` to re-render its list.

`PresetGalleryComponent` has two modes: `apply` (on create pages — picking a
preset fills the open form client-side) and `choose` (on grid index pages —
every card is a link to the create page carrying `?preset=<code>`).
Database slider presets that own source slides instead link to
`vanssa_sylius_slider_admin_slider_create_from_preset`, so the cloning
happens server-side.

## Twig functions

Defined with `#[AsTwigFunction]` on plain services in `src/Twig/`:

| Function | Class | Returns |
| --- | --- | --- |
| `sylius_slider_by_code(code)` | `Twig\SliderExtension` | enabled `Slider` or null |
| `sylius_slide_by_code(code)` | `Twig\SliderExtension` | enabled `Slide` or null |
| `sylius_slider_render_content(content)` | `Twig\SliderExtension` | `Markup`; routes through `monsieurbiz_richeditor_render_field` when that filter exists, otherwise passes through |
| `vanssa_route_exists(name)` | `Twig\SliderExtension` | bool |
| `vanssa_slider_structural_maps(settings)` | `Twig\SliderStructuralExtension` | `{desktop, tablet, mobile}` maps from `Renderer\SliderStructuralSettings` |
| `vanssa_video_embed_url(reference, autoplay)` | `Twig\VideoEmbedExtension` | embed URL for an external reference, null for self-hosted media |
| `vanssa_slider_admin_locales()` | `Twig\Admin\AdminLocalesExtension` | all Sylius locales (the drawer is a hook template and cannot inject a repository) |
| `vanssa_slider_preset_mockups()` | `Twig\Admin\MockupCatalogExtension` | bundled mockup list |
| `vanssa_slider_preview_shop_entrypoints()` | `Twig\Admin\PreviewAssetsExtension` | the configured `preview.shop_entrypoints` |

`Renderer\SliderStructuralSettings` is where the non-CSS-variable settings
(arrows, pagination, container, effect) become effective per-breakpoint
maps: desktop is the base, tablet overlays desktop, mobile overlays tablet,
and empty override values inherit. The storefront controller applies the map
matching the current viewport via `matchMedia`.

## Admin preview layer

The preview is a storefront render embedded in an admin page, so three
problems have to be solved: no channel context, no working media queries,
and unsaved form state.

- **Channel.** `Context\Admin\PreviewChannelContext` implements
  `ChannelContextInterface` and is tagged `sylius.context.channel` with
  priority `192`. It reads the request attribute
  `_vanssa_slider_preview_channel` (constant
  `PreviewChannelContext::REQUEST_ATTRIBUTE`), which the preview controllers
  set before rendering, and throws `ChannelNotFoundException` otherwise —
  so it never hijacks normal requests.
- **Breakpoints.** `Preview\PreviewBreakpointFlattener` bakes one breakpoint
  into the settings server-side. `flattenSliderSettings()` overlays
  `responsive.tablet` and then `responsive.mobile` onto the base settings;
  `flattenSlideSettings()` collapses `responsive.desktop|tablet|mobile` down
  to the effective desktop variant. Both then clear the responsive block.
  Without this the preview frame — which shares the admin viewport — could
  only ever show desktop.
- **Drafts.** Both controllers have an `applyDraftOverrides()` that takes an
  `overrides` parameter (POST body or query string) containing a raw
  bracket-notation form snapshot — `slider[settings][…]`,
  `slider[translations][<locale>][settings][…]` — `parse_str`s it and merges
  it into the in-memory entity. Nothing is flushed.

Both preview controllers accept `channel`, `locale`, `breakpoint` and
`overrides`, call `$this->profiler?->disable()` so the web debug toolbar
stays out of the frame, and render
`templates/admin/{slider,slide}/preview.html.twig`.
`SlidePreviewController` additionally has a `__default__` locale sentinel
that forces every translation lookup to miss, which is how "default"
(untranslated) content is previewed.

`templates/admin/slide/preview/_assets.html.twig` pulls the configured shop
entrypoints' **CSS only**, and imports it into a CSS cascade layer
(`@import url(...) layer(vanssa-shop-preview)`) so storefront Bootstrap
cannot bleed into the admin chrome. It never emits the shop JS bundle —
that bundle starts its own Stimulus application, which would then try to
connect every admin controller already on the page.

`SlideEditPanelController` and `SlideCreatePanelController` serve the real
`SlideType` form as a turbo-frame fragment. Two constraints are encoded
there: the whole form is always round-tripped (a partial one would wipe
omitted fields or break checkbox unchecking), and a failed submission
returns HTTP 422, because Turbo only renders non-2xx form responses into a
frame when the status is 422.

## Preset layer

Two unrelated things share the word "preset". Keep them apart.

**Form presets** (`presets.*` config, `Preset\SettingsPresetProvider`) are
the choice lists and defaults offered by the settings form types.
`values()`, `default()` and `safeDefault()` resolve a
`<section>.<name>.values|default` path out of the injected parameter and
fall back to hard-coded values when the config node is absent.

**Style presets** (`style_presets.*` config plus the `StylePreset` entity)
are one-click bundles applied to a form:

- `Preset\StylePresetProvider` merges config presets with database presets —
  same code, database wins — and converts each dot path into the bracket
  form-field name the client-side applier fills
  (`settings.responsive.desktop.textColor` →
  `slide[settings][responsive][desktop][textColor]`). It exposes
  `slidePresets()` and `sliderPresets()`, memoized per type.
- `Preset\DotPathApplier::apply()` applies such a flat map onto a nested
  array server-side (used by `SliderFactory::createFromStylePreset()`), with
  an optional prefix to strip.
- `Preset\SettingsCapture` does the inverse: flattens an existing
  slide's/slider's settings into the dot-path map, so a preset can be
  bootstrapped from a resource that already looks right.
- `Preset\MockupCatalog` is a deliberate static list of seven bundled SVGs
  (not a directory scan, so it is deterministic and testable) under
  `Resources/public/preset-mockups/`, exposed at
  `/bundles/vanssasyliussliderplugin/preset-mockups/<key>.svg`.
  `defaultMockupFor()` maps the shipped config presets to a fitting image.
- `Cloner\SlideCloner` deep-copies a slide including translations and their
  override flags. Media **paths** are shared — files are not duplicated on
  disk. Change this if you need per-clone media files.

## Video layer

A slide's video slot stores either a self-hosted `/media/...` path or a
normalized external URL. The distinction is made by the provider registry,
not by string inspection in templates.

- `Video\VideoProviderInterface` — `name()`, `supports()`, `normalize()`,
  `embedUrl()`.
- `Video\VideoProviderRegistry` — collects everything tagged
  `vanssa_sylius_slider.video_provider` via `#[AutowireIterator]`.
  `normalize()` returns the first provider's accepted form; `providerFor()`
  and `embedUrl()` resolve a stored reference; `isExternal()` is the
  self-hosted/external test.
- `Video\YouTubeVideoProvider` — the reference implementation. Normalizes
  any accepted input to `https://www.youtube.com/watch?v=<id>` and embeds
  through `youtube-nocookie.com` with `enablejsapi=1`, muted and
  playsinline.

`Twig\VideoEmbedExtension` bridges this into templates: a null return means
"render a `<video>` tag", a URL means "render an `<iframe>`". The
`vanssa-slide-video` Stimulus controller
(`assets/shop/controllers/slide_video_controller.js`) handles visibility
gating and playback for both.

## Stimulus and asset layer

`assets/` is the npm package `@vanssa/sylius-slider-plugin`. It ships 14
Stimulus controllers — 12 admin, 2 shop — registered **only** through the
`@symfony/stimulus-bridge` manifest inside the consuming app's own
`startStimulusApp()`. Neither entrypoint starts a Stimulus application or
calls `app.register()`; if one did, every handler would fire twice.

Four manifests must list the same 14 keys:

| File | Role |
| --- | --- |
| `assets/package.json` (`symfony.controllers`) | authoritative source: `main`, registered `name`, `fetch`, `enabled`, `autoimport` |
| `assets/controllers.json` | this repo's top-level dev mirror, all enabled |
| `assets/admin/controllers.json` | admin Encore build — all 14 enabled and eager |
| `assets/shop/controllers.json` | shop Encore build — `slider` and `slide-video` enabled and eager, the other 12 listed but `enabled: false` |

The per-context files must list all 14 even when a context disables most of
them: the test application's webpack merges each `controllers.json` into the
bridge manifest with a **shallow spread per package key**, so a per-context
object replaces the whole package entry instead of deep-merging. Omitting a
controller silently drops it from that context's build. See
[adding-a-stimulus-controller.md](adding-a-stimulus-controller.md) for the
full procedure and the sync check.

Registered controller identifiers (what templates reference) are the `name`
values: `vanssa-slider`, `vanssa-slide-video`, `vanssa-slider-settings`,
`vanssa-slider-slides-preview`, `vanssa-rgba-color-picker`,
`vanssa-responsive-copy`, `vanssa-preview-frame`, `vanssa-preset-applier`,
`vanssa-preset-gallery`, `vanssa-form-context`, `vanssa-animation-settings`,
`vanssa-image-upload-preview`, `vanssa-mockup-picker`,
`vanssa-modal-portal`.

`assets/admin/entrypoint.js` carries only what must run eagerly outside
Stimulus: `Turbo.session.drive = false` (without it Turbo Drive hijacks
every Sylius admin navigation), the sidebar focus behaviour for
`/admin/(sliders|slides|style-presets)`, and the five admin stylesheet
imports. `assets/shop/entrypoint.js` is comment-only; the file exists
because `vendor/sylius/test-application/webpack.config.js` hard-codes it as
the `plugin-shop-entry` entry. Storefront CSS arrives through the `slider`
controller's `autoimport` of `shop/styles/slider.scss`.

Styling contract, three levels:

- `assets/styles/_tokens.scss` — `$vanssa-slider-*` / `$vanssa-slide-*`
  `!default` build-time values.
- `--vanssa-slider-*` custom properties on `.vanssa-slider`,
  `--vanssa-slide-*` on `.vanssa-slide__content`, written per breakpoint by
  the component templates.
- `data-vanssa-*` attributes on the slide root for values that are not
  visual (headline element, animation, button position/appearance/size,
  parallax strength).

The test application depends on the package as
`"@vanssa/sylius-slider-plugin": "file:../../../assets"`. Yarn classic
*copies* rather than symlinks a `file:` dependency, and a plain
`yarn install` does not refresh the copy when only sources change. The
webpack entries point straight at `../../../assets/**/entrypoint.js`, so
entrypoints and their SCSS are always live, but all 14 controllers are
resolved through the bare specifier — i.e. through the stale copy. The
`nodejs-watch` compose service replaces that copy with a symlink on first
start, which is why watch mode is the supported workflow.

## Migrations

`src/Migrations/` holds ten `DoctrineMigrations\Version*` classes, wired by
`prependDoctrineMigrations()` in the extension. They are ordinary Doctrine
migrations that run in the consuming application's migration namespace.

The history is worth knowing before you write the eleventh:

- `Version20260214180000` creates the original `acme_sylius_slider` /
  `acme_sylius_slide` tables.
- `Version20260215123000` renames them into the `vanssa_` namespace and adds
  translation tables.
- `Version20260215194000` and `Version20260215195500` settle the translation
  tables on `locale_code` and align index names with the Doctrine metadata.
- `Version20260215220000` converts slide→slider from many-to-one to
  many-to-many, with data migration.
- `Version20260216013000` adds `channel_codes` to slides.
- `Version20260216023000` / `...032000` drop base and translated
  title/description columns — that text now lives inside the
  `slide_settings` JSON.
- `Version20260716045546` adds the per-breakpoint cover videos.
- `Version20260718050000` adds `vanssa_sylius_style_preset` and its source
  slide join table.

Two consequences: early migrations reference table names that no longer
exist, so read the whole chain before assuming a column's history; and
settings that live inside JSON columns have no schema, so a settings change
usually needs a defensive read in PHP/Twig rather than a migration.

## Translations

`translations/messages.en.yaml` is the bundle catalogue (the bundle path is
the repository root, so `translations/` is picked up automatically). Keys
are grouped as:

- `vanssa_sylius_slider.ui.*` — labels used by grids, forms and templates.
- `vanssa.sylius.slider.admin.menu.sliders` — the admin menu section label
  used by `Menu\AdminMenuListener`.
- `sylius.ui.*` — a small number of additions to the Sylius namespace.

## Admin menu

`Menu\AdminMenuListener` subscribes to `sylius.menu.admin.main` and adds one
`sliders` section with four children: Sliders, Slides, Slider Presets and
Slide Presets. The two preset entries point at the same
`vanssa_sylius_slider_admin_style_preset_index` route with
`routeParameters: {criteria: {type: slider|slide}}`, which is why the style
preset grid has a `type` select filter.

## Fixtures

`Fixture\SliderDemoFixture` (tag `sylius_fixtures.fixture`, alias
`vanssa_slider_demo`) is exposed as the suite `vanssa_sylius_slider_demo` in
`config/fixtures.yaml`. It seeds a fashion demo matching the test
application's store: a shared pool of slides plus one slider per configured
slider style preset, so every preset can be seen live.

Demo sliders: `fashion-classic-arrows`, `fashion-minimal-fade`,
`fashion-autoplay-showcase`, `fashion-fullscreen-hero`,
`fashion-compact-banner`, `fashion-parallax-showcase`. Demo slides:
`new-collection`, `summer-dresses`, `denim-essentials`, `graphic-tees`,
`street-caps`, `season-sale`, `runway-video`.

`new-collection` is the only slide carrying per-breakpoint layout overrides
(tablet centres the content horizontally, mobile also centres it
vertically). That is deliberate: per-breakpoint layout is the plugin's
headline feature and `tests/e2e/shop/responsive-overrides.spec.ts` asserts
on exactly that slide.

Media comes from `assets/fixtures/images/` and `assets/fixtures/videos/`,
passed through `Service\UploadedMediaStorage` at load time — the fixture
copies files, so the bundled assets are never consumed. Licensing is
documented in `assets/fixtures/LICENSE.md`.

Load with `make load-slider-fixtures`.

## Tests

| Suite | Location | Boots a kernel? |
| --- | --- | --- |
| PHPUnit `unit` | `tests/Unit/` | no |
| PHPUnit `functional` | `tests/Functional/` (base class `FunctionalTestCase`) | yes |
| PHPUnit `integration` | `tests/Integration/` | yes |
| Behat | `features/` + `tests/Behat/Context/` | yes |
| Playwright | `tests/e2e/` | drives the running app |

`phpunit.xml.dist` also defines a `non-unit` suite (functional +
integration) used by CI. Behat contexts are registered in
`tests/Behat/Resources/services.xml`, suites in
`tests/Behat/Resources/suites.yml`.

`playwright.config.ts` has four projects: `desktop` (1400×900), `tablet`
(820×1180), `mobile` (390×844) and `docs-media` (1600×950, `testDir`
`./tests/e2e/docs`). The last one generates the documentation screenshots
and GIFs; it is a generator, not an assertion suite, which is why it is not
part of `make e2e`.

## Request flow, end to end

Storefront, `/slider/fashion-classic-arrows`:

```text
SliderController::showSliderAction
  → SliderRepository::findEnabledOneByCodeForChannel   (channel + locale check)
  → shop/slider/show.html.twig  → shop/slider/_slider.html.twig
  → component vanssa_sylius_slider:shop:slider
      SliderComponent::getSettings()      locale overlay
      SliderComponent::getEnabledSlides() ordering + channel filter
      vanssa_slider_structural_maps()     per-breakpoint structural maps
  → components/…/shop/slider.html.twig    --vanssa-slider-* + data-*
      → components/…/shop/slide.html.twig per slide
  → vanssa-slider Stimulus controller picks the viewport's map at runtime
```

Admin preview, `/admin/sliders/12/preview?locale=en_US&breakpoint=mobile`:

```text
SliderPreviewController::__invoke
  → resolve channel (query, else slider's channels, else first enabled)
  → applyDraftOverrides()     unsaved form state, in memory only
  → flattenForBreakpoint()    PreviewBreakpointFlattener, in memory only
  → request attribute _vanssa_slider_preview_channel
  → admin/slider/preview.html.twig
      → admin/slide/preview/_assets.html.twig  shop CSS in a cascade layer
      → the same shop slider component as above
        (PreviewChannelContext answers the channel lookup)
```

## Where do I put a new X

| You want to add | Touch | Watch out for |
| --- | --- | --- |
| A field on an entity | `src/Entity/<Entity>.php` (attribute mapping) + a new migration in `src/Migrations/` | If the value belongs in an existing JSON settings column, no migration is needed — but nothing validates the JSON either, so read defensively. |
| A slider/slide **setting** (choice) | preset node in `src/DependencyInjection/Configuration.php` under `presets.slider`/`presets.slide`, mirror in `config/config.yaml`, field in the matching `Form/Type/Settings/*Type` via `SettingsPresetProvider::values()`/`safeDefault()`, render in the component template and the admin section template | Booleans defaulting to true must be read as `settings.key ?? true` in Twig; `\|default(true)` turns a stored `false` back into `true`. |
| A form field on a resource | `src/Form/Type/{SliderType,SlideType,StylePresetType}.php` + the hook template that renders it under `templates/admin/<res>/form/sections/` | The field must be rendered by some hookable, or `render_rest: false` silently drops it. |
| A Stimulus controller | `assets/{admin,shop}/controllers/` + all four manifests | See [adding-a-stimulus-controller.md](adding-a-stimulus-controller.md). Manifests are merged at webpack config-load time; the watcher must be restarted after any manifest edit. |
| A route | custom routes at the **top** of `config/routes/admin.yaml` (before the `sylius.resource` imports) or an attribute on `src/Controller/Shop/SliderController.php` | Resource route imports claim broad paths; a custom route declared after them is unreachable. Shop routes are not locale-prefixed. |
| A migration | `src/Migrations/Version<UTC timestamp>.php`, namespace `DoctrineMigrations` | Generate with `docker compose run --rm php vendor/bin/console doctrine:migrations:diff`, then review: the diff also contains the test application's own schema unless you trim it. |
| A template override (in a project) | your app's `templates/bundles/VanssaSyliusSliderPlugin/<same relative path>` | Only works for `@VanssaSyliusSliderPlugin/...` templates. Component templates are referenced from the `#[AsTwigComponent]` attribute, so overriding the file is the way to change them without replacing the class. |
| A hook section in the admin | an entry in `config/twig_hooks/admin/<res>.yaml` + a template under `templates/admin/<res>/` | Disabling the vendor fallback (`default: { enabled: false }`) is often required, otherwise fields render twice. |
| A grid column or action | `config/grids/admin/<res>.yaml`; custom action types also need an entry in the `sylius_grid.templates.action` prepend in `VanssaSyliusSliderExtension::prepend()` | `options.template` on an action does **not** select the template — the global map does. |
| A video provider | implement `Video\VideoProviderInterface`, tag the service `vanssa_sylius_slider.video_provider` | `normalize()` decides ownership of pasted URLs — first accepting provider wins, so a greedy pattern steals other providers' URLs. |
| A style preset | `vanssa_sylius_slider.style_presets.slide\|slider` in project config, or a `StylePreset` row in the admin | Only scalar values survive the dot-path applier. A database preset with the same code overrides the config one. |
| Demo data | `src/Fixture/SliderDemoFixture.php` + media under `assets/fixtures/` | Record the licence in `assets/fixtures/LICENSE.md`. |

## Commands (all containerised)

The host has neither PHP nor Node. Every command below runs in a container.

```bash
make init                 # compose.override.yml, composer install, yarn build, up -d
make up                   # start the stack
make down                 # stop it (make clean also drops volumes)
make database-init        # create the database + run migrations
make load-fixtures        # Sylius core fixtures
make load-slider-fixtures # suite vanssa_sylius_slider_demo
make cc                   # console c:c
make mig                  # Makefile shortcut: vendor/bin/console d:m:mNa

make verify               # ECS --fix + PHPStan + PHPUnit (APP_ENV=test)
make phpunit              # PHPUnit only (APP_ENV=test)
make behat                # Behat only
make phpstan              # PHPStan with phpstan.neon
make ecs                  # ECS over src/
make rector               # Rector dry-run (rector-fix applies)

make node-watch           # start the Encore watcher (compose profile "watch")
make node-watch-logs      # follow it — confirm your edit recompiled
make node-watch-stop      # stop it; nothing stops it for you
make node-build           # one-off build; must not run while the watcher is up

make e2e                  # full Playwright suite (compose profile "e2e")
make e2e-check SPEC=tests/e2e/shop/responsive-overrides.spec.ts
                          # one spec on desktop+tablet+mobile, after waiting
                          # for the watcher to catch up with assets/
make docs-media           # regenerate every screenshot and GIF under docs/
make e2e-down             # remove the Playwright container
```

One-offs that have no `make` target:

```bash
docker compose run --rm php vendor/bin/console debug:router --show-controllers
docker compose run --rm php vendor/bin/console debug:container --tag=vanssa_sylius_slider.video_provider
docker compose run --rm php vendor/bin/console assets:install
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force"
```

`composer ai:verify` is what `make verify` runs inside the php container.
There is no `composer ai:e2e`: Composer runs in the php container and cannot
reach the playwright container, so `make e2e` is the only entry point.

`docker compose run --rm nodejs <cmd>` — the `nodejs` service already has
`entrypoint: ["/bin/sh","-c"]`, so pass the whole shell command as **one**
string argument. Wrapping it in `sh -lc "..."` double-wraps it and the
command silently does nothing.

## Related documents

- [adding-a-stimulus-controller.md](adding-a-stimulus-controller.md) — the
  four-manifest procedure in full.
- [../FLEX_RECIPE.md](../FLEX_RECIPE.md) — what Symfony Flex seeds into a
  consuming project on `composer require`.
