<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type;

use Symfony\Component\Validator\Constraints\Choice;
use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ColorPickerType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $themeValues = $this->settingsPresetProvider->values('color_switcher', 'theme', ['classic', 'monolith', 'nano']);
        $defaultTheme = (string) $this->settingsPresetProvider->safeDefault('color_switcher', 'theme', 'classic', $themeValues);
        $representationValues = $this->settingsPresetProvider->values('color_switcher', 'default_representation', ['HEX', 'RGBA', 'HSLA', 'HSVA', 'CMYK']);
        $defaultRepresentation = (string) $this->settingsPresetProvider->safeDefault('color_switcher', 'default_representation', 'RGBA', $representationValues);

        $resolver->setDefaults([
            'picker_theme' => $defaultTheme,
            'picker_swatches' => [],
            'picker_options' => [],
            'picker_default_representation' => $defaultRepresentation,
            'picker_predefined_only' => false,
            'picker_button_label' => 'Pick color',
            'picker_placeholder' => 'rgba(255, 255, 255, 1)',
        ]);

        $resolver->setAllowedTypes('picker_theme', 'string');
        $resolver->setAllowedTypes('picker_swatches', 'array');
        $resolver->setAllowedTypes('picker_options', 'array');
        $resolver->setAllowedTypes('picker_default_representation', 'string');
        $resolver->setAllowedTypes('picker_predefined_only', 'bool');
        $resolver->setAllowedTypes('picker_button_label', 'string');
        $resolver->setAllowedTypes('picker_placeholder', 'string');

        $resolver->setNormalizer('picker_swatches', static function (Options $options, mixed $value): array {
            if (!is_array($value)) {
                return [];
            }

            $swatches = [];
            foreach ($value as $swatch) {
                if (is_string($swatch) && '' !== trim($swatch)) {
                    $swatches[] = $swatch;
                }
            }

            return $swatches;
        });

        $resolver->setNormalizer('constraints', static function (Options $options, mixed $constraints): array {
            $normalized = is_array($constraints) ? $constraints : [];

            if (true !== $options['picker_predefined_only'] || [] === $options['picker_swatches']) {
                return $normalized;
            }

            $normalized[] = new Choice([
                'choices' => $options['picker_swatches'],
                'message' => 'Please choose one of predefined colors.',
            ]);

            return $normalized;
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $pickerOptions = $options['picker_options'];
        $pickerOptions['defaultRepresentation'] = $pickerOptions['defaultRepresentation'] ?? $options['picker_default_representation'];
        $pickerOptions['onlyPredefinedSwatches'] = $options['picker_predefined_only'];
        $pickerOptions['allowedSwatches'] = $options['picker_swatches'];

        $view->vars['picker_theme'] = $options['picker_theme'];
        $view->vars['picker_swatches'] = $options['picker_swatches'];
        $view->vars['picker_options'] = $pickerOptions;
        $view->vars['picker_button_label'] = $options['picker_button_label'];
        $view->vars['picker_placeholder'] = $options['picker_placeholder'];
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'vanssa_color_picker';
    }
}
