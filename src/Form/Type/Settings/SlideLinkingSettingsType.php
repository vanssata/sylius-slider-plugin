<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SlideLinkingSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Custom URL' => 'custom',
                    'Product' => 'product',
                    'Category' => 'category',
                ],
                'required' => false,
                'attr' => [
                    'data-slider-settings-target' => 'linkingType',
                    'data-action' => 'change->slider-settings#linkingTypeChanged',
                ],
            ])
            ->add('overlay', CheckboxType::class, ['required' => false])
            ->add('openExternal', CheckboxType::class, ['required' => false])
            ->add('showProductFocusImage', CheckboxType::class, ['required' => false])
            ->add('buttonAppearance', ChoiceType::class, [
                'choices' => [
                    'Primary' => 'primary',
                    'Secondary' => 'secondary',
                    'Success' => 'success',
                    'Danger' => 'danger',
                ],
                'required' => false,
            ])
            ->add('buttonSize', ChoiceType::class, [
                'choices' => [
                    'Small' => 'sm',
                    'Medium' => 'md',
                    'Large' => 'lg',
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => fn () => [
                'type' => 'custom',
                'overlay' => false,
                'openExternal' => false,
                'showProductFocusImage' => true,
                'buttonAppearance' => 'primary',
                'buttonSize' => 'md',
            ],
            'allow_extra_fields' => true,
        ]);
    }
}
