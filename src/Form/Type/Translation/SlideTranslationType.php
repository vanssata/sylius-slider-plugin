<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\Type\Translation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SlideSettingsType;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;
use Vanssa\SyliusSliderPlugin\Video\VideoProviderRegistry;

final class SlideTranslationType extends AbstractType
{
    /** @var array<string, array{url: string, getter: string, setter: string}> file field => external-URL wiring per video slot */
    private const VIDEO_SLOTS = [
        'slideCoverVideoFile' => ['url' => 'slideCoverVideoUrl', 'getter' => 'getSlideCoverVideo', 'setter' => 'setSlideCoverVideo'],
        'slideCoverVideoMobileFile' => ['url' => 'slideCoverVideoMobileUrl', 'getter' => 'getSlideCoverVideoMobile', 'setter' => 'setSlideCoverVideoMobile'],
        'slideCoverVideoTabletFile' => ['url' => 'slideCoverVideoTabletUrl', 'getter' => 'getSlideCoverVideoTablet', 'setter' => 'setSlideCoverVideoTablet'],
    ];

    public function __construct(
        private readonly UploadedMediaStorage $uploadedMediaStorage,
        private readonly VideoProviderRegistry $videoProviderRegistry,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('addButton', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Overwrite',
                'help' => 'Override the button label and link for this locale.',
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
            ->add('overrideMedia', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Overwrite',
                'help' => 'Use locale-specific images instead of the main slide media.',
                'attr' => [
                    'data-vanssa-slider-settings-target' => 'overrideMedia',
                    'data-action' => 'vanssa-slider-settings#refresh',
                ],
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
            ->add('overrideLayout', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Overwrite',
                'help' => 'Override the layout for this locale.',
                'attr' => [
                    'data-vanssa-slider-settings-target' => 'overrideLayout',
                    'data-action' => 'vanssa-slider-settings#refresh',
                ],
            ])
            ->add('overrideColors', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Overwrite',
                'help' => 'Override colors and surface for this locale.',
                'attr' => [
                    'data-vanssa-slider-settings-target' => 'overrideColors',
                    'data-action' => 'vanssa-slider-settings#refresh',
                ],
            ])
            ->add('overrideEffects', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Overwrite',
                'help' => 'Override animation and blur effects for this locale.',
                'attr' => [
                    'data-vanssa-slider-settings-target' => 'overrideEffects',
                    'data-action' => 'vanssa-slider-settings#refresh',
                ],
            ])
            ->add('overrideVisibility', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Overwrite',
                'help' => 'Override title/description/button visibility for this locale.',
                'attr' => [
                    'data-vanssa-slider-settings-target' => 'overrideVisibility',
                    'data-action' => 'vanssa-slider-settings#refresh',
                ],
            ])
            ->add('settings', SlideSettingsType::class, [
                'required' => false,
                'property_path' => 'slideSettings',
                'include_parallax' => false,
                'include_video' => false,
            ])
        ;

        // Unmapped children must be populated in POST_SET_DATA — the data
        // mapper resets them to their configured data right after PRE_SET_DATA.
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $translation = $event->getData();
            if (!$translation instanceof SlideTranslation) {
                return;
            }

            $form = $event->getForm();
            $form->get('addButton')->setData($translation->isButtonOverrideEnabled());
            $form->get('overrideMedia')->setData($translation->isMediaOverrideEnabled());
            $form->get('overrideLayout')->setData($translation->isLayoutOverrideEnabled());
            $form->get('overrideColors')->setData($translation->isColorsOverrideEnabled());
            $form->get('overrideEffects')->setData($translation->isEffectsOverrideEnabled());
            $form->get('overrideVisibility')->setData($translation->isVisibilityOverrideEnabled());

            foreach (self::VIDEO_SLOTS as $slot) {
                $stored = $translation->{$slot['getter']}();
                if (is_string($stored) && $this->videoProviderRegistry->isExternal($stored)) {
                    $form->get($slot['url'])->setData($stored);
                }
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $translation = $event->getData();
            if (!$translation instanceof SlideTranslation) {
                return;
            }

            $form = $event->getForm();
            $addButton = true === $form->get('addButton')->getData();
            $overrideMedia = true === $form->get('overrideMedia')->getData();
            $overrideLayout = true === $form->get('overrideLayout')->getData();
            $overrideColors = true === $form->get('overrideColors')->getData();
            $overrideEffects = true === $form->get('overrideEffects')->getData();
            $overrideVisibility = true === $form->get('overrideVisibility')->getData();

            if (!$addButton) {
                $translation->setButtonLabel(null);
                $translation->setUrl(null);
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

                    $translation->{$slot['setter']}($normalized);
                    $externalHandled[$fileField] = true;

                    continue;
                }

                $stored = $translation->{$slot['getter']}();
                if (is_string($stored) && $this->videoProviderRegistry->isExternal($stored)) {
                    $translation->{$slot['setter']}(null);
                }
            }

            foreach ([
                'slideCoverFile' => 'setSlideCover',
                'slideCoverMobileFile' => 'setSlideCoverMobile',
                'slideCoverTabletFile' => 'setSlideCoverTablet',
                'slideCoverVideoFile' => 'setSlideCoverVideo',
                'slideCoverVideoMobileFile' => 'setSlideCoverVideoMobile',
                'slideCoverVideoTabletFile' => 'setSlideCoverVideoTablet',
            ] as $field => $setter) {
                if (isset($externalHandled[$field])) {
                    continue;
                }

                $file = $form->get($field)->getData();
                if ($file instanceof UploadedFile) {
                    $translation->{$setter}($this->uploadedMediaStorage->store($file, 'slider/translation-cover'));
                }
            }

            $translation->setOverrides([
                'button' => $addButton,
                'media' => $overrideMedia,
                'layout' => $overrideLayout,
                'colors' => $overrideColors,
                'effects' => $overrideEffects,
                'visibility' => $overrideVisibility,
            ]);

            $responsive = $translation->getSlideSettings()['responsive'] ?? null;
            $desktop = is_array($responsive) && is_array($responsive['desktop'] ?? null) ? $responsive['desktop'] : [];
            $desktopTitle = $desktop['title'] ?? null;

            // A brand-new translation (locale with no persisted row yet) is
            // only attached to its slide by ResourceTranslationsType's SUBMIT
            // listener, which fires AFTER this child's POST_SUBMIT — reach for
            // the slide through the form tree in that case.
            $slide = $translation->getSlide() ?? $form->getParent()?->getParent()?->getData();
            $slideCode = $slide instanceof Slide ? $slide->getCode() : null;
            $translation->setName(is_string($desktopTitle) && '' !== trim($desktopTitle) ? $desktopTitle : $slideCode);
        });
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SlideTranslation::class,
            'label' => false,
            'allow_extra_fields' => true,
            'attr' => [
                'data-controller' => 'vanssa-slider-settings',
            ],
        ]);
    }
}
