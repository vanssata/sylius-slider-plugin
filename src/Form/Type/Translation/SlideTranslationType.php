<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Translation;

use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SlideContentSettingsType;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SlideSettingsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SlideTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $descriptionType = class_exists('MonsieurBiz\\SyliusRichEditorPlugin\\Form\\Type\\RichEditorType')
            ? 'MonsieurBiz\\SyliusRichEditorPlugin\\Form\\Type\\RichEditorType'
            : TextareaType::class;

        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('description', $descriptionType, ['required' => false])
            ->add('buttonLabel', TextType::class, ['required' => false, 'row_attr' => ['data-slider-settings-custom-only' => '1']])
            ->add('url', TextType::class, ['required' => false, 'row_attr' => ['data-slider-settings-custom-only' => '1']])
            ->add('settings', SlideSettingsType::class, [
                'required' => false,
                'property_path' => 'slideSettings',
                'translation_mode' => true,
            ])
            ->add('content', SlideContentSettingsType::class, [
                'required' => false,
                'property_path' => 'contentSettings',
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
