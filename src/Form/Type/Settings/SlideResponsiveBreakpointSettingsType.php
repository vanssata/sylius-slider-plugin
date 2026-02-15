<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;
use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class SlideResponsiveBreakpointSettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsPresetProvider $settingsPresetProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $headlineElementValues = $this->settingsPresetProvider->values('slide', 'headline_element', ['div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
        $horizontalValues = $this->settingsPresetProvider->values('slide', 'content_horizontal_position', ['start', 'left_2_12', 'left_3_12', 'left_4_12', 'center', 'right_2_12', 'right_3_12', 'right_4_12', 'end']);
        $verticalValues = $this->settingsPresetProvider->values('slide', 'content_vertical_position', ['top', 'center', 'bottom']);
        $textAlignValues = $this->settingsPresetProvider->values('slide', 'content_text_align', ['left', 'center', 'right']);
        $contentAnimationValues = $this->settingsPresetProvider->values('slide', 'content_animation', ['fade-up', 'fade-right', 'zoom-in', 'none']);
        $animationDurationValues = $this->settingsPresetProvider->values('slide', 'animation_duration', [250, 400, 500, 700, 1000]);
        $animationDelayValues = $this->settingsPresetProvider->values('slide', 'animation_delay', [0, 100, 200, 350, 500]);
        $blurPresetValues = $this->settingsPresetProvider->values('slide', 'background_blur_preset', ['none', 'soft', 'medium', 'strong']);
        $contentBlurValues = $this->settingsPresetProvider->values('slide', 'content_blur_strength', [4, 8, 12, 16, 24]);
        $contentPaddingValues = $this->settingsPresetProvider->values('slide', 'content_padding', ['0', '0.75rem', '1rem', '1.5rem', '2rem']);
        $contentMarginValues = $this->settingsPresetProvider->values('slide', 'content_margin', ['0', '0.5rem', '1rem', '1.5rem', '2rem']);
        $borderRadiusValues = $this->settingsPresetProvider->values('slide', 'border_radius', [0, 6, 10, 16, 24]);
        $fontSizeValues = $this->settingsPresetProvider->values('slide', 'headline_font_size', ['0.8rem', '1rem', '1.2rem', '1.5rem', '2rem', '2.5rem', '3rem']);
        $headlineFontSizeDefault = (string) $this->settingsPresetProvider->safeDefault('slide', 'headline_font_size', '1.5rem', $fontSizeValues);
        $descriptionFontSizeValues = $this->settingsPresetProvider->values('slide', 'description_font_size', $fontSizeValues);
        $descriptionFontSizeDefault = (string) $this->settingsPresetProvider->safeDefault('slide', 'description_font_size', '1rem', $descriptionFontSizeValues);
        $buttonFontSizeValues = $this->settingsPresetProvider->values('slide', 'button_font_size', $fontSizeValues);
        $buttonFontSizeDefault = (string) $this->settingsPresetProvider->safeDefault('slide', 'button_font_size', '1.2rem', $buttonFontSizeValues);
        $textSwatches = $this->settingsPresetProvider->stringList('color_switcher.swatches.text', [
            'rgba(255, 255, 255, 1)',
            'rgba(255, 255, 255, 0.92)',
            'rgba(245, 245, 245, 1)',
            'rgba(230, 230, 230, 1)',
            'rgba(0, 0, 0, 1)',
        ]);
        $neutralSwatches = $this->settingsPresetProvider->stringList('color_switcher.swatches.neutral', [
            'rgba(0, 0, 0, 0)',
            'rgba(15, 23, 42, 0.75)',
            'rgba(17, 24, 39, 0.9)',
            'rgba(31, 41, 55, 0.85)',
            'rgba(255, 255, 255, 1)',
        ]);

        $builder
            ->add('headlineElement', ChoiceType::class, [
                'choices' => self::headlineChoices($headlineElementValues),
                'required' => false,
                'help' => 'Responsive headline tag override.',
                'constraints' => [new Assert\Choice(['choices' => $headlineElementValues])],
            ])
            ->add('title', TextType::class, [
                'required' => false,
                'help' => 'Responsive title override for this breakpoint.',
                'constraints' => [new Assert\Length(['max' => 255])],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'help' => 'Responsive description override for this breakpoint.',
                'constraints' => [new Assert\Length(['max' => 65535])],
            ])
            ->add('contentHorizontalPosition', ChoiceType::class, [
                'choices' => self::contentHorizontalChoices($horizontalValues),
                'required' => false,
                'help' => 'Horizontal position of content block (including offset presets by slider width).',
                'constraints' => [new Assert\Choice(['choices' => $horizontalValues])],
            ])
            ->add('contentVerticalPosition', ChoiceType::class, [
                'choices' => self::contentVerticalChoices($verticalValues),
                'required' => false,
                'help' => 'Vertical position of content block (including top offset presets by slider height).',
                'constraints' => [new Assert\Choice(['choices' => $verticalValues])],
            ])
            ->add('contentTextAlign', ChoiceType::class, [
                'choices' => self::labeledChoices($textAlignValues),
                'required' => false,
                'help' => 'Text alignment inside content block.',
                'constraints' => [new Assert\Choice(['choices' => $textAlignValues])],
            ])
            ->add('contentAnimation', ChoiceType::class, [
                'choices' => self::labeledChoices($contentAnimationValues),
                'required' => false,
                'help' => 'Animation effect for this breakpoint.',
                'constraints' => [new Assert\Choice(['choices' => $contentAnimationValues])],
            ])
            ->add('animationDuration', ChoiceType::class, [
                'choices' => self::millisecondsChoices($animationDurationValues),
                'required' => false,
                'help' => 'Animation duration in milliseconds.',
                'constraints' => [new Assert\Choice(['choices' => $animationDurationValues])],
            ])
            ->add('animationDelay', ChoiceType::class, [
                'choices' => self::millisecondsChoices($animationDelayValues),
                'required' => false,
                'help' => 'Delay before animation starts in milliseconds.',
                'constraints' => [new Assert\Choice(['choices' => $animationDelayValues])],
            ])
            ->add('textColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Text color for this breakpoint.',
                'picker_swatches' => $textSwatches,
                'picker_options' => ['defaultRepresentation' => 'RGBA'],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('headlineColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Headline color override.',
                'picker_swatches' => $neutralSwatches,
                'picker_options' => ['defaultRepresentation' => 'RGBA'],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('descriptionColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Description color override.',
                'picker_swatches' => $neutralSwatches,
                'picker_options' => ['defaultRepresentation' => 'RGBA'],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('backgroundColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Content background color override.',
                'picker_swatches' => $neutralSwatches,
                'picker_options' => ['defaultRepresentation' => 'RGBA'],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('mediaOverlayColor', ColorPickerType::class, [
                'required' => false,
                'help' => 'Media overlay color override.',
                'picker_swatches' => $neutralSwatches,
                'picker_options' => ['defaultRepresentation' => 'RGBA'],
                'constraints' => [new Assert\CssColor()],
            ])
            ->add('backgroundBlurPreset', ChoiceType::class, [
                'choices' => self::labeledChoices($blurPresetValues),
                'required' => false,
                'help' => 'Background blur preset override.',
                'constraints' => [new Assert\Choice(['choices' => $blurPresetValues])],
            ])
            ->add('enableTextBlur', CheckboxType::class, [
                'required' => false,
                'help' => 'Enable content blur for this breakpoint.',
            ])
            ->add('contentBlurStrength', ChoiceType::class, [
                'choices' => self::pixelChoices($contentBlurValues),
                'required' => false,
                'help' => 'Blur strength in pixels.',
                'constraints' => [new Assert\Choice(['choices' => $contentBlurValues])],
            ])
            ->add('contentPadding', ChoiceType::class, [
                'choices' => self::labeledChoices($contentPaddingValues),
                'required' => false,
                'help' => 'Inner spacing of content block.',
                'constraints' => [new Assert\Choice(['choices' => $contentPaddingValues])],
            ])
            ->add('contentMargin', ChoiceType::class, [
                'choices' => self::labeledChoices($contentMarginValues),
                'required' => false,
                'help' => 'Outer spacing around content block.',
                'constraints' => [new Assert\Choice(['choices' => $contentMarginValues])],
            ])
            ->add('customCssClass', TextType::class, [
                'required' => false,
                'help' => 'Custom CSS class(es) for this breakpoint.',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-z0-9\-_ ]*$/',
                        'message' => 'Custom CSS class contains invalid characters.',
                    ]),
                ],
            ])
            ->add('borderRadius', ChoiceType::class, [
                'choices' => self::pixelChoices($borderRadiusValues),
                'required' => false,
                'help' => 'Border radius of content block.',
                'constraints' => [new Assert\Choice(['choices' => $borderRadiusValues])],
            ])
            ->add('headlineFontSize', ChoiceType::class, [
                'required' => false,
                'choices' => self::labeledChoices($fontSizeValues),
                'help' => 'Headline font-size.',
                'empty_data' => $headlineFontSizeDefault,
                'constraints' => [new Assert\Choice(['choices' => $fontSizeValues])],
            ])
            ->add('descriptionFontSize', ChoiceType::class, [
                'required' => false,
                'choices' => self::labeledChoices($descriptionFontSizeValues),
                'help' => 'Description font-size.',
                'empty_data' => $descriptionFontSizeDefault,
                'constraints' => [new Assert\Choice(['choices' => $descriptionFontSizeValues])],
            ])
            ->add('buttonFontSize', ChoiceType::class, [
                'required' => false,
                'choices' => self::labeledChoices($buttonFontSizeValues),
                'help' => 'Button font-size.',
                'empty_data' => $buttonFontSizeDefault,
                'constraints' => [new Assert\Choice(['choices' => $buttonFontSizeValues])],
            ])
            ->add('hideTitle', CheckboxType::class, [
                'required' => false,
                'help' => 'Hide slide title on this breakpoint.',
            ])
            ->add('hideDescription', CheckboxType::class, [
                'required' => false,
                'help' => 'Hide slide description on this breakpoint.',
            ])
            ->add('hideButton', CheckboxType::class, [
                'required' => false,
                'help' => 'Hide slide button on this breakpoint.',
            ])
        ;
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
     * @param array<int, string> $values
     *
     * @return array<string, string>
     */
    private static function labeledChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $choices[ucfirst((string) $value)] = (string) $value;
        }

        return $choices;
    }

    /**
     * @param array<int, string> $values
     *
     * @return array<string, string>
     */
    private static function headlineChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $choices[strtoupper((string) $value)] = (string) $value;
        }

        return $choices;
    }

    /**
     * @param array<int, string> $values
     *
     * @return array<string, string>
     */
    private static function contentHorizontalChoices(array $values): array
    {
        $labels = [
            'start' => 'Left edge',
            'left_2_12' => 'Left + 2/12 width',
            'left_3_12' => 'Left + 3/12 width',
            'left_4_12' => 'Left + 4/12 width',
            'center' => 'Center',
            'right_2_12' => 'Right - 2/12 width',
            'right_3_12' => 'Right - 3/12 width',
            'right_4_12' => 'Right - 4/12 width',
            'end' => 'Right edge',
        ];

        $choices = [];
        foreach ($values as $value) {
            $valueString = (string) $value;
            $choices[$labels[$valueString] ?? ucfirst($valueString)] = $valueString;
        }

        return $choices;
    }

    /**
     * @param array<int, string> $values
     *
     * @return array<string, string>
     */
    private static function contentVerticalChoices(array $values): array
    {
        $labels = [
            'top' => 'Top edge',
            'top_1_5' => 'Top + 1/5 height',
            'top_2_5' => 'Top + 2/5 height',
            'top_3_5' => 'Top + 3/5 height',
            'top_4_5' => 'Top + 4/5 height',
            'center' => 'Middle',
            'bottom' => 'Bottom edge',
        ];

        $choices = [];
        foreach ($values as $value) {
            $valueString = (string) $value;
            $choices[$labels[$valueString] ?? ucfirst($valueString)] = $valueString;
        }

        return $choices;
    }

    /**
     * @param array<int, int> $values
     *
     * @return array<string, int>
     */
    private static function millisecondsChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $choices[(string) $value . ' ms'] = (int) $value;
        }

        return $choices;
    }

    /**
     * @param array<int, int> $values
     *
     * @return array<string, int>
     */
    private static function pixelChoices(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $choices[(string) $value . ' px'] = (int) $value;
        }

        return $choices;
    }
}
