<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AutoplaySettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, ['required' => false])
            ->add('interval', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    '2 seconds (Very fast)' => 2000,
                    '3 seconds (Fast)' => 3000,
                    '5 seconds (Normal)' => 5000,
                    '8 seconds (Slow)' => 8000,
                    '10 seconds (Very slow)' => 10000,
                ],
            ])
            ->add('pauseOnHover', CheckboxType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => fn () => ['enabled' => false, 'interval' => 5000, 'pauseOnHover' => true],
            'allow_extra_fields' => true,
        ]);
    }
}
