<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Translation;

use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SlideSettingsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class SlideTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('buttonLabel', TextType::class, [
                'required' => false,
                'row_attr' => ['data-slider-settings-custom-only' => '1'],
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('url', TextType::class, [
                'required' => false,
                'row_attr' => ['data-slider-settings-custom-only' => '1'],
                'constraints' => [
                    new Assert\Length(['max' => 1024]),
                    new Assert\Url(),
                ],
            ])
            ->add('settings', SlideSettingsType::class, [
                'required' => false,
                'property_path' => 'slideSettings',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SlideTranslation::class,
            'label' => false,
            'allow_extra_fields' => true,
            'attr' => [
                'data-controller' => 'slider-settings',
            ],
        ]);
    }
}
