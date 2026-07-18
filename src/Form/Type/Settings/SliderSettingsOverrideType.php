<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Per-LOCALE slider settings overrides: `base` overrides the slider's
 * desktop settings for that locale; `responsive.tablet/mobile` override the
 * locale's breakpoint variants. Every field is optional — empty inherits
 * (partial overrides are the point).
 */
final class SliderSettingsOverrideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('base', SliderResponsiveBreakpointSettingsType::class, ['required' => false])
            ->add('responsive', ResponsiveSliderSettingsWrapperType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => static fn (): array => ['base' => [], 'responsive' => ['tablet' => [], 'mobile' => []]],
            'allow_extra_fields' => true,
        ]);
    }
}
