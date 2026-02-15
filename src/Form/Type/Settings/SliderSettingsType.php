<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;
use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;

final class SliderSettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $containerWidthValues = $this->settingsPresetProvider->values('slider', 'container_width', ['content', 'full']);
        $spacingValues = $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem']);
        $slideEffectValues = $this->settingsPresetProvider->values('slider', 'slide_effect', ['slide', 'fade', 'zoom', 'lift', 'flip']);
        $navigationIconValues = $this->settingsPresetProvider->values('slider', 'navigation_icon', ['chevron', 'angle', 'square']);
        $navigationSizeValues = $this->settingsPresetProvider->values('slider', 'navigation_size', ['1.5rem', '3rem', '4rem', '6rem']);
        $navigationShadowValues = $this->settingsPresetProvider->values('slider', 'navigation_shadow', ['none', 'soft', 'medium', 'strong', 'glow']);
        $paginationShapeValues = $this->settingsPresetProvider->values('slider', 'pagination_shape', ['circle', 'square']);
        $paginationSizeValues = $this->settingsPresetProvider->values('slider', 'pagination_size', ['0.5rem', '0.625rem', '0.8rem', '1rem']);
        $paginationShadowValues = $this->settingsPresetProvider->values('slider', 'pagination_shadow', ['none', 'soft', 'medium', 'strong', 'glow']);
        $accentSwatches = $this->settingsPresetProvider->stringList('color_switcher.swatches.accent', [
            'rgba(250, 204, 21, 1)',
            'rgba(250, 204, 21, 0.75)',
            'rgba(250, 204, 21, 0.45)',
            'rgba(59, 130, 246, 0.85)',
            'rgba(244, 114, 182, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(245, 158, 11, 0.85)',
        ]);

        if (true === $options['translation_mode']) {
            $builder->add('enabled', CheckboxType::class, [
                'label' => 'Use custom options for this locale',
                'help' => 'Enable locale-specific slider settings override.',
                'attr' => [
                    'data-slider-settings-target' => 'translationEnabled',
                    'data-action' => 'change->slider-settings#translationEnabledChanged',
                ],
            ]);
        }

        $builder
            ->add('overlay', CheckboxType::class, [
                'required' => false,
                'help' => 'Render overlay layer above slide media.',
            ])
            ->add('showTitle', CheckboxType::class, [
                'required' => false,
                'help' => 'Display slider title in rendered component.',
            ])
            ->add('containerWidth', ChoiceType::class, [
                'choices' => self::labeledChoices($containerWidthValues),
                'help' => 'Choose full-width or content-width slider container.',
                'constraints' => [
                    new Assert\Choice(['choices' => $containerWidthValues]),
                ],
            ])
            ->add('marginTop', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Top outer spacing around the slider.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('marginRight', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Right outer spacing around the slider.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('marginBottom', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Bottom outer spacing around the slider.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('marginLeft', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Left outer spacing around the slider.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('paddingTop', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Top inner spacing inside slider container.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('paddingRight', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Right inner spacing inside slider container.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('paddingBottom', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Bottom inner spacing inside slider container.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('paddingLeft', ChoiceType::class, [
                'choices' => self::remChoices($spacingValues),
                'help' => 'Left inner spacing inside slider container.',
                'constraints' => [
                    new Assert\Choice(['choices' => $spacingValues]),
                ],
            ])
            ->add('justifySlideHeight', CheckboxType::class, [
                'required' => false,
                'help' => 'Normalize slide heights to prevent layout jumps.',
            ])
            ->add('rewind', CheckboxType::class, [
                'required' => false,
                'help' => 'Return to first slide after the last one.',
            ])
            ->add('speed', IntegerType::class, [
                'required' => false,
                'help' => 'Transition speed in milliseconds.',
                'constraints' => [
                    new Assert\Range(['min' => 100, 'max' => 10000]),
                ],
            ])
            ->add('pauseOnHover', CheckboxType::class, [
                'required' => false,
                'help' => 'Pause slide progression on mouse hover.',
            ])
            ->add('slideEffect', ChoiceType::class, [
                'choices' => self::labeledChoices($slideEffectValues),
                'help' => 'Visual transition effect between slides.',
                'constraints' => [
                    new Assert\Choice(['choices' => $slideEffectValues]),
                ],
            ])
            ->add('maxHeight', TextType::class, [
                'required' => false,
                'help' => 'Optional max height of slider area (for example: 560px, 70vh, 48rem).',
                'constraints' => [
                    new Assert\Length(['max' => 32]),
                    new Assert\Regex([
                        'pattern' => '/^\s*$|^[0-9]+(?:\.[0-9]+)?(?:px|rem|em|vh|vw|%)$/',
                        'message' => 'Enter a valid CSS size (for example: 560px, 70vh, 48rem).',
                    ]),
                ],
            ])
            ->add('cssClasses', TextType::class, [
                'required' => false,
                'help' => 'Additional CSS classes applied to the slider root.',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-z0-9\-_ ]*$/',
                        'message' => 'CSS classes contain invalid characters.',
                    ]),
                ],
            ])
            ->add('autoplay', AutoplaySettingsType::class, [
                'help' => 'Configure automatic slide cycling behavior.',
            ])
            ->add('parallax', ParallaxSettingsType::class, [
                'help' => 'Configure optional parallax movement for slide media.',
            ])
            ->add('showNavigation', CheckboxType::class, [
                'required' => false,
                'help' => 'Enable navigation controls section.',
            ])
            ->add('showArrows', CheckboxType::class, [
                'required' => false,
                'help' => 'Show previous/next arrow buttons.',
            ])
            ->add('navigationIcon', ChoiceType::class, [
                'choices' => self::labeledChoices($navigationIconValues),
                'help' => 'Arrow icon style for navigation buttons.',
                'constraints' => [
                    new Assert\Choice(['choices' => $navigationIconValues]),
                ],
            ])
            ->add('navigationSize', ChoiceType::class, [
                'choices' => self::remChoices($navigationSizeValues),
                'help' => 'Size of navigation buttons.',
                'constraints' => [
                    new Assert\Choice(['choices' => $navigationSizeValues]),
                ],
            ])
            ->add('navigationColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Text/icon color of navigation buttons.',
                'picker_swatches' => $accentSwatches,
                'picker_options' => [
                    'defaultRepresentation' => 'RGBA',
                     'picker_predefined_only' => true
                ],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('navigationBackgroundColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Background color of navigation buttons.',
                'picker_swatches' => $accentSwatches,
                'picker_options' => [
                    'defaultRepresentation' => 'RGBA',
                ],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('navigationShadow', ChoiceType::class, [
                'choices' => self::labeledChoices($navigationShadowValues),
                'help' => 'Shadow preset for navigation buttons.',
                'constraints' => [
                    new Assert\Choice(['choices' => $navigationShadowValues]),
                ],
            ])
            ->add('paginationShape', ChoiceType::class, [
                'choices' => self::labeledChoices($paginationShapeValues),
                'help' => 'Shape of pagination indicators.',
                'constraints' => [
                    new Assert\Choice(['choices' => $paginationShapeValues]),
                ],
            ])
            ->add('paginationSize', ChoiceType::class, [
                'choices' => self::remChoices($paginationSizeValues),
                'help' => 'Size of pagination indicators.',
                'constraints' => [
                    new Assert\Choice(['choices' => $paginationSizeValues]),
                ],
            ])
            ->add('paginationShadow', ChoiceType::class, [
                'choices' => self::labeledChoices($paginationShadowValues),
                'help' => 'Shadow preset for pagination indicators.',
                'constraints' => [
                    new Assert\Choice(['choices' => $paginationShadowValues]),
                ],
            ])
            ->add('paginationColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Default color of pagination indicators.',
                'picker_swatches' => $accentSwatches,
                'picker_options' => [
                    'defaultRepresentation' => 'RGBA',
                    'picker_predefined_only' => true
                ],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('paginationActiveColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Color of the active pagination indicator.',
                'picker_swatches' => $accentSwatches,
                'picker_options' => [
                    'defaultRepresentation' => 'RGBA',
                     'picker_predefined_only' => true
                ],
                'constraints' => [new Assert\CssColor()],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data['navigationSize'] = self::normalizeNavigationSize($data['navigationSize'] ?? null);
            $data['paginationSize'] = self::normalizePaginationSize($data['paginationSize'] ?? null);
            $data['autoplay'] = self::normalizeAutoplay($data['autoplay'] ?? null);
            $data['parallax'] = self::normalizeParallax($data['parallax'] ?? null);

            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }
            $data['navigationSize'] = self::normalizeNavigationSize($data['navigationSize'] ?? null);
            $data['paginationSize'] = self::normalizePaginationSize($data['paginationSize'] ?? null);
            $data['autoplay'] = self::normalizeAutoplay($data['autoplay'] ?? null);
            $data['parallax'] = self::normalizeParallax($data['parallax'] ?? null);

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_mode' => false,
            'data_class' => null,
            'empty_data' => fn (): array => [
                'enabled' => false,
                'overlay' => false,
                'showTitle' => true,
                'containerWidth' => $this->settingsPresetProvider->safeDefault('slider', 'container_width', 'full', $this->settingsPresetProvider->values('slider', 'container_width', ['content', 'full'])),
                'marginTop' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'marginRight' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'marginBottom' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'marginLeft' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'paddingTop' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'paddingRight' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'paddingBottom' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'paddingLeft' => $this->settingsPresetProvider->safeDefault('slider', 'spacing', '0', $this->settingsPresetProvider->values('slider', 'spacing', ['0', '0.5rem', '1rem', '1.5rem', '2rem', '4rem'])),
                'justifySlideHeight' => true,
                'rewind' => true,
                'speed' => 400,
                'pauseOnHover' => true,
                'slideEffect' => $this->settingsPresetProvider->safeDefault('slider', 'slide_effect', 'slide', $this->settingsPresetProvider->values('slider', 'slide_effect', ['slide', 'fade', 'zoom', 'lift', 'flip'])),
                'maxHeight' => null,
                'cssClasses' => null,
                'autoplay' => [
                    'enabled' => false,
                    'interval' => $this->settingsPresetProvider->safeDefault('slider', 'autoplay_interval', 5000, $this->settingsPresetProvider->values('slider', 'autoplay_interval', [2000, 3000, 5000, 8000, 10000])),
                    'pauseOnHover' => true,
                ],
                'parallax' => [
                    'strength' => null,
                ],
                'showNavigation' => true,
                'showArrows' => true,
                'navigationIcon' => $this->settingsPresetProvider->safeDefault('slider', 'navigation_icon', 'chevron', $this->settingsPresetProvider->values('slider', 'navigation_icon', ['chevron', 'angle', 'square'])),
                'navigationSize' => $this->settingsPresetProvider->safeDefault('slider', 'navigation_size', '3rem', $this->settingsPresetProvider->values('slider', 'navigation_size', ['1.5rem', '3rem', '4rem', '6rem'])),
                'navigationColor' => 'rgba(250, 204, 21, 1)',
                'navigationBackgroundColor' => 'rgba(17, 24, 39, 0.85)',
                'navigationShadow' => $this->settingsPresetProvider->safeDefault('slider', 'navigation_shadow', 'none', $this->settingsPresetProvider->values('slider', 'navigation_shadow', ['none', 'soft', 'medium', 'strong', 'glow'])),
                'paginationShape' => $this->settingsPresetProvider->safeDefault('slider', 'pagination_shape', 'circle', $this->settingsPresetProvider->values('slider', 'pagination_shape', ['circle', 'square'])),
                'paginationSize' => $this->settingsPresetProvider->safeDefault('slider', 'pagination_size', '0.625rem', $this->settingsPresetProvider->values('slider', 'pagination_size', ['0.5rem', '0.625rem', '0.8rem', '1rem'])),
                'paginationShadow' => $this->settingsPresetProvider->safeDefault('slider', 'pagination_shadow', 'none', $this->settingsPresetProvider->values('slider', 'pagination_shadow', ['none', 'soft', 'medium', 'strong', 'glow'])),
                'paginationColor' => 'rgba(250, 204, 21, 0.45)',
                'paginationActiveColor' => 'rgba(250, 204, 21, 1)',
            ],
            'allow_extra_fields' => true,
            'attr' => [
                'data-controller' => 'slider-settings',
            ],
        ]);

        $resolver->setAllowedTypes('translation_mode', 'bool');
    }

    private static function normalizeNavigationSize(mixed $value): ?string
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        return match ($value) {
            'sm' => '1rem',
            'md' => '1.5rem',
            'lg' => '2rem',
            default => $value,
        };
    }

    private static function normalizePaginationSize(mixed $value): ?string
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        return match ($value) {
            'sm' => '0.5rem',
            'md' => '0.625rem',
            'lg' => '0.8rem',
            default => $value,
        };
    }

    private static function normalizeAutoplay(mixed $value): array
    {
        if (!is_array($value)) {
            return ['enabled' => false, 'interval' => 5000, 'pauseOnHover' => true];
        }

        $enabled = $value['enabled'] ?? $value['active'] ?? false;
        $interval = $value['interval'] ?? 5000;
        $pauseOnHover = $value['pauseOnHover'] ?? true;

        return [
            'enabled' => true === $enabled || 1 === $enabled || '1' === $enabled,
            'interval' => is_numeric($interval) ? (int) $interval : 5000,
            'pauseOnHover' => true === $pauseOnHover || 1 === $pauseOnHover || '1' === $pauseOnHover,
        ];
    }

    private static function normalizeParallax(mixed $value): array
    {
        if (!is_array($value)) {
            return ['strength' => null];
        }

        $strength = $value['strength'] ?? null;
        if (!is_string($strength)) {
            return ['strength' => null];
        }

        $strength = trim($strength);
        if ('' === $strength) {
            return ['strength' => null];
        }

        return [
            'strength' => $strength,
        ];
    }

    /**
     * @param array<int, scalar> $values
     *
     * @return array<string, string>
     */
    private static function remChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $valueString = (string) $value;
            $label = '0' === $valueString ? '0' : sprintf('%s rem', str_replace('rem', '', $valueString));
            $choices[$label] = $valueString;
        }

        return $choices;
    }

    /**
     * @param array<int, scalar> $values
     *
     * @return array<string, string>
     */
    private static function labeledChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $valueString = (string) $value;
            $choices[ucwords(str_replace(['-', '_'], ' ', $valueString))] = $valueString;
        }

        return $choices;
    }
}
