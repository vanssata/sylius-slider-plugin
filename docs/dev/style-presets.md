# Style presets

A style preset is a flat map of dot paths to scalar values plus a label.
The plugin reads presets from two sources (project configuration and the
`vanssa_sylius_style_preset` table), merges them, and turns every dot path
into the bracket-notation form field name that the admin JS fills in.

For the admin-facing view of the same feature see
[../usage/style-presets.md](../usage/style-presets.md).

## Define presets in project configuration

```yaml
# config/packages/vanssa_sylius_slider.yaml
vanssa_sylius_slider:
    style_presets:
        slide:
            brand_hero:
                label: 'Brand Hero'
                settings:
                    settings.responsive.desktop.contentHorizontalPosition: start
                    settings.responsive.desktop.contentVerticalPosition: center
                    settings.responsive.desktop.contentTextAlign: left
                    settings.responsive.desktop.contentPadding: '2rem'
                    settings.responsive.desktop.contentWidth: boxed
                    settings.responsive.desktop.borderRadius: 16
                    settings.responsive.desktop.headlineElement: h1
                    settings.responsive.desktop.headlineFontSize: '2.5rem'
                    settings.responsive.desktop.descriptionFontSize: '1.2rem'
                    settings.responsive.desktop.buttonFontSize: '1.2rem'
                    settings.responsive.desktop.textColor: 'rgba(255, 255, 255, 1)'
                    settings.responsive.desktop.headlineColor: 'rgba(250, 204, 21, 1)'
                    settings.responsive.desktop.descriptionColor: 'rgba(230, 230, 230, 1)'
                    settings.responsive.desktop.backgroundColor: 'rgba(15, 23, 42, 0.75)'
                    settings.responsive.desktop.mediaOverlayColor: 'rgba(0, 0, 0, 0)'
                    settings.responsive.desktop.contentAnimation: fade-up
                    settings.responsive.desktop.animationDuration: 700
                    settings.responsive.desktop.animationDelay: 100
                    settings.responsive.desktop.backgroundBlurPreset: soft
                    settings.responsive.desktop.enableTextBlur: false
                    settings.linking.buttonAppearance: primary
                    settings.linking.buttonSize: lg
                    settings.linking.buttonPosition: content_left
                    settings.parallax.strength: '1rem'
                    addButton: true
        slider:
            brand_carousel:
                label: 'Brand Carousel'
                settings:
                    settings.containerWidth: full
                    settings.slideEffect: fade
                    settings.speed: 700
                    settings.rewind: true
                    settings.showNavigation: true
                    settings.showArrows: false
                    settings.paginationStyle: lines
                    settings.paginationPosition: bottom-inside
                    settings.paginationShape: circle
                    settings.paginationSize: '0.625rem'
                    settings.autoplay.enabled: true
                    settings.autoplay.interval: 5000
                    settings.autoplay.pauseOnHover: true
                    settings.showProgressBar: true
                    settings.parallax.strength: '1rem'
```

Node rules, from `Vanssa\SyliusSliderPlugin\DependencyInjection\Configuration`:

- `label` is required and must not be empty. A preset without it fails
  container compilation.
- `settings` is a free-form variable node, defaulting to `[]`. Keys are dot
  paths relative to the form root; values must be scalars. Non-scalar
  values are silently dropped downstream.
- The key under `slide` / `slider` is the preset code. It must be unique
  within its type.

### Defining `slide` or `slider` replaces the shipped set for that type

Symfony's config processing merges the user-supplied config arrays with
each other and only falls back to a node default when the key is absent
entirely. `style_presets.slide` and `style_presets.slider` each carry the
shipped presets as their *default value*, so this configuration leaves you
with exactly one slide preset and all six shipped slider presets:

```yaml
# Result: slide presets = [brand_hero]  (hero_dark, clean_light, minimal,
#         bold_center, split_left_light, gradient_overlay, glass_card,
#         bottom_banner, promo_badge_right are gone)
#         slider presets = the six shipped ones (key was never touched)
vanssa_sylius_slider:
    style_presets:
        slide:
            brand_hero:
                label: 'Brand Hero'
                settings:
                    settings.responsive.desktop.headlineColor: 'rgba(250, 204, 21, 1)'
```

Two ways out:

```yaml
# 1. Re-declare the shipped presets you want to keep, alongside your own.
#    Copy them from Configuration::defaultSlideStylePresets().
vanssa_sylius_slider:
    style_presets:
        slide:
            hero_dark:
                label: 'Hero Dark'
                settings:
                    settings.responsive.desktop.textColor: 'rgba(255, 255, 255, 1)'
                    settings.responsive.desktop.backgroundColor: 'rgba(15, 23, 42, 0.75)'
            brand_hero:
                label: 'Brand Hero'
                settings:
                    settings.responsive.desktop.headlineColor: 'rgba(250, 204, 21, 1)'
```

```php
// 2. Or add presets as database rows instead — they are additive and never
//    displace the shipped set, except when they reuse the same code.
$preset = new \Vanssa\SyliusSliderPlugin\Entity\StylePreset();
$preset->setCode('brand_hero');
$preset->setType(\Vanssa\SyliusSliderPlugin\Entity\StylePreset::TYPE_SLIDE);
$preset->setLabel('Brand Hero');
$preset->setSettings([
    'settings.responsive.desktop.headlineColor' => 'rgba(250, 204, 21, 1)',
]);
$entityManager->persist($preset);
$entityManager->flush();
```

## Where the configuration ends up

```php
// src/DependencyInjection/VanssaSyliusSliderExtension.php
$container->setParameter('vanssa_sylius_slider.style_presets', $config['style_presets']);
```

```xml
<!-- config/services.xml -->
<defaults autowire="true" autoconfigure="true" public="false">
    <bind key="$stylePresets">%vanssa_sylius_slider.style_presets%</bind>
</defaults>
```

Anything constructor-injecting `array $stylePresets` receives the merged
configuration tree — that is how `StylePresetProvider` and
`Vanssa\SyliusSliderPlugin\Fixture\SliderDemoFixture` get it.

## Config and database presets are merged by the provider

```php
namespace App\Admin;

use Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider;

final class MyPresetConsumer
{
    public function __construct(private readonly StylePresetProvider $provider)
    {
    }

    public function dump(): void
    {
        // Each entry:
        //   label     string
        //   fields    array<string, bool|float|int|string>  bracket field names
        //   source    'config' | 'database'
        //   mockup    string|null                           public image path
        //   slideIds  list<int>                             database presets only
        $slidePresets = $this->provider->slidePresets();
        $sliderPresets = $this->provider->sliderPresets();
    }
}
```

Merge order, from `StylePresetProvider::build()`:

1. every configured preset of that type, in configuration order, tagged
   `source: 'config'`;
2. then `StylePresetRepository::findEnabledByType()` — enabled rows only,
   ordered by `position ASC, label ASC` — tagged `source: 'database'`.

Because step 2 assigns by code into the same array, a database preset with
a config preset's code overwrites the value **in the config preset's
position**, and a database preset with a new code is appended. Results are
memoized per type for the lifetime of the service.

## Dot path to form field name

```php
// Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider::toFieldName()
// $formRoot is StylePreset::TYPE_SLIDE ('slide') or TYPE_SLIDER ('slider'),
// which are also the form block prefixes of SlideType and SliderType.
$name = $formRoot;
foreach (explode('.', $dotPath) as $segment) {
    $name .= '[' . $segment . ']';
}
```

```php
// slide presets
'settings.responsive.desktop.textColor'
    => 'slide[settings][responsive][desktop][textColor]'
'settings.linking.buttonSize'
    => 'slide[settings][linking][buttonSize]'
'addButton'
    => 'slide[addButton]'

// slider presets
'settings.autoplay.enabled'
    => 'slider[settings][autoplay][enabled]'
'settings.speed'
    => 'slider[settings][speed]'
```

No prefix is stripped and no validation happens here: any dot path is
converted, whether or not a matching field exists.

## How the applier fills the form

```js
// assets/admin/utils/apply_preset_fields.js
export function applyPresetFields(fields) {
    for (const [name, value] of Object.entries(fields || {})) {
        const field = document.querySelector(`[name="${CSS.escape(name)}"]`);
        if (!field) {
            continue;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = value === true;
        } else {
            field.value = String(value);
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
```

Consequences worth designing around:

- **A field that is not on the page is skipped, silently.** The base slide
  form builds `settings` with `include_texts => false`, so
  `settings.responsive.<bp>.title` and `.description` have no input there;
  those live under `slide[translations][<locale>][settings]...`. A preset
  carrying them applies nothing for those keys.
- **Booleans must be real booleans.** `field.checked = value === true` —
  `"true"`, `1` and `"1"` all leave the checkbox unchecked.
- **Select values must be existing options.** Assigning a value that is not
  among the `<option>`s leaves the select with nothing selected. Server
  side, the same lists are enforced by `Assert\Choice` constraints built
  from `vanssa_sylius_slider.presets.*.values`.
- **The bubbling `input` + `change` pair is load-bearing.** It is what makes
  `vanssa-rgba-color-picker` repaint its swatch, `vanssa-slider-settings`
  re-run its field gating, and `vanssa-preview-frame` snapshot a new draft.
- Nothing is submitted or persisted. The preset is undone by reloading the
  form without saving.

Two controllers call it:

```js
// assets/admin/controllers/preset_applier_controller.js — preview toolbars
// and the "add slide" panel.
apply(event) {
    const preset = this.presetsValue[event.params.preset];
    if (!preset || !preset.fields) {
        return;
    }

    applyPresetFields(preset.fields);
}
```

```js
// assets/admin/controllers/preset_gallery_controller.js — create pages.
// Also auto-applies ?preset=<code> once when arriving from a grid's
// choose-mode gallery.
const chosen = new URLSearchParams(window.location.search).get('preset');
if (chosen && this.presetsValue[chosen]) {
    requestAnimationFrame(() => applyPresetFields(this.presetsValue[chosen].fields ?? {}));
}
```

## Valid dot paths

Slide presets — form root `slide`:

```yaml
# per breakpoint: settings.responsive.<desktop|tablet|mobile>.<field>
headlineElement:            # h1..h6, div
contentHorizontalPosition:  # start, left_2_12, left_3_12, left_4_12, center,
                            # right_2_12, right_3_12, right_4_12, end
contentVerticalPosition:    # top, top_1_5, top_2_5, top_3_5, top_4_5, center, bottom
contentTextAlign:           # left, center, right
contentAnimation:           # fade-up, fade-down, fade-left, fade-right, zoom-in,
                            # slide-up, flip-in, blur-in, bounce-in, none
animationDuration:          # ms, integer
animationDelay:             # ms, integer
textColor:                  # free CSS colour string
headlineColor:              # free CSS colour string
descriptionColor:           # free CSS colour string
backgroundColor:            # free CSS colour string
mediaOverlayColor:          # free CSS colour string
backgroundBlurPreset:       # none, soft, medium, strong
enableTextBlur:             # boolean
contentBlurStrength:        # integer
contentPadding:             # CSS length
contentMargin:              # CSS length
contentWidth:               # boxed, full
contentMaxHeight:           # none, 20%, 30%, 40%, 50%, 100%
customCssClass:             # free string
borderRadius:               # integer
headlineFontSize:           # CSS length
descriptionFontSize:        # CSS length
buttonFontSize:             # CSS length
hideTitle:                  # boolean
hideDescription:            # boolean
hideButton:                 # boolean
title:                      # translation form only, not on the base slide form
description:                # translation form only, not on the base slide form

# settings.linking.<field>
type:                       # custom, product, category
overlay:                    # boolean
openExternal:               # boolean
showProductFocusImage:      # boolean
buttonAppearance:           # primary, secondary, success, danger
buttonSize:                 # sm, md, lg
buttonPosition:             # content_left, content_center, content_right,
                            # slider_bottom_left, slider_bottom_left_2_12,
                            # slider_bottom_left_3_12, slider_bottom_left_4_12,
                            # slider_bottom_center, slider_bottom_right,
                            # slider_bottom_right_2_12, slider_bottom_right_3_12,
                            # slider_bottom_right_4_12

# settings.parallax.strength   CSS length, or '0' to disable for this slide
# addButton                    boolean, unmapped checkbox on SlideType
```

Slider presets — form root `slider`:

```yaml
# settings.<field>
overlay:                  # boolean
showTitle:                # boolean
containerWidth:           # content, full
marginTop:                # CSS length
marginRight:              # CSS length
marginBottom:             # CSS length
marginLeft:               # CSS length
paddingTop:               # CSS length
paddingRight:             # CSS length
paddingBottom:            # CSS length
paddingLeft:              # CSS length
justifySlideHeight:       # boolean
rewind:                   # boolean
speed:                    # ms, integer (free integer input)
pauseOnHover:             # boolean
slideEffect:              # slide, fade, zoom, lift, flip
maxHeight:                # free CSS size, e.g. 560px / 70vh / 48rem
cssClasses:               # free string
showNavigation:           # boolean
showArrows:               # boolean
arrowsPosition:           # overlay, outside, bottom
arrowsVerticalAlign:      # center, top, bottom
navigationIcon:           # chevron, angle, square
navigationSize:           # CSS length
navigationColor:          # free CSS colour string
navigationBackgroundColor:# free CSS colour string
navigationShadow:         # none, soft, medium, strong, glow
paginationPosition:       # bottom-inside, bottom-outside, top, left, right
paginationStyle:          # dots, lines, numbers
paginationShape:          # circle, square
paginationSize:           # CSS length
paginationShadow:         # none, soft, medium, strong, glow
paginationColor:          # free CSS colour string
paginationActiveColor:    # free CSS colour string
showProgressBar:          # boolean
keyboardNavigation:       # boolean
touchSwipe:               # boolean
lazyLoadMedia:            # boolean

# settings.autoplay.<enabled|interval|pauseOnHover>
# settings.parallax.strength
```

Colour fields derive from `TextType` (see
`Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType`), so any string
applies. The commented value lists above are the shipped ones; each is
configurable, and every value a preset uses must be in the configured list.

## Widen the allowed values for an option

```yaml
# config/packages/vanssa_sylius_slider.yaml
# Each option under presets.slide / presets.slider is its own named node,
# so overriding one leaves the rest at their defaults. `values` replaces
# the whole list for that option — include the values you want to keep.
vanssa_sylius_slider:
    presets:
        slide:
            border_radius:
                values: [0, 6, 10, 12, 16, 20, 24]
                default: 0
            animation_duration:
                values: [250, 400, 500, 600, 700, 1000]
                default: 500
            headline_font_size:
                values: ['0.8rem', '1rem', '1.2rem', '1.5rem', '1.75rem', '2rem', '2.25rem', '2.5rem', '3rem']
                default: '1.5rem'
        slider:
            parallax_strength:
                values: ['0.5rem', '1rem', '1.5rem', '2rem', '3rem', '4rem']
                default: null
```

The read side is `Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider`,
injected into the settings form types:

```php
$values = $this->settingsPresetProvider->values('slide', 'border_radius', [0, 6, 10, 16, 24]);
$default = $this->settingsPresetProvider->safeDefault('slide', 'border_radius', 0, $values);
```

`safeDefault()` falls back to the first configured value when the
configured default is not itself in the list, so a mismatched pair cannot
produce an unselectable default.

## The database preset resource

```yaml
# config/config.yaml — resource registration shipped by the plugin
sylius_resource:
    resources:
        vanssa_sylius_slider.style_preset:
            driver: doctrine/orm
            classes:
                model: Vanssa\SyliusSliderPlugin\Entity\StylePreset
                repository: Vanssa\SyliusSliderPlugin\Repository\StylePresetRepository
                form: Vanssa\SyliusSliderPlugin\Form\Type\StylePresetType
```

```php
// Vanssa\SyliusSliderPlugin\Entity\StylePreset
// Table  vanssa_sylius_style_preset       (unique code, index on type+enabled)
// Join   vanssa_sylius_style_preset_slide (ManyToMany to Slide, ON DELETE CASCADE)
// Migration Version20260718050000
const TYPE_SLIDER = 'slider';
const TYPE_SLIDE  = 'slide';

public function getCode(): string;              // string(64), unique
public function getType(): string;              // string(16)
public function getLabel(): string;             // string(255)
public function getSettings(): array;           // json, dot-path => scalar
public function getMockupImage(): ?string;      // string(1024), public path
public function isEnabled(): bool;
public function getPosition(): int;
public function getSlides(): Collection;        // ordered by position ASC, id ASC
```

```php
// Vanssa\SyliusSliderPlugin\Repository\StylePresetRepository
// Service alias: vanssa_sylius_slider.repository.style_preset
// (aliased in config/services.xml so it can also be autowired by class)
public function findEnabledByType(string $type): array;
public function findEnabledOneByCodeAndType(string $code, string $type): ?StylePreset;
```

The `settings` column holds the same flat dot-path map as configuration.
`StylePresetType` renders it as a JSON textarea through
`Vanssa\SyliusSliderPlugin\Form\DataTransformer\JsonArrayTransformer`,
which throws a `TransformationFailedException` on invalid JSON or on JSON
that does not decode to an array.

## Bootstrap a preset from an existing slide or slider

```php
use Vanssa\SyliusSliderPlugin\Preset\SettingsCapture;

$capture = new SettingsCapture();

// Flattens responsive.<desktop|tablet|mobile>.*, linking.* and parallax.*
// into 'settings.<...>' dot paths.
$slideSettings = $capture->fromSlide($slide);
// ['settings.responsive.desktop.contentTextAlign' => 'left', ...]

// Flattens the whole slider settings array, minus channelCodes and
// slideOrder (instance data, not style).
$sliderSettings = $capture->fromSlider($slider);
// ['settings.autoplay.enabled' => true, 'settings.speed' => 500, ...]
```

`StylePresetType` runs this on `POST_SUBMIT`, but only when the submitted
`settings` map is empty and the matching unmapped `captureSlide` /
`captureSlider` field holds a resource. It also clears `slides` whenever
the type is `slide`.

Note that a slide capture includes `settings.responsive.<bp>.title` and
`.description` if the source slide carries them, and those paths have no
field on the base slide form — see the applier notes above.

## Expand dot paths onto a settings array (server side)

```php
use Vanssa\SyliusSliderPlugin\Preset\DotPathApplier;

$settings = (new DotPathApplier())->apply(
    ['channelCodes' => ['WEB']],           // target
    [                                      // dot paths => scalars
        'settings.autoplay.enabled' => true,
        'settings.autoplay.interval' => 5000,
        'settings.speed' => 500,
    ],
    'settings',                            // prefix to strip
);

// [
//     'channelCodes' => ['WEB'],
//     'autoplay' => ['enabled' => true, 'interval' => 5000],
//     'speed' => 500,
// ]
```

Non-scalar values other than `null` are skipped. Scalar intermediate values
are replaced by arrays when a deeper path needs them.

## Create a slider from a preset, server side

```yaml
# config/routes/admin.yaml
vanssa_sylius_slider_admin_slider_create_from_preset:
    path: /sliders/new/from-preset/{presetCode}
    methods: [GET, POST]
    defaults:
        _controller: vanssa_sylius_slider.controller.slider::createAction
        _sylius:
            section: admin
            permission: true
            template: '@SyliusAdmin/shared/crud/create.html.twig'
            redirect: vanssa_sylius_slider_admin_slider_update
            factory:
                method: createFromStylePreset
                arguments:
                    - 'expr:notFoundOnNull(service("vanssa_sylius_slider.repository.style_preset").findEnabledOneByCodeAndType($presetCode, "slider"))'
            vars:
                route:
                    parameters:
                        presetCode: $presetCode
```

```php
// Vanssa\SyliusSliderPlugin\Factory\SliderFactory
public function createFromStylePreset(StylePreset $preset): Slider
{
    /** @var Slider $slider */
    $slider = $this->createNew();

    $slider->setName($preset->getLabel());
    $settings = (new DotPathApplier())->apply($slider->getSettings(), $preset->getSettings(), 'settings');
    $slider->setSettings($settings);

    $cloner = new SlideCloner();
    foreach ($preset->getSlides() as $sourceSlide) {
        $clone = $cloner->clone($sourceSlide);
        $clone->addSlider($slider);
    }

    return $slider;
}
```

`Vanssa\SyliusSliderPlugin\Cloner\SlideCloner` deep-copies settings,
channel codes, media paths and translations into new rows. Media files on
disk are shared, not duplicated. Clone codes are
`substr($sourceCode, 0, 64 - strlen($suffix)) . '-copy-' . bin2hex(random_bytes(4))`.

## Mockup images

```php
// Vanssa\SyliusSliderPlugin\Preset\MockupCatalog
// Files: Resources/public/preset-mockups/*.svg
// Published by assets:install under
//   /bundles/vanssasyliussliderplugin/preset-mockups
//
// Keys, a deliberate static list rather than a directory scan:
//   dark, light, with-text, center-bold, minimal, gradient, glass
public function all(): array;      // list<array{key, label, path}>
public function pathFor(string $key): ?string;
public function defaultMockupFor(string $type, string $name): ?string;
```

`defaultMockupFor()` is a hard-coded map from shipped config preset codes to
bundled images. There is no `mockup` key in the `style_presets`
configuration tree, so a config preset you define yourself has no thumbnail
and renders the placeholder icon. The same is true of `bottom_banner`,
which the map does not cover.

`MockupCatalog` is `final` and consumers type-hint the concrete class, so it
cannot be decorated. To ship a thumbnail with your own preset, make it a
database preset: `mockupImage` is a plain string rendered into `<img src>`,
so any public path works.

```php
$preset->setMockupImage('/bundles/vanssasyliussliderplugin/preset-mockups/glass.svg');
$preset->setMockupImage('/media/slider/preset-mockups/a1b2c3.webp'); // upload target
$preset->setMockupImage('/build/admin/images/my-preset.png');       // your own build
```

Uploads through the form field `mockupImageFile` go through
`Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage::store($file, 'slider/preset-mockups')`,
which writes to `%kernel.project_dir%/public/media/slider/preset-mockups/`
under a random name and returns the public path.

The bundled list is also available in Twig:

```twig
{% for mockup in vanssa_slider_preset_mockups() %}
    <img src="{{ mockup.path }}" alt="{{ mockup.label }}">
{% endfor %}
```

## Render the gallery yourself

```twig
{# Create-page mode: cards fill the form on the current page. #}
{{ component('vanssa_sylius_slider:admin:preset_gallery', { type: 'slide' }) }}

{# Grid mode: no form exists yet, so every card links to the create page
   with ?preset=<code>; the create page's own gallery applies it. #}
{{ component('vanssa_sylius_slider:admin:preset_gallery', { type: 'slider', mode: 'choose' }) }}
```

`Vanssa\SyliusSliderPlugin\Twig\Component\Admin\PresetGalleryComponent`
props: `type` (`'slide'` or `'slider'`, default `'slide'`) and `mode`
(`'apply'` or `'choose'`, default `'apply'`). The modal element id is
`vanssa-preset-gallery-<type>`, which is what a `data-bs-target` must point
at. In `apply` mode the modal opens itself once on connect; database slider
presets with source slides always get a `createUrl` and link to the
create-from-preset route regardless of mode.

Where the plugin mounts it:

```yaml
# config/twig_hooks/admin/slide.yaml (slider.yaml is the mirror image)
sylius_twig_hooks:
    hooks:
        'sylius_admin.slide.create.content':
            preset_gallery:
                template: '@VanssaSyliusSliderPlugin/admin/slide/form/preset_gallery.html.twig'
                priority: 160
```

```yaml
# config/grids/admin/slide.yaml — the Slides grid's main Create button is
# replaced by a choose-mode gallery trigger. The action type resolves through
# the sylius.grid.templates.action map prepended by the plugin extension.
actions:
    main:
        create:
            type: slide_preset_create
```

## Try-on: the preview override wire format

```twig
{# templates/admin/slider/preview/_panel_body.html.twig #}
<button
    type="button"
    class="dropdown-item"
    data-action="vanssa-preset-applier#apply mouseenter->vanssa-preview-frame#previewPreset mouseleave->vanssa-preview-frame#endPresetPreview focus->vanssa-preview-frame#previewPreset blur->vanssa-preview-frame#endPresetPreview"
    data-vanssa-preset-applier-preset-param="{{ name }}"
    data-vanssa-preview-frame-overrides-param="{{ preset.fields|url_encode }}"
>{{ preset.label }}</button>
```

```js
// assets/admin/controllers/preview_frame_controller.js
previewPreset(event) {
    window.clearTimeout(this.tryOnTimeout);
    const overrides = event.params.overrides ?? '';
    if (overrides === '') {
        return;
    }

    this.tryOnTimeout = window.setTimeout(() => {
        const url = this.currentUrl();
        if (url === null || !this.hasFrameTarget) {
            return;
        }

        const merged = new URLSearchParams(this.readDraft() ?? '');
        for (const [name, value] of new URLSearchParams(overrides)) {
            merged.set(name, value);
        }

        this.element.classList.add('is-preview-pending');
        this.submitDraft(url, merged.toString());
    }, 200);
}
```

The merged string is POSTed as an `overrides` field to the preview route,
where `SliderPreviewController` / `SlidePreviewController` `parse_str()` it
and `array_replace_recursive()` it onto the in-memory entity. Nothing is
flushed, and neither the real form nor the localStorage draft is touched —
`endPresetPreview()` re-renders from the draft-or-saved state.

## Fixtures

```php
// Vanssa\SyliusSliderPlugin\Fixture\SliderDemoFixture::applyStylePreset()
// Same dot-path expansion, applied directly to entity settings so demo data
// matches what the one-click applier would produce.
$preset = $this->stylePresets[$type][$code] ?? [];
foreach ($preset['settings'] ?? [] as $dotPath => $value) {
    $segments = explode('.', (string) $dotPath);
    if ('settings' === ($segments[0] ?? null)) {
        array_shift($segments);
    }
    // ... walk $segments and assign the leaf
}
```

```bash
# Reload the demo data (one slider per shipped slider preset, seven slides
# seeded from slide presets).
make load-slider-fixtures

# Equivalent, without the make wrapper:
docker compose run --rm php vendor/bin/console sylius:fixtures:load vanssa_sylius_slider_demo -n
```

## Override the resource

```yaml
# config/packages/vanssa_sylius_slider.yaml — standard Sylius resource override
sylius_resource:
    resources:
        vanssa_sylius_slider.style_preset:
            classes:
                model: App\Entity\StylePreset
                form: App\Form\Type\StylePresetType
```

Two constraints on a replacement:
`Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider` constructor-injects
the concrete `Vanssa\SyliusSliderPlugin\Repository\StylePresetRepository`,
and `SliderFactory::createFromStylePreset()` type-hints
`Vanssa\SyliusSliderPlugin\Entity\StylePreset`. A replacement repository
must extend the former and a replacement model the latter.

## Tests and commands

The host has neither PHP nor Node; everything runs in containers.

```bash
# Unit coverage for the preset plumbing
docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit tests/Unit/Preset
docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit tests/Unit/DependencyInjection/ConfigurationTest.php

# Functional coverage: the admin CRUD and create-from-preset cloning
docker compose run --rm -e APP_ENV=test php vendor/bin/phpunit tests/Functional/Admin/StylePresetAdminTest.php

# Behat, including the @javascript scenario "Clicking a style preset fills
# the mapped fields" (needs the headless chrome container)
make behat

# ECS --fix + PHPStan + PHPUnit, all with APP_ENV=test
make verify
```

Relevant test files:

- `tests/Unit/Preset/StylePresetProviderTest.php` — dot-path conversion,
  merge order, database-over-config override.
- `tests/Unit/Preset/DotPathApplierTest.php` — prefix stripping, nesting,
  non-scalar skipping.
- `tests/Unit/DependencyInjection/ConfigurationTest.php` — the shipped
  preset codes and that a project can add its own.
- `tests/Functional/Admin/StylePresetAdminTest.php` — index and create form
  render, and `/admin/sliders/new/from-preset/<code>` producing cloned
  slides rather than links.
- `features/admin/slider_editor_ux.feature` — hover then click a preset in
  the preview toolbar, asserting
  `slide[settings][responsive][desktop][textColor]`.

After changing any Stimulus controller under `assets/admin/controllers/`,
recompile through the watcher rather than a one-off build:

```bash
make node-watch          # start `encore dev --watch` as a detached service
make node-watch-logs     # confirm the edit recompiled
make node-watch-stop     # nothing stops it automatically

# The manifests in assets/package.json and the controllers.json files are
# merged at webpack config-load time only — restart the watcher after
# editing any of them, or it keeps building the old controller set.
```
