<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SliderSettingsType;
use Vanssa\SyliusSliderPlugin\Form\Type\Translation\SliderTranslationType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class)
            ->add('enabled', ChoiceType::class, [
                'required' => false,
                'expanded' => false,
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'label' => 'Enabled',
            ])
            ->add('settings', SliderSettingsType::class, [
                'required' => false,
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => SliderTranslationType::class,
                'label' => false,
                'by_reference' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $slider = $event->getData();
            if (!$slider instanceof Slider) {
                return;
            }

            if ('' === trim($slider->getName())) {
                $slider->setName($slider->getCode());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Slider::class,
            'allow_extra_fields' => true,
        ]);
    }
}
