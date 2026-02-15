<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SlideSettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('linking', SlideLinkingSettingsType::class, [
                'required' => false,
                'help' => 'Link and button behavior for this slide.',
            ])
            ->add('responsive', SlideResponsiveSettingsType::class, [
                'required' => false,
                'help' => 'Device-specific overrides for slide settings.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => fn (): array => [
                'linking' => [
                    'type' => $this->settingsPresetProvider->safeDefault('slide', 'linking_type', 'custom', $this->settingsPresetProvider->values('slide', 'linking_type', ['custom', 'product', 'category'])),
                    'overlay' => false,
                    'openExternal' => false,
                    'showProductFocusImage' => true,
                    'buttonAppearance' => $this->settingsPresetProvider->safeDefault('slide', 'button_appearance', 'primary', $this->settingsPresetProvider->values('slide', 'button_appearance', ['primary', 'secondary', 'success', 'danger'])),
                    'buttonSize' => $this->settingsPresetProvider->safeDefault('slide', 'button_size', 'md', $this->settingsPresetProvider->values('slide', 'button_size', ['sm', 'md', 'lg'])),
                    'buttonPosition' => $this->settingsPresetProvider->safeDefault('slide', 'button_position', 'content_left', $this->settingsPresetProvider->values('slide', 'button_position', ['content_left', 'content_center', 'content_right', 'slider_bottom_left', 'slider_bottom_left_2_12', 'slider_bottom_left_3_12', 'slider_bottom_left_4_12', 'slider_bottom_center', 'slider_bottom_right', 'slider_bottom_right_2_12', 'slider_bottom_right_3_12', 'slider_bottom_right_4_12'])),
                ],
                'responsive' => [
                    'desktop' => [
                        'headlineFontSize' => (string) $this->settingsPresetProvider->default('slide', 'headline_font_size', '1.5rem'),
                        'descriptionFontSize' => (string) $this->settingsPresetProvider->default('slide', 'description_font_size', '1rem'),
                        'buttonFontSize' => (string) $this->settingsPresetProvider->default('slide', 'button_font_size', '1.2rem'),
                    ],
                    'tablet' => [],
                    'mobile' => [],
                ],
            ],
            'allow_extra_fields' => true,
        ]);
    }
}
