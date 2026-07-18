<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;
use Vanssa\SyliusSliderPlugin\Form\DataTransformer\JsonArrayTransformer;
use Vanssa\SyliusSliderPlugin\Preset\SettingsCapture;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;

final class StylePresetType extends AbstractType
{
    public function __construct(
        private readonly UploadedMediaStorage $uploadedMediaStorage,
        private readonly SettingsCapture $settingsCapture,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addCodeAndTypeFields($builder, false);

        $builder
            ->add('label', TextType::class, [
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('enabled', ChoiceType::class, [
                'required' => false,
                'choices' => ['Yes' => true, 'No' => false],
            ])
            ->add('position', IntegerType::class, [
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('settings', TextareaType::class, [
                'required' => false,
                'help' => 'Flat JSON map of dot-paths to values, e.g. {"settings.responsive.desktop.textColor": "rgba(255,255,255,1)"} for slides or {"settings.autoplay.enabled": true} for sliders. See the README "Style presets" section for the full key reference.',
                'attr' => ['rows' => 14, 'class' => 'font-monospace'],
            ])
            ->add('mockupImage', HiddenType::class, [
                'required' => false,
                'attr' => ['data-vanssa-mockup-picker-target' => 'valueField'],
            ])
            ->add('mockupImageFile', FileType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Custom mockup image',
            ])
            ->add('slides', EntityType::class, [
                'class' => Slide::class,
                'choice_label' => 'name',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'by_reference' => false,
                'help' => 'Slider presets only: slides copied into a new slider created from this preset.',
            ])
            ->add('captureSlide', EntityType::class, [
                'class' => Slide::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'placeholder' => '— none —',
                'label' => 'Capture settings from slide',
                'help' => 'When settings are left empty, fill them from this existing slide.',
            ])
            ->add('captureSlider', EntityType::class, [
                'class' => Slider::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'placeholder' => '— none —',
                'label' => 'Capture settings from slider',
                'help' => 'When settings are left empty, fill them from this existing slider.',
            ])
        ;

        $builder->get('settings')->addModelTransformer(new JsonArrayTransformer());

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $preset = $event->getData();
            if (!$preset instanceof StylePreset) {
                return;
            }

            $this->addCodeAndTypeFields($event->getForm(), null !== $preset->getId());

            // The split menu links to /new?type=slider|slide — preselect it.
            if (null === $preset->getId()) {
                $requestedType = $this->requestStack->getCurrentRequest()?->query->get('type');
                if (\in_array($requestedType, [StylePreset::TYPE_SLIDER, StylePreset::TYPE_SLIDE], true)) {
                    $preset->setType($requestedType);
                }
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $preset = $event->getData();
            if (!$preset instanceof StylePreset) {
                return;
            }

            $form = $event->getForm();

            /** @var UploadedFile|null $mockup */
            $mockup = $form->get('mockupImageFile')->getData();
            if ($mockup instanceof UploadedFile) {
                $preset->setMockupImage($this->uploadedMediaStorage->store($mockup, 'slider/preset-mockups'));
            }

            if ($preset->getSettings() === []) {
                $captureSlide = $form->get('captureSlide')->getData();
                $captureSlider = $form->get('captureSlider')->getData();
                if ($preset->getType() === StylePreset::TYPE_SLIDE && $captureSlide instanceof Slide) {
                    $preset->setSettings($this->settingsCapture->fromSlide($captureSlide));
                } elseif ($preset->getType() === StylePreset::TYPE_SLIDER && $captureSlider instanceof Slider) {
                    $preset->setSettings($this->settingsCapture->fromSlider($captureSlider));
                }
            }

            // Source slides only make sense on slider presets.
            if ($preset->getType() === StylePreset::TYPE_SLIDE) {
                foreach ($preset->getSlides()->toArray() as $slide) {
                    $preset->removeSlide($slide);
                }
            }
        });
    }

    private function addCodeAndTypeFields(FormBuilderInterface|FormInterface $form, bool $disabled): void
    {
        $form->add('code', TextType::class, [
            'label' => 'sylius.ui.code',
            'disabled' => $disabled,
            'empty_data' => '',
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 64]),
                new Assert\Regex([
                    'pattern' => '/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
                    'message' => 'Code may contain only letters, numbers, dashes and underscores.',
                ]),
            ],
        ]);

        $form->add('type', ChoiceType::class, [
            'disabled' => $disabled,
            'empty_data' => StylePreset::TYPE_SLIDE,
            'choices' => [
                'Slide preset' => StylePreset::TYPE_SLIDE,
                'Slider preset' => StylePreset::TYPE_SLIDER,
            ],
            'attr' => [
                'data-vanssa-mockup-picker-target' => 'typeField',
                'data-action' => 'vanssa-mockup-picker#refresh',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StylePreset::class,
            'allow_extra_fields' => true,
        ]);
    }
}
