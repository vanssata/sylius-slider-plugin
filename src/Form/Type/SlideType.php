<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
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
use Symfony\Component\Form\FormError;
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
use Vanssa\SyliusSliderPlugin\Video\VideoProviderRegistry;

final class SlideType extends AbstractType
{
    /** @var array<string, array{url: string, remove: string, getter: string, setter: string}> file field => external-URL wiring per video slot */
    private const VIDEO_SLOTS = [
        'slideCoverVideoFile' => ['url' => 'slideCoverVideoUrl', 'remove' => 'slideCoverVideoRemove', 'getter' => 'getSlideCoverVideo', 'setter' => 'setSlideCoverVideo'],
        'slideCoverVideoMobileFile' => ['url' => 'slideCoverVideoMobileUrl', 'remove' => 'slideCoverVideoMobileRemove', 'getter' => 'getSlideCoverVideoMobile', 'setter' => 'setSlideCoverVideoMobile'],
        'slideCoverVideoTabletFile' => ['url' => 'slideCoverVideoTabletUrl', 'remove' => 'slideCoverVideoTabletRemove', 'getter' => 'getSlideCoverVideoTablet', 'setter' => 'setSlideCoverVideoTablet'],
    ];

    /** @var array<string, array{remove: string, setter: string, directory: string}> file field => storage wiring per image slot */
    private const IMAGE_SLOTS = [
        'slideCoverFile' => ['remove' => 'slideCoverRemove', 'setter' => 'setSlideCover', 'directory' => 'slider/base-cover'],
        'slideCoverMobileFile' => ['remove' => 'slideCoverMobileRemove', 'setter' => 'setSlideCoverMobile', 'directory' => 'slider/base-cover-mobile'],
        'slideCoverTabletFile' => ['remove' => 'slideCoverTabletRemove', 'setter' => 'setSlideCoverTablet', 'directory' => 'slider/base-cover-tablet'],
    ];

    public function __construct(
        private readonly UploadedMediaStorage $uploadedMediaStorage,
        private readonly ManagerRegistry $managerRegistry,
        private readonly VideoProviderRegistry $videoProviderRegistry,
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
            ->add('channels', ChannelChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'label' => 'Channels',
                'help' => 'Leave empty to display this slide on every channel.',
            ])
            ->add('slideCoverFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverMobileFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverTabletFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverVideoFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverVideoMobileFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverVideoTabletFile', FileType::class, ['required' => false, 'mapped' => false])
            ->add('slideCoverVideoUrl', TextType::class, self::videoUrlFieldOptions())
            ->add('slideCoverVideoMobileUrl', TextType::class, self::videoUrlFieldOptions())
            ->add('slideCoverVideoTabletUrl', TextType::class, self::videoUrlFieldOptions())
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
                // Texts are translated per locale; the base form holds only
                // display configuration.
                'include_texts' => false,
            ])
            ->add('addButton', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Add button/link',
                'help' => 'Show a button or link on this slide.',
                'attr' => [
                    'data-vanssa-slider-settings-target' => 'addButton',
                    'data-action' => 'vanssa-slider-settings#refresh',
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

        self::addMediaRemovalFields($builder);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $slide = $event->getData();
            if (!$slide instanceof Slide) {
                return;
            }

            $this->addCodeField($event->getForm(), null !== $slide->getId());
        });

        // Unmapped children must be populated in POST_SET_DATA — the data
        // mapper resets them to their configured data right after PRE_SET_DATA.
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $slide = $event->getData();
            if (!$slide instanceof Slide) {
                return;
            }

            $event->getForm()->get('addButton')->setData(
                null !== $slide->getButtonLabel() || null !== $slide->getUrl(),
            );

            // Prefill the external-URL inputs when the stored reference is an
            // external provider URL (self-hosted paths stay file-managed).
            foreach (self::VIDEO_SLOTS as $slot) {
                $stored = $slide->{$slot['getter']}();
                if (is_string($stored) && $this->videoProviderRegistry->isExternal($stored)) {
                    $event->getForm()->get($slot['url'])->setData($stored);
                }
            }

            $codes = $slide->getChannelCodes();
            if ([] === $codes) {
                return;
            }

            $repository = $this->managerRegistry->getRepository(Channel::class);
            $channels = $repository->findBy(['code' => $codes]);
            $event->getForm()->get('channels')->setData(new ArrayCollection($channels));
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

            // A fresh upload always wins; otherwise the "remove" flag from the
            // media tile's × empties the slot.
            foreach (self::IMAGE_SLOTS as $fileField => $slot) {
                $upload = $form->get($fileField)->getData();
                if ($upload instanceof UploadedFile) {
                    $slide->{$slot['setter']}($this->uploadedMediaStorage->store($upload, $slot['directory']));

                    continue;
                }

                if (true === $form->get($slot['remove'])->getData()) {
                    $slide->{$slot['setter']}(null);
                }
            }

            // External video URLs win over file uploads for their slot; a
            // cleared URL whose stored value was external removes the video.
            $externalHandled = [];
            foreach (self::VIDEO_SLOTS as $fileField => $slot) {
                $urlValue = $form->get($slot['url'])->getData();
                $url = is_string($urlValue) ? trim($urlValue) : '';
                if ('' !== $url) {
                    $normalized = $this->videoProviderRegistry->normalize($url);
                    if (null === $normalized) {
                        $form->get($slot['url'])->addError(new FormError('Unsupported video URL — only YouTube links are accepted.'));

                        continue;
                    }

                    $slide->{$slot['setter']}($normalized);
                    $externalHandled[$fileField] = true;

                    continue;
                }

                $stored = $slide->{$slot['getter']}();
                if (is_string($stored) && $this->videoProviderRegistry->isExternal($stored)) {
                    $slide->{$slot['setter']}(null);
                }
            }

            foreach (self::VIDEO_SLOTS as $fileField => $slot) {
                if (isset($externalHandled[$fileField])) {
                    continue;
                }

                $video = $form->get($fileField)->getData();
                if ($video instanceof UploadedFile) {
                    $slide->{$slot['setter']}($this->uploadedMediaStorage->store($video, 'slider/base-cover-video'));

                    continue;
                }

                if (true === $form->get($slot['remove'])->getData()) {
                    $slide->{$slot['setter']}(null);
                }
            }
        });
    }

    /**
     * One unmapped "remove this media" checkbox per image/video slot, toggled
     * by the × on the slot's preview tile
     * (admin/shared/form/media_upload_field.html.twig). Unmapped and
     * label-less: the tile owns the whole interaction.
     */
    private static function addMediaRemovalFields(FormBuilderInterface $builder): void
    {
        $removeFields = array_merge(
            array_column(self::IMAGE_SLOTS, 'remove'),
            array_column(self::VIDEO_SLOTS, 'remove'),
        );

        foreach ($removeFields as $field) {
            $builder->add($field, CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => false,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function videoUrlFieldOptions(): array
    {
        return [
            'required' => false,
            'mapped' => false,
            'label' => 'External video URL',
            'help' => 'Paste a YouTube link instead of uploading a file — it wins over the upload for this slot; clear it to remove the external video.',
            'attr' => ['placeholder' => 'https://www.youtube.com/watch?v=…'],
        ];
    }

    private function addCodeField(FormBuilderInterface|FormInterface $form, bool $disabled): void
    {
        $form->add('code', TextType::class, [
            'label' => 'sylius.ui.code',
            'disabled' => $disabled,
            // Empty submissions map '' (not null) so NotBlank renders a form
            // error instead of a TypeError 500 in the strict-typed setter.
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Slide::class,
            'allow_extra_fields' => true,
            'attr' => [
                'data-controller' => 'vanssa-slider-settings',
            ],
        ]);
    }
}
