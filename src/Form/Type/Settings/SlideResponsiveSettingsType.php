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
                'include_texts' => $options['include_texts'],
            ])
            ->add('tablet', SlideResponsiveBreakpointSettingsType::class, [
                'required' => false,
                'help' => 'Tablet breakpoint overrides.',
                'include_texts' => $options['include_texts'],
            ])
            ->add('mobile', SlideResponsiveBreakpointSettingsType::class, [
                'required' => false,
                'help' => 'Mobile breakpoint overrides.',
                'include_texts' => $options['include_texts'],
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
            'include_texts' => true,
        ]);

        $resolver->setAllowedTypes('include_texts', 'bool');
    }
}
