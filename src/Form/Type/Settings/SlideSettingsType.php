<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SlideSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['translation_mode']) {
            $builder->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => 'Use custom options for this locale',
                'attr' => [
                    'data-slider-settings-target' => 'translationEnabled',
                    'data-action' => 'change->slider-settings#translationEnabledChanged',
                ],
            ]);
        }

        $builder
            ->add('headlineElement', ChoiceType::class, [
                'choices' => [
                    'Div' => 'div',
                    'H1' => 'h1',
                    'H2' => 'h2',
                    'H3' => 'h3',
                    'H4' => 'h4',
                    'H5' => 'h5',
                    'H6' => 'h6',
                ],
                'required' => false,
            ])
            ->add('contentHorizontalPosition', ChoiceType::class, [
                'choices' => [
                    'Left' => 'start',
                    'Center' => 'center',
                    'Right' => 'end',
                ],
                'required' => false,
            ])
            ->add('contentVerticalPosition', ChoiceType::class, [
                'choices' => [
                    'Top' => 'top',
                    'Center' => 'center',
                    'Bottom' => 'bottom',
                ],
                'required' => false,
            ])
            ->add('contentTextAlign', ChoiceType::class, [
                'choices' => [
                    'Left' => 'left',
                    'Center' => 'center',
                    'Right' => 'right',
                ],
                'required' => false,
            ])
            ->add('contentAnimation', ChoiceType::class, [
                'choices' => [
                    'Fade up' => 'fade-up',
                    'Fade right' => 'fade-right',
                    'Zoom in' => 'zoom-in',
                    'None' => 'none',
                ],
                'required' => false,
            ])
            ->add('animationDuration', ChoiceType::class, [
                'choices' => [
                    'Very fast (250ms)' => 250,
                    'Fast (400ms)' => 400,
                    'Normal (500ms)' => 500,
                    'Slow (700ms)' => 700,
                    'Very slow (1000ms)' => 1000,
                ],
                'required' => false,
            ])
            ->add('animationDelay', ChoiceType::class, [
                'choices' => [
                    'No delay (0ms)' => 0,
                    'Short (100ms)' => 100,
                    'Medium (200ms)' => 200,
                    'Long (350ms)' => 350,
                    'Very long (500ms)' => 500,
                ],
                'required' => false,
            ])
            ->add('textColor', TextType::class, ['required' => false])
            ->add('headlineColor', TextType::class, ['required' => false])
            ->add('descriptionColor', TextType::class, ['required' => false])
            ->add('backgroundColor', TextType::class, ['required' => false])
            ->add('mediaOverlayColor', TextType::class, ['required' => false])
            ->add('backgroundBlurPreset', ChoiceType::class, [
                'choices' => [
                    'No blur background' => 'none',
                    'Soft blur' => 'soft',
                    'Medium blur' => 'medium',
                    'Strong blur' => 'strong',
                ],
                'required' => false,
            ])
            ->add('enableTextBlur', CheckboxType::class, ['required' => false])
            ->add('contentBlurStrength', ChoiceType::class, [
                'choices' => [
                    'Light (4px)' => 4,
                    'Soft (8px)' => 8,
                    'Medium (12px)' => 12,
                    'Strong (16px)' => 16,
                    'Heavy (24px)' => 24,
                ],
                'required' => false,
            ])
            ->add('contentPadding', ChoiceType::class, [
                'choices' => [
                    'None' => '0',
                    'Small' => '0.75rem',
                    'Medium' => '1rem',
                    'Large' => '1.5rem',
                    'XL' => '2rem',
                ],
                'required' => false,
            ])
            ->add('contentMargin', ChoiceType::class, [
                'choices' => [
                    'None' => '0',
                    'Small' => '0.5rem',
                    'Medium' => '1rem',
                    'Large' => '1.5rem',
                    'XL' => '2rem',
                ],
                'required' => false,
            ])
            ->add('customCssClass', TextType::class, ['required' => false])
            ->add('borderRadius', ChoiceType::class, [
                'choices' => [
                    'Square (0px)' => 0,
                    'Soft (6px)' => 6,
                    'Rounded (10px)' => 10,
                    'Large rounded (16px)' => 16,
                    'Pill (24px)' => 24,
                ],
                'required' => false,
            ])
            ->add('linking', SlideLinkingSettingsType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_mode' => false,
            'data_class' => null,
            'empty_data' => fn () => [
                'enabled' => false,
                'headlineElement' => 'div',
                'contentHorizontalPosition' => 'start',
                'contentVerticalPosition' => 'bottom',
                'contentTextAlign' => 'left',
                'contentAnimation' => 'fade-up',
                'animationDuration' => 500,
                'animationDelay' => 0,
                'textColor' => 'rgba(255, 255, 255, 1)',
                'headlineColor' => null,
                'descriptionColor' => null,
                'backgroundColor' => null,
                'mediaOverlayColor' => null,
                'backgroundBlurPreset' => 'none',
                'enableTextBlur' => false,
                'contentBlurStrength' => 12,
                'contentPadding' => '1rem',
                'contentMargin' => '0',
                'customCssClass' => null,
                'borderRadius' => 0,
                'linking' => [
                    'type' => 'custom',
                    'overlay' => false,
                    'openExternal' => false,
                    'showProductFocusImage' => true,
                    'buttonAppearance' => 'primary',
                    'buttonSize' => 'md',
                ],
            ],
            'allow_extra_fields' => true,
        ]);

        $resolver->setAllowedTypes('translation_mode', 'bool');
    }
}
