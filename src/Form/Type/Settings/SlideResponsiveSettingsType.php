<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SlideResponsiveSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('desktop', SlideResponsiveBreakpointSettingsType::class, [
                'required' => false,
                'help' => 'Desktop breakpoint overrides.',
            ])
            ->add('tablet', SlideResponsiveBreakpointSettingsType::class, [
                'required' => false,
                'help' => 'Tablet breakpoint overrides.',
            ])
            ->add('mobile', SlideResponsiveBreakpointSettingsType::class, [
                'required' => false,
                'help' => 'Mobile breakpoint overrides.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => static fn (): array => [
                'desktop' => [],
                'tablet' => [],
                'mobile' => [],
            ],
            'allow_extra_fields' => true,
        ]);
    }
}
