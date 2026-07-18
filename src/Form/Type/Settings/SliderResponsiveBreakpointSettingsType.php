<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;
use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;

/**
 * A full set of slider setting OVERRIDES: every field is optional and an
 * empty value inherits from the level below (desktop base, or the
 * non-translated settings for locale overrides). Used for the slider's
 * tablet/mobile breakpoints AND for per-locale overrides (base + per
 * breakpoint) — see SliderSettingsOverrideType.
 */
final class SliderResponsiveBreakpointSettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Tri-state flags: '' inherits, '1'/'0' force on/off.
        foreach ([
            'showNavigation' => 'Show navigation',
            'showArrows' => 'Show arrows',
            'showProgressBar' => 'Show progress bar',
        ] as $field => $label) {
            $builder->add($field, ChoiceType::class, [
                'label' => $label,
                'choices' => ['Yes' => '1', 'No' => '0'],
                'required' => false,
                'placeholder' => 'Inherit',
                'constraints' => [new Assert\Choice(['choices' => ['1', '0']])],
            ]);
        }

        $catalogChoices = [
            'containerWidth' => ['container_width', ['content', 'full']],
            'slideEffect' => ['slide_effect', ['slide', 'fade', 'zoom', 'lift', 'flip']],
            'arrowsPosition' => ['arrows_position', ['overlay', 'outside', 'bottom']],
            'arrowsVerticalAlign' => ['arrows_vertical_align', ['center', 'top', 'bottom']],
            'navigationIcon' => ['navigation_icon', ['chevron', 'angle', 'square']],
            'navigationSize' => ['navigation_size', ['1.5rem', '3rem', '4rem', '6rem']],
            'navigationShadow' => ['navigation_shadow', ['none', 'soft', 'medium', 'strong', 'glow']],
            'paginationStyle' => ['pagination_style', ['dots', 'lines', 'numbers']],
            'paginationPosition' => ['pagination_position', ['bottom-inside', 'bottom-outside', 'top', 'left', 'right']],
            'paginationShape' => ['pagination_shape', ['circle', 'square']],
            'paginationSize' => ['pagination_size', ['0.5rem', '0.625rem', '0.8rem', '1rem']],
            'paginationShadow' => ['pagination_shadow', ['none', 'soft', 'medium', 'strong', 'glow']],
        ];
        foreach ($catalogChoices as $field => [$catalogKey, $fallback]) {
            $values = $this->settingsPresetProvider->values('slider', $catalogKey, $fallback);
            $builder->add($field, ChoiceType::class, [
                'choices' => self::stringChoices($values),
                'required' => false,
                'placeholder' => 'Inherit',
                'constraints' => [new Assert\Choice(['choices' => array_map(static fn (mixed $value): string => (string) $value, $values)])],
            ]);
        }

        foreach (['navigationColor', 'navigationBackgroundColor', 'paginationColor', 'paginationActiveColor'] as $colorField) {
            $builder->add($colorField, ColorPickerType::class, [
                'required' => false,
                'help' => 'Empty inherits.',
            ]);
        }

        $spacingValues = $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem']);
        foreach (['marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'] as $field) {
            $builder->add($field, ChoiceType::class, [
                'choices' => self::stringChoices($spacingValues),
                'required' => false,
                'placeholder' => 'Inherit',
                'constraints' => [new Assert\Choice(['choices' => array_map(static fn (mixed $value): string => (string) $value, $spacingValues)])],
            ]);
        }

        $builder->add('maxHeight', TextType::class, [
            'required' => false,
            'help' => 'Overrides the slider max height (e.g. 320px); empty inherits.',
            'constraints' => [
                new Assert\Regex([
                    'pattern' => '/^$|^\d+(\.\d+)?(px|rem|vh|%)$/',
                    'message' => 'Use a CSS length like 320px, 20rem, 60vh or 50%.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => static fn (): array => [],
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * @param array<int, bool|float|int|string> $values
     *
     * @return array<string, string>
     */
    private static function stringChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $choices[(string) $value] = (string) $value;
        }

        return $choices;
    }
}
