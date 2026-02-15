<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class SlideLinkingSettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeValues = $this->settingsPresetProvider->values('slide', 'linking_type', ['custom', 'product', 'category']);
        $buttonAppearanceValues = $this->settingsPresetProvider->values('slide', 'button_appearance', ['primary', 'secondary', 'success', 'danger']);
        $buttonSizeValues = $this->settingsPresetProvider->values('slide', 'button_size', ['sm', 'md', 'lg']);
        $buttonPositionValues = $this->settingsPresetProvider->values('slide', 'button_position', ['content_left', 'content_center', 'content_right', 'slider_bottom_left', 'slider_bottom_left_2_12', 'slider_bottom_left_3_12', 'slider_bottom_left_4_12', 'slider_bottom_center', 'slider_bottom_right', 'slider_bottom_right_2_12', 'slider_bottom_right_3_12', 'slider_bottom_right_4_12']);

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => self::labelChoices($typeValues),
                'required' => false,
                'help' => 'Select what this slide should link to when clicked.',
                'attr' => [
                    'data-slider-settings-target' => 'linkingType',
                    'data-action' => 'change->slider-settings#linkingTypeChanged',
                ],
                'constraints' => [
                    new Assert\Choice(['choices' => $typeValues]),
                ],
            ])
            ->add('overlay', CheckboxType::class, [
                'required' => false,
                'help' => 'Enable clickable overlay over the full slide.',
            ])
            ->add('openExternal', CheckboxType::class, [
                'required' => false,
                'help' => 'Open link in a new browser tab.',
            ])
            ->add('showProductFocusImage', CheckboxType::class, [
                'required' => false,
                'help' => 'Use focused product image when linked to a product.',
            ])
            ->add('buttonAppearance', ChoiceType::class, [
                'choices' => self::labelChoices($buttonAppearanceValues),
                'required' => false,
                'help' => 'Visual style of the slide action button.',
                'constraints' => [
                    new Assert\Choice(['choices' => $buttonAppearanceValues]),
                ],
            ])
            ->add('buttonSize', ChoiceType::class, [
                'choices' => self::labelChoices($buttonSizeValues),
                'required' => false,
                'help' => 'Size of the slide action button.',
                'constraints' => [
                    new Assert\Choice(['choices' => $buttonSizeValues]),
                ],
            ])
            ->add('buttonPosition', ChoiceType::class, [
                'choices' => self::positionChoices($buttonPositionValues),
                'required' => false,
                'help' => 'Position of the action button in content or at slider bottom.',
                'constraints' => [
                    new Assert\Choice(['choices' => $buttonPositionValues]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => fn (): array => [
                'type' => $this->settingsPresetProvider->safeDefault('slide', 'linking_type', 'custom', $this->settingsPresetProvider->values('slide', 'linking_type', ['custom', 'product', 'category'])),
                'overlay' => false,
                'openExternal' => false,
                'showProductFocusImage' => true,
                'buttonAppearance' => $this->settingsPresetProvider->safeDefault('slide', 'button_appearance', 'primary', $this->settingsPresetProvider->values('slide', 'button_appearance', ['primary', 'secondary', 'success', 'danger'])),
                'buttonSize' => $this->settingsPresetProvider->safeDefault('slide', 'button_size', 'md', $this->settingsPresetProvider->values('slide', 'button_size', ['sm', 'md', 'lg'])),
                'buttonPosition' => $this->settingsPresetProvider->safeDefault('slide', 'button_position', 'content_left', $this->settingsPresetProvider->values('slide', 'button_position', ['content_left', 'content_center', 'content_right', 'slider_bottom_left', 'slider_bottom_left_2_12', 'slider_bottom_left_3_12', 'slider_bottom_left_4_12', 'slider_bottom_center', 'slider_bottom_right', 'slider_bottom_right_2_12', 'slider_bottom_right_3_12', 'slider_bottom_right_4_12'])),
            ],
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * @param array<int, scalar> $values
     *
     * @return array<string, string>
     */
    private static function labelChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $valueString = (string) $value;
            $label = ucwords(str_replace(['-', '_'], ' ', $valueString));
            $choices[$label] = $valueString;
        }

        return $choices;
    }

    /**
     * @param array<int, scalar> $values
     *
     * @return array<string, string>
     */
    private static function positionChoices(array $values): array
    {
        $labels = [
            'content_left' => 'In content: bottom left',
            'content_center' => 'In content: bottom center',
            'content_right' => 'In content: bottom right',
            'slider_bottom_left' => 'Slider bottom: left edge',
            'slider_bottom_left_2_12' => 'Slider bottom: left + 2/12 width',
            'slider_bottom_left_3_12' => 'Slider bottom: left + 3/12 width',
            'slider_bottom_left_4_12' => 'Slider bottom: left + 4/12 width',
            'slider_bottom_center' => 'Slider bottom: center',
            'slider_bottom_right' => 'Slider bottom: right edge',
            'slider_bottom_right_2_12' => 'Slider bottom: right - 2/12 width',
            'slider_bottom_right_3_12' => 'Slider bottom: right - 3/12 width',
            'slider_bottom_right_4_12' => 'Slider bottom: right - 4/12 width',
        ];

        $choices = [];
        foreach ($values as $value) {
            $valueString = (string) $value;
            $choices[$labels[$valueString] ?? ucwords(str_replace(['-', '_'], ' ', $valueString))] = $valueString;
        }

        return $choices;
    }
}
