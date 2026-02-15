<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SlideCoverContentSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alt', TextType::class, [
                'required' => false,
                'help' => 'Alternative text used by screen readers.',
            ])
            ->add('title', TextType::class, [
                'required' => false,
                'help' => 'Tooltip/title attribute for the image.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => fn () => [
                'alt' => null,
                'title' => null,
            ],
            'allow_extra_fields' => true,
        ]);
    }
}
