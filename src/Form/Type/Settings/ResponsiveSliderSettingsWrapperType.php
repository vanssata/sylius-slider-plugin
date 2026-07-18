<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * tablet/mobile SLIDER layout override groups — desktop is the base
 * settings themselves (unlike slides, which carry a desktop group too).
 */
final class ResponsiveSliderSettingsWrapperType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tablet', SliderResponsiveBreakpointSettingsType::class, ['required' => false])
            ->add('mobile', SliderResponsiveBreakpointSettingsType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => static fn (): array => ['tablet' => [], 'mobile' => []],
            'allow_extra_fields' => true,
        ]);
    }
}
