<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Translation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanssa\SyliusSliderPlugin\Entity\SliderTranslation;

final class SliderTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SliderTranslation::class,
            'label' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
