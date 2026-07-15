<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Translation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SlideSettingsType;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;

final class SlideTranslationType extends AbstractType
{
    public function __construct(
        private readonly UploadedMediaStorage $uploadedMediaStorage,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'sylius.ui.name',
                'help' => 'Translated slide title shown in this locale.',
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('addButton', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Translate button/link',
                'help' => 'Override the button label and link for this locale.',
                'attr' => [
                    'data-slider-settings-target' => 'addButton',
                    'data-action' => 'slider-settings#refresh',
                ],
            ])
            ->add('buttonLabel', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('url', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 1024]),
                    new Assert\Url(),
                ],
            ])
            ->add('overrideMedia', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Override media for this locale',
                'help' => 'Use locale-specific images instead of the main slide media.',
                'attr' => [
                    'data-slider-settings-target' => 'overrideMedia',
                    'data-action' => 'slider-settings#refresh',
                ],
            ])
            ->add('slideCoverFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverMobileFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverTabletFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('overrideSettings', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Override display settings for this locale',
                'help' => 'Override layout, heading tag, colors and effects for this locale.',
                'attr' => [
                    'data-slider-settings-target' => 'overrideSettings',
                    'data-action' => 'slider-settings#refresh',
                ],
            ])
            ->add('settings', SlideSettingsType::class, [
                'required' => false,
                'property_path' => 'slideSettings',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $translation = $event->getData();
            if (!$translation instanceof SlideTranslation) {
                return;
            }

            $form = $event->getForm();
            $form->get('addButton')->setData($translation->isButtonOverrideEnabled());
            $form->get('overrideMedia')->setData($translation->isMediaOverrideEnabled());
            $form->get('overrideSettings')->setData($translation->isSettingsOverrideEnabled());
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $translation = $event->getData();
            if (!$translation instanceof SlideTranslation) {
                return;
            }

            $form = $event->getForm();
            $addButton = true === $form->get('addButton')->getData();
            $overrideMedia = true === $form->get('overrideMedia')->getData();
            $overrideSettings = true === $form->get('overrideSettings')->getData();

            if (!$addButton) {
                $translation->setButtonLabel(null);
                $translation->setUrl(null);
            }

            foreach ([
                'slideCoverFile' => 'setSlideCover',
                'slideCoverMobileFile' => 'setSlideCoverMobile',
                'slideCoverTabletFile' => 'setSlideCoverTablet',
            ] as $field => $setter) {
                $file = $form->get($field)->getData();
                if ($file instanceof UploadedFile) {
                    $translation->{$setter}($this->uploadedMediaStorage->store($file, 'slider/translation-cover'));
                }
            }

            $translation->setOverrides([
                'button' => $addButton,
                'media' => $overrideMedia,
                'settings' => $overrideSettings,
            ]);
        });
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
