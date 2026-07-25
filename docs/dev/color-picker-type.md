# ColorPickerType

`Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType` is a reusable Symfony
form field: a text input plus a [Pickr](https://github.com/simonwep/pickr)
colour picker, driven by the `vanssa-rgba-color-picker` Stimulus controller.
The stored value is always a CSS colour string — the field extends `TextType`,
so it maps onto a plain `string` property or array key.

The plugin uses it for `navigationColor`, `navigationBackgroundColor`,
`paginationColor` and `paginationActiveColor` in `SliderSettingsType`, for the
same four in `SliderResponsiveBreakpointSettingsType`, and for `textColor`,
`headlineColor`, `descriptionColor`, `backgroundColor` and `mediaOverlayColor`
in `SlideResponsiveBreakpointSettingsType`. Nothing ties it to slider entities
— use it on any form.

## Basic usage

```php
<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;

final class BrandSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('headlineColor', ColorPickerType::class, [
            'required' => false,
            'label' => 'Headline colour',
            'constraints' => [new Assert\CssColor()],
        ]);
    }
}
```

No extra template work is needed: the plugin registers its form theme
globally in its own `config/config.yaml`, which the Flex recipe imports into
`config/packages/vanssa_sylius_slider.yaml`:

```yaml
twig:
    form_themes:
        - '@VanssaSyliusSliderPlugin/form/theme/color_picker.html.twig'
```

If your project replaces `twig.form_themes` wholesale instead of appending,
add that line back or the field renders as a bare text input with no picker
button.

## Options

| Option | Type | Default |
| --- | --- | --- |
| `picker_theme` | `string` | config `color_switcher.theme.default` — `classic` |
| `picker_swatches` | `array` | `[]` |
| `picker_default_representation` | `string` | config `color_switcher.default_representation.default` — `RGBA` |
| `picker_predefined_only` | `bool` | `false` |
| `picker_options` | `array` | `[]` |
| `picker_button_label` | `string` | `'Pick color'` |
| `picker_placeholder` | `string` | `'rgba(255, 255, 255, 1)'` |

"config" means the `vanssa_sylius_slider.presets.*` tree — see
[Global defaults](#global-defaults).

- `picker_theme` — Pickr theme name. The controller imports the CSS of all
  three shipped themes (`classic`, `monolith`, `nano`), so any of them renders
  without extra asset work.
- `picker_swatches` — the swatch row under the picker. Entries that are not
  non-empty strings are dropped by the option normalizer, so a stray `null` in
  a config-driven list is ignored rather than breaking the widget.
- `picker_default_representation` — one of `HEX`, `RGBA`, `HSLA`, `HSVA`,
  `CMYK`. It is copied into `picker_options['defaultRepresentation']` only if
  that key is not already set, so an explicit `picker_options` entry wins.
- `picker_predefined_only` — restricts the value to `picker_swatches`; see
  below.
- `picker_options` — merged into the Pickr constructor options. The merge is
  recursive for objects, but arrays replace wholesale
  (`rgba_color_picker_controller.js#deepMerge`).
- `picker_button_label` — used as the button's `aria-label` and as
  visually-hidden text. Not translated by the type; pass a translated string.
- `picker_placeholder` — merged into the input's `attr.placeholder`.

## Full configuration

```php
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;

$builder->add('navigationColor', ColorPickerType::class, [
    'required' => false,
    'label' => 'Navigation colour',
    'help' => 'Icon colour of the previous/next buttons.',
    'picker_theme' => 'monolith',
    'picker_swatches' => [
        'rgba(250, 204, 21, 1)',
        'rgba(250, 204, 21, 0.75)',
        'rgba(17, 24, 39, 0.85)',
    ],
    'picker_default_representation' => 'RGBA',
    'picker_button_label' => 'Pick the navigation colour',
    'picker_placeholder' => 'rgba(17, 24, 39, 0.85)',
    'picker_options' => [
        'components' => [
            'preview' => true,
            'opacity' => true,
            'hue' => true,
            'interaction' => [
                'hex' => false,
                'rgba' => true,
                'input' => true,
                'clear' => true,
                'save' => true,
            ],
        ],
    ],
    'constraints' => [new Assert\CssColor()],
]);
```

The controller's base options already enable `preview`, `opacity`, `hue` and
the full `interaction` row (`hex`, `rgba`, `hsla`, `hsva`, `cmyk`, `input`,
`clear`, `save`). `picker_options` only needs the keys you want to change.

Three keys in `picker_options` are not yours to set: `el` is forced back to the
button target after the merge, and `onlyPredefinedSwatches` / `allowedSwatches`
are written by `buildView()` and stripped again before the options reach
`Pickr.create()`.

## Swatches-only mode

```php
$builder->add('badgeColor', ColorPickerType::class, [
    'required' => false,
    'picker_predefined_only' => true,
    'picker_swatches' => [
        'rgba(17, 24, 39, 1)',
        'rgba(31, 41, 55, 1)',
        'rgba(250, 204, 21, 1)',
    ],
]);
```

With `picker_predefined_only => true` **and** a non-empty `picker_swatches`:

- The picker renders in swatches-only mode — `preview`, `opacity` and `hue` are
  turned off and only `clear` and `save` remain in the interaction row.
- Client-side, a saved colour that is not in the list is replaced by the first
  swatch. The comparison is lower-cased with whitespace removed, so
  `rgba(17,24,39,1)` and `rgba(17, 24, 39, 1)` count as the same colour.
- Server-side, the type appends a `Choice` constraint over `picker_swatches`
  with the message `Please choose one of predefined colors.` — added on top of
  any `constraints` you pass, never replacing them.

If `picker_swatches` is empty the constraint is **not** added and the picker
stays in full mode, so `picker_predefined_only` on its own does nothing.

`picker_predefined_only` is a top-level form option. Nesting it inside
`picker_options` has no effect at all: `buildView()` reads the top-level option,
and an unknown key inside `picker_options` is simply passed through to Pickr,
which ignores it. The field then keeps accepting arbitrary colours and no
`Choice` constraint is added.

## Rendered markup

```twig
{% block vanssa_color_picker_widget %}
    {% set attr = attr|merge({
        'data-vanssa-rgba-color-picker-target': 'input',
        placeholder: picker_placeholder
    }) %}

    <div
        {{ stimulus_controller('vanssa-rgba-color-picker', {
            theme: picker_theme,
            swatches: picker_swatches,
            options: picker_options
        }) }}
    >
        <div class="input-group">
            {{ block('form_widget_simple') }}
            <button
                type="button"
                class="btn vanssa-color-picker-swatch"
                aria-label="{{ picker_button_label }}"
                {{ stimulus_target('vanssa-rgba-color-picker', 'button') }}
            >
                <span class="visually-hidden">{{ picker_button_label }}</span>
            </button>
        </div>
    </div>
{% endblock %}
```

The block prefix is `vanssa_color_picker`, so a project theme can override
`vanssa_color_picker_widget`, `vanssa_color_picker_row` or
`vanssa_color_picker_label` like any other Symfony form block. Keep the
`input` and `button` Stimulus targets in any replacement — without them
Stimulus raises "Missing target element" as soon as the controller connects,
and the field stays a plain text input.

The `.vanssa-color-picker-swatch` button is styled in
`assets/admin/styles/rgba_color_picker.scss`, which the plugin's admin
entrypoint imports.

## Behaviour of the Stimulus controller

`assets/admin/controllers/rgba_color_picker_controller.js`, identifier
`vanssa-rgba-color-picker`.

```js
static targets = ['button', 'input'];
static values = {
    theme:    { type: String, default: 'classic' },
    swatches: { type: Array,  default: [] },
    options:  { type: Object, default: {} },
};
```

What it does on every save or clear:

```js
this.inputTarget.dispatchEvent(new Event('input',  { bubbles: true }));
this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
```

Those two bubbling events are the contract with the rest of the admin — the
style-preset applier, the `vanssa-slider-settings` field gating and the live
preview draft all react to them. A replacement controller that writes
`inputTarget.value` without dispatching them leaves the preview showing the old
colour until the next full reload.

The value written back depends on Pickr's current representation
(`getColorRepresentation()`): `HEX*` produces `toHEXA()`, `HSLA`/`HSVA`/`CMYK`
their respective forms, and anything else falls back to
`color.toRGBA().toString(0)` — RGBA with zero decimals. That is why the shipped
swatch lists are written as `rgba(...)` strings.

Two lifecycle details matter if you reuse the field inside a modal or a
turbo-frame:

- `connect()` returns early when the element already contains a `.pickr`
  wrapper. Moving the field in the DOM (the preview modal re-parents real
  form fieldsets) disconnects and reconnects the controller even though the
  element never left the document; re-running `Pickr.create()` there would
  crash, because Pickr consumed the original button element on first init.
- `disconnect()` returns early while `this.element.isConnected` is still true,
  for the same reason. Teardown only happens on a genuine removal from the
  document.

`updateSwatch()` tints the button. After Pickr has consumed the original
button it writes the `--pcr-color` custom property on the generated
`.pcr-button` instead, so values written straight into the input — typing, a
style preset, "copy from desktop" — still tint the swatch. An empty input falls
back to `rgba(255, 255, 255, 1)` for the tint only; the stored value stays
empty.

## Global defaults

The type asks `Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider` for its
theme and representation defaults, so a project can retune them once for every
picker in the admin. These are the shipped values, from the plugin's
`config/config.yaml`:

```yaml
vanssa_sylius_slider:
    presets:
        color_switcher:
            theme:
                values: [classic, monolith, nano]
                default: classic
            default_representation:
                values: [HEX, RGBA, HSLA, HSVA, CMYK]
                default: RGBA
            swatches:
                text:
                    - 'rgba(0, 0, 0, 0)'
                    - 'rgba(255, 255, 255, 1)'
                    - 'rgba(255, 255, 255, 0.92)'
                    - 'rgba(245, 245, 245, 1)'
                    - 'rgba(230, 230, 230, 1)'
                    - 'rgba(0, 0, 0, 1)'
                neutral:
                    - 'rgba(0, 0, 0, 0)'
                    - 'rgba(15, 23, 42, 0.75)'
                    - 'rgba(17, 24, 39, 0.9)'
                    - 'rgba(31, 41, 55, 0.85)'
                    - 'rgba(255, 255, 255, 1)'
                accent:
                    - 'rgba(0, 0, 0, 0)'
                    - 'rgba(250, 204, 21, 1)'
                    - 'rgba(250, 204, 21, 0.75)'
                    - 'rgba(250, 204, 21, 0.45)'
                    - 'rgba(59, 130, 246, 0.85)'
                    - 'rgba(244, 114, 182, 0.85)'
                    - 'rgba(16, 185, 129, 0.85)'
                    - 'rgba(245, 158, 11, 0.85)'
```

Override them in your own project config:

```yaml
# config/packages/vanssa_sylius_slider.yaml
vanssa_sylius_slider:
    presets:
        color_switcher:
            theme:
                values: [nano]
                default: nano
            swatches:
                accent:
                    - 'rgba(0, 0, 0, 0)'
                    - 'rgba(220, 38, 38, 1)'
                    - 'rgba(37, 99, 235, 1)'
```

The `values` lists are replaced, not merged — listing one value means that is
the only value. `SettingsPresetProvider::safeDefault()` falls back to the first
entry of `values` when the configured `default` is not in it, so a mismatched
pair yields a usable (if unexpected) theme rather than an exception.

The three swatch groups are not consumed by `ColorPickerType` itself. They are
read by the form types that use the field, through
`SettingsPresetProvider::stringList('color_switcher.swatches.accent', [...])`
and friends, and passed on as `picker_swatches`. Reading them in your own form:

```php
<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;
use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;

final class BannerSettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $textSwatches = $this->settingsPresetProvider->stringList('color_switcher.swatches.text', [
            'rgba(255, 255, 255, 1)',
            'rgba(0, 0, 0, 1)',
        ]);

        $builder->add('captionColor', ColorPickerType::class, [
            'required' => false,
            'picker_swatches' => $textSwatches,
        ]);
    }
}
```

Always pass a fallback list to `stringList()`. It returns the fallback when the
path is missing or when the configured list contains no usable strings, and an
empty swatch list silently disables `picker_predefined_only`.

## See also

- [extending.md](extending.md#add-a-form-field) — adding fields to the slider
  and slide settings forms.
- [style-presets.md](style-presets.md) — how colour values travel from a style
  preset into these fields.
