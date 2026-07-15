<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Sylius\Component\Core\Model\Channel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SlideSettingsType;
use Vanssa\SyliusSliderPlugin\Form\Type\Translation\SlideTranslationType;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;

final class SlideType extends AbstractType
{
    public function __construct(
        private readonly UploadedMediaStorage $uploadedMediaStorage,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addCodeField($builder, false);

        $builder
            ->add('sliders', EntityType::class, [
                'class' => Slider::class,
                'choice_label' => 'name',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'by_reference' => false,
            ])
            ->add('channels', EntityType::class, [
                'class' => Channel::class,
                'choice_label' => 'name',
                'required' => false,
                'multiple' => true,
                'mapped' => false,
                'label' => 'Channels',
                'help' => 'Leave empty to display this slide on every channel.',
                'autocomplete' => true,
            ])
            ->add('slideCoverFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverMobileFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverTabletFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverVideoFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('presentationMediaFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('position', IntegerType::class)
            ->add('enabled', ChoiceType::class, [
                'required' => false,
                'expanded' => false,
                'label' => 'Enabled',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
            ])
            ->add('settings', SlideSettingsType::class, [
                'required' => false,
                'property_path' => 'slideSettings',
            ])
            ->add('addButton', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Add button/link',
                'help' => 'Show a button or link on this slide.',
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
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => SlideTranslationType::class,
                'label' => false,
                'by_reference' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $slide = $event->getData();
            if (!$slide instanceof Slide) {
                return;
            }

            $this->addCodeField($event->getForm(), null !== $slide->getId());

            $event->getForm()->get('addButton')->setData(
                null !== $slide->getButtonLabel() || null !== $slide->getUrl(),
            );

            $codes = $slide->getChannelCodes();
            if ([] === $codes) {
                return;
            }

            $repository = $this->managerRegistry->getRepository(Channel::class);
            $channels = $repository->findBy(['code' => $codes]);
            $event->getForm()->get('channels')->setData($channels);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $slide = $event->getData();
            if (!$slide instanceof Slide) {
                return;
            }

            $form = $event->getForm();

            $channels = $form->get('channels')->getData();
            $codes = [];
            if (is_iterable($channels)) {
                foreach ($channels as $channel) {
                    if ($channel instanceof Channel && '' !== $channel->getCode()) {
                        $codes[] = $channel->getCode();
                    }
                }
            }
            $slide->setChannelCodes(array_values(array_unique($codes)));

            if ('' === trim($slide->getName())) {
                $slide->setName($slide->getCode());
            }

            if (true !== $form->get('addButton')->getData()) {
                $slide->setButtonLabel(null);
                $slide->setUrl(null);
            }

            /** @var UploadedFile|null $cover */
            $cover = $form->get('slideCoverFile')->getData();
            if ($cover instanceof UploadedFile) {
                $slide->setSlideCover($this->uploadedMediaStorage->store($cover, 'slider/base-cover'));
            }

            /** @var UploadedFile|null $mobile */
            $mobile = $form->get('slideCoverMobileFile')->getData();
            if ($mobile instanceof UploadedFile) {
                $slide->setSlideCoverMobile($this->uploadedMediaStorage->store($mobile, 'slider/base-cover-mobile'));
            }

            /** @var UploadedFile|null $tablet */
            $tablet = $form->get('slideCoverTabletFile')->getData();
            if ($tablet instanceof UploadedFile) {
                $slide->setSlideCoverTablet($this->uploadedMediaStorage->store($tablet, 'slider/base-cover-tablet'));
            }

            /** @var UploadedFile|null $video */
            $video = $form->get('slideCoverVideoFile')->getData();
            if ($video instanceof UploadedFile) {
                $slide->setSlideCoverVideo($this->uploadedMediaStorage->store($video, 'slider/base-cover-video'));
            }

            /** @var UploadedFile|null $presentation */
            $presentation = $form->get('presentationMediaFile')->getData();
            if ($presentation instanceof UploadedFile) {
                $slide->setPresentationMedia($this->uploadedMediaStorage->store($presentation, 'slider/presentation-media'));
            }
        });
    }

    private function addCodeField(FormBuilderInterface|FormInterface $form, bool $disabled): void
    {
        $form->add('code', TextType::class, [
            'label' => 'sylius.ui.code',
            'disabled' => $disabled,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 64]),
                new Assert\Regex([
                    'pattern' => '/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
                    'message' => 'Code may contain only letters, numbers, dashes and underscores.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Slide::class,
            'allow_extra_fields' => true,
            'attr' => [
                'data-controller' => 'slider-settings',
            ],
        ]);
    }
}
