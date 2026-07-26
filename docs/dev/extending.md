# Extending the plugin

Every example below is a complete file you can copy into a Sylius project that
has `vanssa/sylius-slider-plugin` installed. Paths starting with `src/`,
`config/` or `templates/` are **your project's** paths unless stated otherwise;
paths starting with `vendor/vanssa/sylius-slider-plugin/` are the plugin's own
files, shown for reference.

- [Domain model](#domain-model)
- [Where things are wired](#where-things-are-wired)
- [Override a template](#override-a-template)
- [Add a form field](#add-a-form-field)
- [Add a built-in slider option (plugin contributors)](#add-a-built-in-slider-option-plugin-contributors)
- [Replace or decorate a service](#replace-or-decorate-a-service)
- [Listen to events](#listen-to-events)
- [Add a video provider](#add-a-video-provider)
- [Add style presets](#add-style-presets)
- [Replace a Stimulus controller](#replace-a-stimulus-controller)
- [Integrate with other plugins](#integrate-with-other-plugins)
- [The admin preview](#the-admin-preview)
- [Evolving the settings schema](#evolving-the-settings-schema)
- [Extend the demo fixtures](#extend-the-demo-fixtures)

## Domain model

| Class | Table |
| --- | --- |
| `Vanssa\SyliusSliderPlugin\Entity\Slider` | `vanssa_sylius_slider` |
| `Vanssa\SyliusSliderPlugin\Entity\SliderTranslation` | `vanssa_sylius_slider_translation` |
| `Vanssa\SyliusSliderPlugin\Entity\Slide` | `vanssa_sylius_slide` |
| `Vanssa\SyliusSliderPlugin\Entity\SlideTranslation` | `vanssa_sylius_slide_translation` |
| `Vanssa\SyliusSliderPlugin\Entity\StylePreset` | `vanssa_sylius_style_preset` |

`Slider` and `Slide` are many-to-many through `vanssa_sylius_slide_slider`
(owning side: `Slide::$sliders`). A slide can therefore appear in several
sliders; per-slider ordering is not a column but a list of slide ids stored in
`Slider::$settings['slideOrder']` and read back by
`Slider::getOrderedSlides()`. `StylePreset` has its own many-to-many to
`Slide` (`vanssa_sylius_style_preset_slide`) — those are the source slides a
slider preset clones from.

All five classes are non-`final`, as are the three repositories. Every other
class under `src/` is `final` — see
[Replace or decorate a service](#replace-or-decorate-a-service) for what that
implies.

Settings are JSON columns, not columns per option:

```text
Slider::$settings              → containerWidth, slideEffect, speed, autoplay{},
                                 parallax{}, responsive{tablet,mobile}, …
Slider::$settings.slideOrder   → list<int> of slide ids
Slider::$settings.channelCodes → list<string>, via get/setChannelCodes()
SliderTranslation::$settings   → per-locale overrides merged by
                                 Slider::getLocalizedSettings($locale, $fallback)
Slide::$slideSettings          → linking{}, parallax{}, video{},
                                 responsive{desktop,tablet,mobile}
Slide::$contentSettings        → media accessibility metadata
                                 (slideCoverAlt, slideCoverTitle, …)
SlideTranslation::$slideSettings, ::$contentSettings
                               → per-locale overrides merged by
                                 Slide::getLocalizedSlideSettings(...)
StylePreset::$settings         → flat dot-path map, e.g.
                                 "settings.responsive.desktop.textColor"
```

`Slide::$contentSettings` has no editing surface today.
`SlideContentSettingsType` and `SlideCoverContentSettingsType` exist under
`src/Form/Type/Settings/`, but no form type, template or configuration file
references them. The demo fixture is the only writer — it stores
`slideCover.alt` and `slideCover.title` — `SlideCloner` copies the array onto
clones, and nothing reads it back: the storefront template takes the image
`alt` and `title` from the slide's localized name.
`Slide::getLocalizedContentSettings()` works if you fill the column yourself,
but rendering the values is your job.

## Where things are wired

```text
vendor/vanssa/sylius-slider-plugin/
├── config/
│   ├── config.yaml               # twig.form_themes, vanssa_sylius_slider.presets,
│   │                             # sylius_resource.resources.*
│   ├── services.xml              # prototype registration + explicit services/tags
│   ├── services/fixtures.xml     # SliderDemoFixture (alias vanssa_slider_demo)
│   ├── fixtures.yaml             # suite vanssa_sylius_slider_demo
│   ├── grids/admin/{slider,slide,style_preset}.yaml
│   ├── routes/admin.yaml         # custom routes + 3 sylius.resource route sets
│   ├── routes/shop.yaml          # imports SliderController attributes
│   └── twig_hooks/admin/{slider,slide,style_preset}.yaml
├── src/                          # PHP
├── templates/                    # Twig, namespace @VanssaSyliusSliderPlugin
├── assets/                       # 14 Stimulus controllers + SCSS
└── Resources/public/preset-mockups/*.svg
```

`config/twig_hooks/shop.yaml` exists but declares no hooks — storefront
rendering goes through Twig components, not hooks.

Routes you can reference by name:

```text
# shop (not locale-prefixed)
vanssa_sylius_slider_shop_slider_show          GET  /slider/{code}
vanssa_sylius_slider_shop_banner_show          GET  /banner/{code}

# admin, custom
vanssa_sylius_slider_admin_slider_preview      GET|POST  /sliders/{id}/preview
vanssa_sylius_slider_admin_slide_preview       GET|POST  /slides/{id}/preview
vanssa_sylius_slider_admin_slide_edit_panel    GET|POST  /slides/{id}/edit-panel
vanssa_sylius_slider_admin_slide_create_panel  GET|POST  /slides/create-panel/{sliderId}
vanssa_sylius_slider_admin_slide_create_for_slider    GET|POST  /slides/new/{sliderId}
vanssa_sylius_slider_admin_slider_create_from_preset  GET|POST  /sliders/new/from-preset/{presetCode}

# admin, generated by sylius.resource (index/create/update/delete)
vanssa_sylius_slider_admin_slider_*
vanssa_sylius_slider_admin_slide_*
vanssa_sylius_slider_admin_style_preset_*
```

## Override a template

The bundle class is `VanssaSyliusSliderPlugin` and `getPath()` returns the
package root, so the Twig namespace `@VanssaSyliusSliderPlugin` maps to
`vendor/vanssa/sylius-slider-plugin/templates`. Symfony resolves a
project-level file at the same relative path first.

Copy the plugin file, then edit your copy:

```bash
mkdir -p templates/bundles/VanssaSyliusSliderPlugin/admin/slider/form/sections/general
cp vendor/vanssa/sylius-slider-plugin/templates/admin/slider/form/sections/general/cssClasses.html.twig \
   templates/bundles/VanssaSyliusSliderPlugin/admin/slider/form/sections/general/cssClasses.html.twig
```

```twig
{# templates/bundles/VanssaSyliusSliderPlugin/admin/slider/form/sections/general/cssClasses.html.twig #}
<div class="col-12">
    {{ form_row(hookable_metadata.context.form.settings.cssClasses, {
        help: 'Space-separated classes. Our design system prefixes them with "shop-".',
    }) }}
</div>
```

Admin section templates are rendered as Twig Hookables, so they receive
`hookable_metadata.context` rather than plain `form` — `.context.form` is the
`SliderType`/`SlideType` form view, `.context.form.settings` its settings
subtree.

Instead of copying the file you can point the hookable at a template of your
own, or turn it off entirely:

```yaml
# config/packages/vanssa_sylius_slider_hooks.yaml
sylius_twig_hooks:
    hooks:
        # Point the existing hookable at your own template.
        'sylius_admin.slider.update.content.form.content.sections.general':
            cssClasses:
                template: 'admin/slider/my_css_classes.html.twig'
                priority: 100

        # Drop the "Translations" accordion item from the update page.
        'sylius_admin.slider.update.content.form.content.sections':
            translations:
                enabled: false
```

The full hook tree the plugin ships lives in
`vendor/vanssa/sylius-slider-plugin/config/twig_hooks/admin/`. Two traps in
there are worth repeating:

- The workspace hookable on `sylius_admin.slider.update.content` is named
  `form` on purpose, so that it overrides the vendor's fallback `form`
  hookable from `sylius_admin.common.update.content`. Under any other name both
  render and the slider form appears twice.
- The plugin sets `configuration.render_rest: false` on those hookables. A
  form field that no template renders is silently absent from the page (and
  therefore submitted as empty), it does not fall through to a generic
  renderer.

Storefront markup is not hook-driven. Override
`templates/bundles/VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/shop/slider.html.twig`
(or `slide.html.twig`) the same way, or stop calling the plugin's component and
render your own.

## Add a form field

The settings forms have `data_class => null` (they map onto the JSON arrays)
and `allow_extra_fields => true`, so a Symfony form type extension is enough —
no subclassing of the `final` form types.

Three pieces are required: the extension, a template that renders the field
(the admin uses `render_rest: false`), and something that reads the value.

```php
<?php
// src/Form/Extension/SliderSettingsTypeExtension.php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SliderSettingsType;

final class SliderSettingsTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('appBadge', ChoiceType::class, [
            'required' => false,
            'label' => 'Corner badge',
            'help' => 'Ribbon rendered in the top-right corner of the slider.',
            'choices' => ['None' => '', 'New' => 'new', 'Sale' => 'sale'],
            'constraints' => [new Assert\Choice(['choices' => ['', 'new', 'sale']])],
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [SliderSettingsType::class];
    }
}
```

```twig
{# templates/admin/slider/app_badge.html.twig #}
<div class="col-12 col-md-6">
    {{ form_row(hookable_metadata.context.form.settings.appBadge) }}
</div>
```

```yaml
# config/packages/vanssa_sylius_slider_hooks.yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.slider.create.content.form.content.sections.general':
            app_badge:
                template: 'admin/slider/app_badge.html.twig'
                priority: 50
        'sylius_admin.slider.update.content.form.content.sections.general':
            app_badge:
                template: 'admin/slider/app_badge.html.twig'
                priority: 50
```

```twig
{# templates/bundles/VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/shop/slider.html.twig #}
{# … copy of the plugin template … #}
{% if settings.appBadge|default('') is not empty %}
    <span class="app-slider-badge app-slider-badge--{{ settings.appBadge }}"></span>
{% endif %}
```

Two things to get right:

- The value lands in `Slider::$settings['appBadge']` only because the parent
  form's data is an array. Register the extension on `SliderSettingsType`, not
  on `SliderType` — the latter has `data_class => Slider::class` and would need
  a real property.
- A boolean that defaults to *on* must be read as `settings.myFlag ?? true` in
  Twig. `settings.myFlag|default(true)` turns a stored `false` back into
  `true`, because Twig's `default` filter tests for falsiness, not for absence.

The same recipe works for `SlideSettingsType`,
`SlideResponsiveBreakpointSettingsType`, `SliderResponsiveBreakpointSettingsType`
and the translation types under
`Vanssa\SyliusSliderPlugin\Form\Type\Translation\`. For per-breakpoint fields,
extend the breakpoint type and remember that an empty override value means
"inherit from the wider breakpoint" (`PreviewBreakpointFlattener::overlay()`
and `SliderStructuralSettings` both skip `null`, `''` and `[]`).

## Add a built-in slider option (plugin contributors)

Projects can only override preset nodes that already exist. Adding a *new*
choice-typed option means editing the plugin itself; this is the sequence the
existing options (`arrowsPosition`, `paginationStyle`, …) follow.

1. Declare the preset node so projects can retune values and default:

```php
// src/DependencyInjection/Configuration.php — inside presets.slider
->arrayNode('arrows_gap')
    ->addDefaultsIfNotSet()
    ->children()
        ->arrayNode('values')->scalarPrototype()->end()->defaultValue(['0', '0.5rem', '1rem'])->end()
        ->scalarNode('default')->defaultValue('0.5rem')->end()
    ->end()
->end()
```

2. Read it in the form type through `SettingsPresetProvider` and extend
   `empty_data`:

```php
// src/Form/Type/Settings/SliderSettingsType.php
$arrowsGapValues = $this->settingsPresetProvider->values('slider', 'arrows_gap', ['0', '0.5rem', '1rem']);

$builder->add('arrowsGap', ChoiceType::class, [
    'choices' => self::remChoices($arrowsGapValues),
    'help' => 'Gap between the arrows and the slider edge.',
    'constraints' => [new Assert\Choice(['choices' => $arrowsGapValues])],
]);

// configureOptions(), inside 'empty_data':
'arrowsGap' => $this->settingsPresetProvider->safeDefault('slider', 'arrows_gap', '0.5rem', $arrowsGapValues),
```

`safeDefault()` falls back to the first configured value when a project
configures a default that is not in its own `values` list, so a bad project
config cannot produce an unselectable choice.

3. Render it in `templates/admin/slider/form/sections/general/settings.html.twig`.
   If it only makes sense while another toggle is on, wrap it in one of the
   containers the `vanssa-slider-settings` controller knows:

```twig
<div class="row" data-slider-settings-arrows-only>
    <div class="col-12 col-md-6">{{ form_row(form.arrowsGap) }}</div>
</div>
```

Recognised gating attributes (see
`assets/admin/controllers/slider_settings_controller.js`):
`data-slider-settings-custom-only`, `-product-only`, `-navigation-only`,
`-arrows-only`, `-autoplay-only`, `-button-only`,
`data-slider-settings-<media|layout|colors|effects|visibility>-override-only`
and `data-slider-settings-item-guard="<name>"`. Only the controller identifier
carries the `vanssa-` prefix; the literal attributes do not.

4. Consume it on the storefront, in
   `templates/components/vanssa_sylius_slider/shop/slider.html.twig`. Visual
   values belong in a CSS custom property or a modifier class; behavioural
   values belong in the `sliderOptions` map passed to the controller:

```twig
{% set sliderOptions = sliderOptions|merge({ arrowsGap: settings.arrowsGap|default('0.5rem') }) %}
```

If the option must be able to differ per breakpoint without a page reload, add
it to `SliderStructuralSettings` as well (namespace
`Vanssa\SyliusSliderPlugin\Renderer`). Its `maps()` result is what
`vanssa_slider_structural_maps()` puts into `sliderOptions.responsive`, and what
`slider_controller.js` re-applies on `matchMedia` changes.

## Replace or decorate a service

Only these plugin classes are non-`final` and therefore extendable:
`Slider`, `Slide`, `SliderTranslation`, `SlideTranslation`, `StylePreset`,
`SliderRepository`, `SlideRepository`, `StylePresetRepository`.

Everything else — `StylePresetProvider`, `SettingsPresetProvider`,
`MockupCatalog`, `SettingsCapture`, `DotPathApplier`, `SlideCloner`,
`VideoProviderRegistry`, `UploadedMediaStorage`, `SliderStructuralSettings`,
`PreviewBreakpointFlattener`, the factories, the Twig components and all form
types — is `final`, and their consumers type-hint the concrete class. A
`#[AsDecorator]` on one of them cannot be constructed (you cannot extend a
`final` class), and Symfony's `CheckTypeDeclarationsPass` rejects a plain class
swap in debug mode. Use the extension points below instead.

### Replace the admin form type

`sylius_resource` resolves `classes.form` by class name; the replacement does
not have to extend the plugin's type.

```yaml
# config/packages/vanssa_sylius_slider.yaml
sylius_resource:
    resources:
        vanssa_sylius_slider.slider:
            classes:
                form: App\Form\Type\MySliderType
```

### Replace a repository

```php
<?php
// src/Repository/SliderRepository.php

declare(strict_types=1);

namespace App\Repository;

use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository as BaseSliderRepository;

final class SliderRepository extends BaseSliderRepository
{
    /** @return list<Slider> */
    public function findEnabledForChannelCode(string $channelCode): array
    {
        /** @var list<Slider> $sliders */
        $sliders = $this->createQueryBuilder('slider')
            ->andWhere('slider.enabled = true')
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter(
            $sliders,
            static fn (Slider $slider): bool => $slider->isAvailableForChannel($channelCode, 'en_US'),
        ));
    }
}
```

```yaml
# config/packages/vanssa_sylius_slider.yaml
sylius_resource:
    resources:
        vanssa_sylius_slider.slider:
            classes:
                repository: App\Repository\SliderRepository
```

The subclass keeps `SliderRepository` as its type, so the plugin's own
constructor type-hints still resolve — `SliderController`, `SliderExtension`,
`HomepageSliderComponent`, `SliderPreviewController`,
`SlideCreatePanelController`, `SliderSlideBrowserComponent`,
`SliderSlidesPreviewComponent` and `SliderDemoFixture` all inject the concrete
class, so a replacement that does *not* extend it fails at container compile
time.

### Add a channel context

`PreviewChannelContext` is registered with
`<tag name="sylius.context.channel" priority="192" />`. It is additive:
`Sylius\Component\Channel\Context\CompositeChannelContext` iterates the tagged
services in priority order and returns the first channel that does not raise
`ChannelNotFoundException`. To resolve the channel differently for your own
admin surface, register another one:

```php
<?php
// src/Channel/ForcedChannelContext.php

declare(strict_types=1);

namespace App\Channel;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

#[Autoconfigure(tags: [['name' => 'sylius.context.channel', 'priority' => 200]])]
final readonly class ForcedChannelContext implements ChannelContextInterface
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private RequestStack $requestStack,
        #[Autowire(service: 'sylius.repository.channel')]
        private ChannelRepositoryInterface $channelRepository,
    ) {
    }

    public function getChannel(): ChannelInterface
    {
        $code = $this->requestStack->getMainRequest()?->query->get('forceChannel');
        if (!is_string($code) || '' === $code) {
            throw new ChannelNotFoundException('No channel forced.');
        }

        $channel = $this->channelRepository->findOneByCode($code);
        if (!$channel instanceof ChannelInterface) {
            throw new ChannelNotFoundException(sprintf('Channel "%s" does not exist.', $code));
        }

        return $channel;
    }
}
```

A priority above 192 wins over the preview context; below it, the preview
keeps deciding for `/admin/sliders/{id}/preview`.

## Listen to events

The plugin dispatches no PHP events of its own. Three families are available:
the Sylius resource events its CRUD routes produce, the admin menu event it
subscribes to, and the browser `CustomEvent`s its Stimulus controllers emit.

### Sylius resource controller events

`config/routes/admin.yaml` registers the three CRUD route sets with
`type: sylius.resource`, and the resource aliases are
`vanssa_sylius_slider.slider`, `.slide`, `.style_preset`. Sylius builds the
event name as `<applicationName>.<resourceName>.<pre|post>_<action>`:

```php
<?php
// src/EventListener/SliderCacheInvalidator.php

declare(strict_types=1);

namespace App\EventListener;

use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

final class SliderCacheInvalidator
{
    #[AsEventListener(event: 'vanssa_sylius_slider.slider.post_create')]
    #[AsEventListener(event: 'vanssa_sylius_slider.slider.post_update')]
    #[AsEventListener(event: 'vanssa_sylius_slider.slider.post_delete')]
    public function onSliderWritten(ResourceControllerEvent $event): void
    {
        $slider = $event->getSubject();
        if (!$slider instanceof Slider) {
            return;
        }

        // purge your HTTP cache for /slider/{code} …
    }
}
```

`create`, `update` and `delete` each have a `pre_` and a `post_` variant.
Bulk delete dispatches `vanssa_sylius_slider.slider.bulk_delete` once with the
whole collection as the subject (no prefix), then `pre_delete`/`post_delete`
per resource. A `pre_` listener can abort the write with
`$event->stop('Sliders in use cannot be deleted.')`.

These fire **only for the standard CRUD controllers**. The plugin's own
turbo-frame endpoints and Live Components persist through the entity manager
directly and dispatch nothing: `SlideCreatePanelController`,
`SlideEditPanelController`, `SliderSlidesPreviewComponent` (drag reorder,
detach) and `SliderSlideBrowserComponent` (attach slides). If your listener has
to run on every write, use a Doctrine listener instead:

```php
<?php
// src/EventListener/SliderDoctrineListener.php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

#[AsEntityListener(event: Events::postPersist, entity: Slider::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Slider::class)]
final class SliderDoctrineListener
{
    public function postPersist(Slider $slider, PostPersistEventArgs $args): void
    {
        $this->touch($slider);
    }

    public function postUpdate(Slider $slider, PostUpdateEventArgs $args): void
    {
        $this->touch($slider);
    }

    private function touch(Slider $slider): void
    {
        // …
    }
}
```

### The admin menu event

`Vanssa\SyliusSliderPlugin\Menu\AdminMenuListener` subscribes to
`sylius.menu.admin.main` and adds a `sliders` group with the children
`vanssa_sylius_slider_sliders`, `vanssa_sylius_slider_slides`,
`vanssa_sylius_slider_slider_presets` and `vanssa_sylius_slider_slide_presets`.
Subscribe to the same event to reshape it:

```php
<?php
// src/Menu/SliderMenuListener.php

declare(strict_types=1);

namespace App\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class SliderMenuListener
{
    #[AsEventListener(event: 'sylius.menu.admin.main', priority: -100)]
    public function hidePresetMenus(MenuBuilderEvent $event): void
    {
        $sliders = $event->getMenu()->getChild('sliders');
        if (null === $sliders) {
            return;
        }

        $sliders->removeChild('vanssa_sylius_slider_slide_presets');
        $sliders->removeChild('vanssa_sylius_slider_slider_presets');
    }
}
```

A negative priority is required: the plugin's listener has to have created the
group before you can change it.

### Browser events

Four `CustomEvent` names are emitted. The two storefront ones bubble from the
media element; the two admin ones are dispatched straight on `document`.

```js
// your project's storefront JS entry
document.addEventListener('vanssa-slide-video:playing', (event) => {
    // event.target is the <video> or <iframe>; event.detail.remainingMs is a
    // number when the duration is known, otherwise null.
    console.log('slide video playing', event.detail.remainingMs);
});

document.addEventListener('vanssa-slide-video:ended', (event) => {
    console.log('slide video ended', event.target);
});
```

`slider_controller.js` listens for both on the slider root to hold autoplay
until a video finishes; if you replace the video controller, keep emitting them
or autoplay will advance mid-video.

```js
// your project's admin JS entry
document.addEventListener('vanssa-preview:context', (event) => {
    // { scope: 'inline'|<modal element id>, locale: 'en_US'|'default',
    //   breakpoint: 'desktop'|'tablet'|'mobile' }
    console.log(event.detail);
});

document.addEventListener('vanssa-preview:saved', (event) => {
    // detail.source is the id of the preview element that just persisted.
    console.log('preview saved by', event.detail.source);
});
```

Those two are dispatched on `document` rather than on the controller element
because the slide modal's form lives in a lazily loaded turbo-frame that is not
a descendant of the preview toolbar. `vanssa-form-context` (the settings form)
and `vanssa-preview-frame` (other preview instances on the page) are the
in-plugin listeners; the `scope` token in `vanssa-preview:context` is what stops
the inline panel and the slide modals from driving each other's forms.

## Add a video provider

A slide video slot stores either a self-hosted `/media/...` path or a
normalized external URL. `VideoProviderRegistry` collects every service tagged
`vanssa_sylius_slider.video_provider` and asks them in registration order; the
first provider whose `normalize()` returns non-`null` wins.

```php
<?php
// src/Video/VimeoVideoProvider.php

declare(strict_types=1);

namespace App\Video;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Vanssa\SyliusSliderPlugin\Video\VideoProviderInterface;

#[AutoconfigureTag('vanssa_sylius_slider.video_provider')]
final class VimeoVideoProvider implements VideoProviderInterface
{
    private const ID_PATTERN = '[0-9]{6,12}';

    public function name(): string
    {
        return 'vimeo';
    }

    public function supports(string $reference): bool
    {
        return null !== $this->extractId($reference);
    }

    public function normalize(string $url): ?string
    {
        $id = $this->extractId($url);

        return null === $id ? null : sprintf('https://vimeo.com/%s', $id);
    }

    public function embedUrl(string $reference, bool $autoplay = true): string
    {
        return sprintf(
            'https://player.vimeo.com/video/%s?%s',
            $this->extractId($reference) ?? '',
            http_build_query([
                'autoplay' => $autoplay ? 1 : 0,
                'muted' => 1,
                'playsinline' => 1,
                'controls' => 0,
                'dnt' => 1,
            ]),
        );
    }

    private function extractId(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url) {
            return null;
        }

        $patterns = [
            '#^https?://(?:www\.)?vimeo\.com/(' . self::ID_PATTERN . ')#',
            '#^https?://player\.vimeo\.com/video/(' . self::ID_PATTERN . ')#',
        ];

        foreach ($patterns as $pattern) {
            if (1 === preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
```

`normalize()` must be strict about what it accepts. It runs on every pasted URL
for every slot in `SlideType`'s `POST_SUBMIT` listener, so a provider that
returns non-`null` for anything vaguely URL-shaped will swallow other
providers' links. `supports()` has to accept exactly what `normalize()`
produces, otherwise `VideoEmbedExtension` cannot find the provider again at
render time and the slide falls back to a `<video>` tag pointing at an external
URL.

The storefront side is `assets/shop/controllers/slide_video_controller.js`. It
treats any `IFRAME` element as an embed and speaks the YouTube IFrame API:
`{"event":"listening"}` on load, `{"event":"command","func":"playVideo"}` /
`"pauseVideo"`, and it reads `data.info.playerState` (`1` = playing, `0` =
ended) from `postMessage`. Vimeo's player API uses a different message shape,
so a Vimeo provider also needs that controller replaced — see
[Replace a Stimulus controller](#replace-a-stimulus-controller). Without it the
iframe still renders and plays, but the slider's visibility gating and
autoplay-until-ended stop working for those slides.

## Add style presets

This section covers the two ways to register a preset. For the full dot-path
catalogue, the try-on wire format and how to render the gallery yourself, see
[style-presets.md](style-presets.md).

`StylePresetProvider` merges two sources into one list per type (`slide` and
`slider`). A database preset with the same code as a config preset replaces it.
Dot paths are converted to bracket field names
(`settings.responsive.desktop.textColor` →
`slide[settings][responsive][desktop][textColor]`) and applied client-side by
`assets/admin/utils/apply_preset_fields.js`. Only scalar values survive the
conversion — nested arrays are skipped.

### From configuration

```yaml
# config/packages/vanssa_sylius_slider.yaml
vanssa_sylius_slider:
    style_presets:
        slide:
            app_editorial:
                label: 'Editorial'
                settings:
                    'settings.responsive.desktop.contentHorizontalPosition': left_3_12
                    'settings.responsive.desktop.contentVerticalPosition': center
                    'settings.responsive.desktop.contentTextAlign': left
                    'settings.responsive.desktop.headlineFontSize': '2.5rem'
                    'settings.responsive.desktop.textColor': 'rgba(255, 255, 255, 1)'
                    'settings.responsive.desktop.backgroundColor': 'rgba(0, 0, 0, 0)'
                    'settings.responsive.desktop.mediaOverlayColor': 'rgba(2, 6, 23, 0.55)'
                    'settings.linking.buttonAppearance': secondary
                    'settings.linking.buttonSize': lg
        slider:
            app_showroom:
                label: 'Showroom'
                settings:
                    'settings.slideEffect': fade
                    'settings.speed': 800
                    'settings.autoplay.enabled': true
                    'settings.autoplay.interval': 6000
                    'settings.showProgressBar': true
```

`label` is required and cannot be empty; `settings` defaults to an empty map.
Using an existing key (`hero_dark`, `classic_arrows`, …) replaces that shipped
preset rather than adding one. The shipped defaults are
`Configuration::defaultSlideStylePresets()` and
`defaultSliderStylePresets()`.

Config presets get a bundled mockup image only if `MockupCatalog::defaultMockupFor()`
knows their key, so a new config preset shows no thumbnail in the gallery. To
ship a thumbnail, create the preset in the database instead and set
`mockupImage` to any public path.

### From the database

Admin users create these under `/admin/style-presets/`. To seed them:

```php
<?php
// src/DataFixtures/StylePresetFixture.php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;
use Vanssa\SyliusSliderPlugin\Preset\SettingsCapture;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

final class StylePresetFixture extends AbstractFixture
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SliderRepository $sliderRepository,
        private readonly SettingsCapture $settingsCapture,
    ) {
    }

    public function getName(): string
    {
        return 'app_style_presets';
    }

    public function load(array $options): void
    {
        $source = $this->sliderRepository->findEnabledOneByCode('fashion-fullscreen-hero');
        if (null === $source) {
            return;
        }

        $preset = new StylePreset();
        $preset->setCode('app_showroom');
        $preset->setType(StylePreset::TYPE_SLIDER);
        $preset->setLabel('Showroom');
        // Flattens the slider's settings into the dot-path map, minus
        // channelCodes and slideOrder.
        $preset->setSettings($this->settingsCapture->fromSlider($source));
        $preset->setMockupImage('/bundles/vanssasyliussliderplugin/preset-mockups/dark.svg');
        $preset->setEnabled(true);
        $preset->setPosition(10);

        foreach ($source->getOrderedSlides() as $slide) {
            $preset->addSlide($slide);
        }

        $this->entityManager->persist($preset);
        $this->entityManager->flush();
    }
}
```

Attaching slides changes how the preset is offered: `PresetGalleryComponent`
links database **slider** presets that have source slides to
`vanssa_sylius_slider_admin_slider_create_from_preset`, and
`SliderFactory::createFromStylePreset()` then applies the settings with
`DotPathApplier` and deep-copies every source slide with `SlideCloner`. Clones
share media paths with the source — no file is duplicated on disk, so deleting
the uploaded image of a source slide breaks every slider created from that
preset.

`SettingsCapture::fromSlide()` is the slide-side equivalent; it captures
`settings.responsive.<breakpoint>.*`, `settings.linking.*` and
`settings.parallax.*`.

Bundled mockup thumbnails are a deliberate static list in `MockupCatalog`
(`dark`, `light`, `with-text`, `center-bold`, `minimal`, `gradient`, `glass`),
published by `assets:install` to
`/bundles/vanssasyliussliderplugin/preset-mockups/<key>.svg`. The class is
`final`; to offer your own thumbnails, publish the images through your own
bundle or `public/` and store the path in `StylePreset::$mockupImage` — the
provider passes `getMockupImage()` straight through, so any public path works.

## Replace a Stimulus controller

All 14 controllers register through the `@symfony/stimulus-bridge` manifest in
`assets/package.json` (`symfony.controllers`), mirrored into your project's
`assets/controllers.json` when Flex installs the package (see
[../FLEX_RECIPE.md](../FLEX_RECIPE.md)). The plugin's own entrypoints never call
`startStimulusApp()` or `app.register()`.

Disable that one entry in your project's `assets/controllers.json` — keep the
other thirteen exactly as Flex wrote them, `autoimport` maps included, a
controller that is not listed is not built:

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
            "slide-video": { "enabled": false, "fetch": "eager" },
            "slider-settings": { "enabled": true, "fetch": "lazy" },
            "slider-slides-preview": { "enabled": true, "fetch": "lazy" },
            "rgba-color-picker": {
                "enabled": true,
                "fetch": "lazy",
                "autoimport": {
                    "@simonwep/pickr/dist/themes/classic.min.css": true
                }
            },
            "responsive-copy": { "enabled": true, "fetch": "lazy" },
            "preview-frame": { "enabled": true, "fetch": "lazy" },
            "preset-applier": { "enabled": true, "fetch": "lazy" },
            "preset-gallery": { "enabled": true, "fetch": "lazy" },
            "form-context": { "enabled": true, "fetch": "lazy" },
            "animation-settings": { "enabled": true, "fetch": "lazy" },
            "image-upload-preview": { "enabled": true, "fetch": "lazy" },
            "mockup-picker": { "enabled": true, "fetch": "lazy" },
            "modal-portal": { "enabled": true, "fetch": "lazy" }
        }
    },
    "entrypoints": []
}
```

The two `autoimport` maps have to survive the edit. The bridge imports those
paths from the `controllers.json` entry, not from the plugin's
`assets/package.json`, and `shop/styles/slider.scss` has no other route into
the build — the shop entrypoint is comment-only by design — so a manifest that
drops it compiles without complaint and ships a storefront with no slider
styles. The `rgba-color-picker` map is redundant today, because
`rgba_color_picker_controller.js` imports the Pickr themes itself, but it is
what the package ships: keep it rather than pruning it.

Then register your own class under the same identifier:

```js
// assets/bootstrap.js
import { startStimulusApp } from '@symfony/stimulus-bridge';
import VanssaSlideVideoController from './controllers/vanssa_slide_video_controller.js';

const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/,
));

app.register('vanssa-slide-video', VanssaSlideVideoController);
```

Templates and the other controllers only ever reference the identifier
(`vanssa-slide-video`, `vanssa-slider`, `vanssa-rgba-color-picker`, …), so a
replacement registered under the same name is picked up unchanged. Leaving the
manifest entry `enabled: true` while also calling `app.register()` gives the
element two controller instances and every action fires twice.

Identifiers, in manifest order: `vanssa-slider`, `vanssa-slide-video`,
`vanssa-slider-settings`, `vanssa-slider-slides-preview`,
`vanssa-rgba-color-picker`, `vanssa-responsive-copy`, `vanssa-preview-frame`,
`vanssa-preset-applier`, `vanssa-preset-gallery`, `vanssa-form-context`,
`vanssa-animation-settings`, `vanssa-image-upload-preview`,
`vanssa-mockup-picker`, `vanssa-modal-portal`.

Adding or renaming a controller *inside the plugin* is a different job: this
repository carries four manifests that must stay in sync, because
`sylius/test-application`'s webpack config shallow-merges them per package key.
See [adding-a-stimulus-controller.md](adding-a-stimulus-controller.md).

## Integrate with other plugins

Two integrations exist in the code. Both are optional; `composer.json` lists
them under `suggest`.

### Sylius CMS blocks

`templates/shop/integration/cms/slider_block.html.twig` renders a slider by
code inside a CMS block:

```twig
{{ sylius_cms_render_block(
    'homepage_slider',
    '@VanssaSyliusSliderPlugin/shop/integration/cms/slider_block.html.twig',
    { 'slider_code': 'fashion-classic-arrows' }
) }}
```

It looks the slider up with `sylius_slider_by_code()`, includes
`shop/slider/_slider.html.twig` when it finds one, and — in debug mode only —
prints which code failed to resolve. Nothing renders in prod for an unknown
code, so verify the block's `slider_code` in `dev` first.

### Rich editor for slide descriptions

`Vanssa\SyliusSliderPlugin\Twig\SliderExtension::renderContent()` backs the
`sylius_slider_render_content()` Twig function. It checks whether the Twig
filter `monsieurbiz_richeditor_render_field` is registered:

```php
$filter = $this->twig->getFilter('monsieurbiz_richeditor_render_field');
if (null === $filter) {
    return new Markup($content, 'UTF-8');
}
```

If the filter exists the content is rendered through it; otherwise the raw
string is passed through as markup. The detection is per render call and needs
no configuration — installing
`monsieurbiz/sylius-rich-editor-plugin` is the whole integration. The same
mechanism is how you can substitute any other renderer: register a Twig filter
under that name.

Note that the function returns a `Markup` object either way, so whatever ends
up in the description column is emitted unescaped. Anything writing to
`SlideTranslation` outside the admin form must sanitise its own input.

## The admin preview

The preview renders real storefront markup inside a turbo-frame in the admin
page. Because it is an admin viewport, CSS media queries and the storefront's
`matchMedia` code can never select tablet or mobile there — so the requested
breakpoint is baked in server-side by
`Vanssa\SyliusSliderPlugin\Preview\PreviewBreakpointFlattener`, which overlays
desktop → tablet → mobile (empty values inherit) and then clears the responsive
variants.

Pieces involved:

```text
Controller\Admin\SliderPreviewController   route vanssa_sylius_slider_admin_slider_preview
Controller\Admin\SlidePreviewController    route vanssa_sylius_slider_admin_slide_preview
    query: locale (default: the channel's default locale)
           channel (default: the resource's first channel)
           breakpoint (desktop|tablet|mobile, anything else -> desktop)
           overrides (unsaved draft, from the request body on POST)
    both disable the profiler, so the debug toolbar stays out of the frame
Preview\PreviewBreakpointFlattener         flattenSliderSettings()/flattenSlideSettings()
Context\Admin\PreviewChannelContext        tag sylius.context.channel, priority 192
templates/admin/slider/preview.html.twig   standalone pages loaded into the
templates/admin/slide/preview.html.twig    workspace turbo-frame
assets/admin/controllers/preview_frame_controller.js
```

To style the preview like your theme, point the shop entrypoints at your own
Encore build. The format is `"build:entry"` (or plain `"entry"`). The preview
controllers pass the list to `preview.html.twig` as `shopEntrypoints`, and the
shared `admin/slide/preview/_assets.html.twig` partial reads the same parameter
through `vanssa_slider_preview_shop_entrypoints()`:

```yaml
# config/packages/vanssa_sylius_slider.yaml
vanssa_sylius_slider:
    preview:
        shop_entrypoints:
            - 'shop:shop-entry'
            - 'app.shop:app-shop-entry'
            - 'app.shop:plugin-shop-entry'
            - 'app.shop:my-theme-entry'
```

Listing an entrypoint that the referenced build does not contain makes the
preview page fail to render, so add to the shipped defaults rather than
guessing a replacement list.

## Evolving the settings schema

The settings columns are JSON, so a new key needs no migration — but old rows
will not have it. Rules the plugin follows:

- Read with an explicit fallback in PHP (`$settings['key'] ?? $default`) and in
  Twig (`settings.key ?? true` for booleans that default to on, never
  `|default(true)`).
- Normalize in the form type's `PRE_SET_DATA`/`SUBMIT` listeners rather than in
  templates. `SliderSettingsType::normalizeAutoplay()` accepts both the current
  `autoplay.enabled` and the legacy `autoplay.active`;
  `normalizeNavigationSize()`/`normalizePaginationSize()` map the legacy
  `sm`/`md`/`lg` tokens onto rem values. `SliderStructuralSettings` and the
  storefront template repeat the same fallbacks so unmigrated rows still
  render.
- Never rename a JSON key without keeping the old one readable.

Structural changes (new columns, new tables) do need a migration. The plugin
ships its own under `src/Migrations` with the namespace
`VanssaSyliusSliderPluginMigrations`, registered through
`PrependDoctrineMigrationsTrait` with the directory alias
`@VanssaSyliusSliderPlugin/src/Migrations`. Those files keep their own
top-level namespace despite living under the plugin's PSR-4 root, so
`composer.json` excludes `/src/Migrations/` from the classmap — Doctrine
loads them by path, not autoloading. In a project, generate migrations into
your own configured directory as usual:

```bash
docker compose run --rm php vendor/bin/console doctrine:migrations:diff
docker compose run --rm php vendor/bin/console doctrine:migrations:migrate -n
```

## Extend the demo fixtures

`Vanssa\SyliusSliderPlugin\Fixture\SliderDemoFixture` is tagged
`sylius_fixtures.fixture` with alias `vanssa_slider_demo` and is the only
fixture in the `vanssa_sylius_slider_demo` suite. It creates seven slides
(`new-collection`, `summer-dresses`, `denim-essentials`, `graphic-tees`,
`street-caps`, `season-sale`, `runway-video`) and one slider per shipped slider
style preset (`fashion-classic-arrows`, `fashion-minimal-fade`,
`fashion-autoplay-showcase`, `fashion-fullscreen-hero`,
`fashion-compact-banner`, `fashion-parallax-showcase`). It is idempotent —
existing codes are updated, not duplicated.

Add your own fixture to the same suite:

```yaml
# config/packages/vanssa_sylius_slider.yaml
sylius_fixtures:
    suites:
        vanssa_sylius_slider_demo:
            fixtures:
                app_style_presets: ~
```

```bash
make load-slider-fixtures
# = docker compose run --rm php vendor/bin/console sylius:fixtures:load vanssa_sylius_slider_demo -n
```

Media handling: the fixture copies its bundled files before handing them to
`Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage`, so the sources under
`assets/fixtures/images` and `assets/fixtures/videos` are never consumed and
the suite can be re-run. Keep license documentation for anything you add there
next to the existing `assets/fixtures/LICENSE.md`.

The slide `new-collection` is the only demo slide carrying per-breakpoint
layout overrides (tablet centres the content horizontally, mobile also centres
it vertically). `tests/e2e/shop/responsive-overrides.spec.ts` asserts on it, so
removing those overrides breaks that spec.

## See also

- [architecture.md](architecture.md) — how the bundle, services, routes, hooks
  and components fit together.
- [style-presets.md](style-presets.md) — the preset system in depth.
- [adding-a-stimulus-controller.md](adding-a-stimulus-controller.md) — the four
  manifests and the registration chain.
- [color-picker-type.md](color-picker-type.md) — the reusable `ColorPickerType`
  form field.
- [testing.md](testing.md) — which test layer to use for what.
- [contributing.md](contributing.md) — running the checks in containers.
- [../FLEX_RECIPE.md](../FLEX_RECIPE.md) — the Flex endpoint and what
  `composer require` wires up.
