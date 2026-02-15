# ColorPickerType Guide

This plugin provides a reusable Symfony form field type:

- `Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType`

It is backed by Pickr and the plugin Stimulus controller (`vanssa-rgba-color-picker`).

## Basic Usage

```php
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;

$builder->add('headlineColor', ColorPickerType::class, [
    'required' => false,
]);
```

## Available Form Options

- `picker_theme` (`string`)
  - Pickr theme: `classic`, `monolith`, `nano`
- `picker_swatches` (`string[]`)
  - List of predefined swatches shown in picker
- `picker_default_representation` (`string`)
  - One of: `HEX`, `RGBA`, `HSLA`, `HSVA`, `CMYK`
- `picker_predefined_only` (`bool`)
  - If `true`, user can only pick from `picker_swatches`
- `picker_options` (`array<string, mixed>`)
  - Raw Pickr options passed to `Pickr.create(...)`
- `picker_button_label` (`string`)
  - Accessible button label text
- `picker_placeholder` (`string`)
  - Input placeholder

## Example: Full Configuration

```php
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;
use Symfony\Component\Validator\Constraints as Assert;

$builder->add('navigationColor', ColorPickerType::class, [
    'required' => false,
    'picker_theme' => 'classic',
    'picker_swatches' => [
        'rgba(250, 204, 21, 1)',
        'rgba(250, 204, 21, 0.75)',
        'rgba(17, 24, 39, 0.85)',
    ],
    'picker_default_representation' => 'RGBA',
    'picker_predefined_only' => false,
    'picker_options' => [
        'defaultRepresentation' => 'RGBA',
        'components' => [
            'preview' => true,
            'opacity' => true,
            'hue' => true,
            'interaction' => [
                'input' => true,
                'clear' => true,
                'save' => true,
            ],
        ],
    ],
    'constraints' => [
        new Assert\CssColor(),
    ],
]);
```

## Example: Predefined Swatches Only

```php
$builder->add('badgeColor', ColorPickerType::class, [
    'picker_swatches' => [
        '#111827',
        '#1f2937',
        '#facc15',
    ],
    'picker_predefined_only' => true,
]);
```

When `picker_predefined_only` is enabled:

- the picker is rendered in swatches-only mode
- form validation enforces values to be one of `picker_swatches`

## Preset Integration

Global defaults are loaded from:

- `vanssa_sylius_slider.presets.color_switcher.*`

Configured in your app, e.g.:

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
                text: ['rgba(255, 255, 255, 1)', 'rgba(0, 0, 0, 1)']
                neutral: ['rgba(0, 0, 0, 0)', 'rgba(17, 24, 39, 0.9)']
                accent: ['rgba(250, 204, 21, 1)', 'rgba(59, 130, 246, 0.85)']
```

## Notes

- `picker_options` lets you use Pickr features without changing PHP type code.
- For strict design systems, combine:
  - `picker_predefined_only: true`
  - curated `picker_swatches`
