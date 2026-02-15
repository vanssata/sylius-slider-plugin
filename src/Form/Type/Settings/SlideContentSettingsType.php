<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SlideContentSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slideCover', SlideCoverContentSettingsType::class, [
                'required' => false,
                'help' => 'Accessibility metadata for primary slide image.',
            ])
            ->add('slideCoverAlt', TextType::class, [
                'required' => false,
                'help' => 'Alternative text for the slide image.',
            ])
            ->add('slideCoverTitle', TextType::class, [
                'required' => false,
                'help' => 'Title attribute for the slide image.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => fn () => [
                'slideCover' => [
                    'alt' => null,
                    'title' => null,
                ],
                'slideCoverAlt' => null,
                'slideCoverTitle' => null,
            ],
            'allow_extra_fields' => true,
        ]);
    }
}
