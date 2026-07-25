<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;

/**
 * Seeds a fashion-themed demo matching the sylius/test-application store: a
 * shared pool of slides (photos sourced from the Sylius core fixtures) and one
 * slider per configured slider style preset, so every preset can be seen live.
 */
final class SliderDemoFixture extends AbstractFixture
{
    /**
     * @param array<string, array<string, array{label?: string, settings?: array<string, mixed>}>> $stylePresets
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
        private readonly UploadedMediaStorage $uploadedMediaStorage,
        private readonly array $stylePresets,
    ) {
    }

    public function getName(): string
    {
        return 'vanssa_slider_demo';
    }

    public function load(array $options): void
    {
        $newCollection = $this->createOrUpdateSlide(
            'new-collection',
            'New Collection',
            'Fresh looks for the season — dresses, denim and everyday essentials.',
            1,
            null,
            'gradient_overlay',
            // Per-breakpoint layout overrides. Exactly one demo slide carries
            // them on purpose: per-breakpoint everything is the plugin's
            // headline feature, yet nothing in the demo data showed it, and it
            // is the surface the shop/responsive-overrides e2e spec asserts on
            // (the breakpoint <style> block used to never render at all).
            tabletOverrides: [
                'contentHorizontalPosition' => 'center',
                'contentTextAlign' => 'center',
            ],
            mobileOverrides: [
                'contentVerticalPosition' => 'center',
            ],
        );
        $summerDresses = $this->createOrUpdateSlide(
            'summer-dresses',
            'Summer Dresses',
            'Light fabrics and pastel prints, ready for the beach.',
            2,
            null,
            'clean_light',
        );
        $denimEssentials = $this->createOrUpdateSlide(
            'denim-essentials',
            'Denim Essentials',
            'Jeans and shorts that go with everything you own.',
            3,
            null,
            'split_left_light',
        );
        $graphicTees = $this->createOrUpdateSlide(
            'graphic-tees',
            'Graphic Tees',
            'Oversized tees in this month\'s colour drop.',
            4,
            null,
            'bold_center',
        );
        $streetCaps = $this->createOrUpdateSlide(
            'street-caps',
            'Caps & Beanies',
            'Knitted beanies and street caps for colder days.',
            5,
            null,
            'bottom_banner',
        );
        $seasonSale = $this->createOrUpdateSlide(
            'season-sale',
            'Season Sale',
            'Up to 50% off selected knitwear and accessories.',
            6,
            null,
            'glass_card',
        );
        $runwayVideo = $this->createOrUpdateSlide(
            'runway-video',
            'Backstage Reel',
            'Sample video slide for playback testing (Big Buck Bunny, CC BY 3.0, Blender Foundation).',
            1,
            'big-buck-bunny',
            'hero_dark',
        );

        $this->entityManager->flush();

        $sliders = [
            $this->createOrUpdateSlider('fashion-classic-arrows', 'Fashion Classic Arrows', 'classic_arrows', [
                $newCollection,
                $summerDresses,
                $denimEssentials,
                $graphicTees,
            ]),
            $this->createOrUpdateSlider('fashion-minimal-fade', 'Fashion Minimal Fade', 'minimal_fade', [
                $summerDresses,
                $seasonSale,
                $newCollection,
            ]),
            $this->createOrUpdateSlider('fashion-autoplay-showcase', 'Fashion Autoplay Showcase', 'autoplay_showcase', [
                $graphicTees,
                $streetCaps,
                $denimEssentials,
                $seasonSale,
            ]),
            $this->createOrUpdateSlider('fashion-fullscreen-hero', 'Fashion Fullscreen Hero', 'fullscreen_hero', [
                $newCollection,
                $runwayVideo,
                $summerDresses,
            ]),
            $this->createOrUpdateSlider('fashion-compact-banner', 'Fashion Compact Banner', 'compact_banner', [
                $seasonSale,
                $streetCaps,
                $graphicTees,
            ]),
            $this->createOrUpdateSlider('fashion-parallax-showcase', 'Fashion Parallax Showcase', 'parallax_showcase', [
                $denimEssentials,
                $newCollection,
                $graphicTees,
                $summerDresses,
            ]),
        ];

        foreach ($sliders as $slider) {
            $slider->setEnabled(true);
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<Slide> $slides
     */
    private function createOrUpdateSlider(string $code, string $name, string $stylePreset, array $slides): Slider
    {
        $slider = $this->sliderRepository->findOneBy(['code' => $code]);
        if (!$slider instanceof Slider) {
            $slider = new Slider();
            $slider->setCode($code);
            $this->entityManager->persist($slider);
        }

        $slider->setName($name);

        // Demo sliders never render a heading above the slides.
        $settings = array_merge($slider->getSettings(), [
            'showTitle' => false,
            'overlay' => false,
            'showNavigation' => true,
            'showArrows' => true,
            'slideEffect' => 'slide',
            'speed' => 500,
            'paginationShape' => 'square',
        ]);
        $slider->setSettings($this->applyStylePreset($settings, StylePreset::TYPE_SLIDER, $stylePreset));

        foreach ($slider->getSlides()->toArray() as $existing) {
            $slider->removeSlide($existing);
        }

        foreach ($slides as $slide) {
            $slider->addSlide($slide);
        }

        $slider->setSlideOrder(array_map(static fn (Slide $slide): int => (int) $slide->getId(), array_filter($slides, static fn (Slide $slide): bool => null !== $slide->getId())));

        $translation = $slider->getOrCreateTranslation('en_US');
        $translation->setName($name);

        return $slider;
    }

    /**
     * @param array<string, string> $tabletOverrides
     * @param array<string, string> $mobileOverrides
     */
    private function createOrUpdateSlide(
        string $code,
        string $title,
        string $description,
        int $imageSet = 1,
        ?string $video = null,
        ?string $stylePreset = null,
        array $tabletOverrides = [],
        array $mobileOverrides = [],
    ): Slide {
        $slide = $this->slideRepository->findOneBy(['code' => $code]);
        if (!$slide instanceof Slide) {
            $slide = new Slide();
            $slide->setCode($code);
            $this->entityManager->persist($slide);
        }

        $slide->setName($title);
        $slide->setEnabled(true);
        $slide->setSlideCover($this->uploadFixtureImage('desktop', $imageSet));
        $slide->setSlideCoverMobile($this->uploadFixtureImage('mobile', $imageSet));
        $slide->setSlideCoverVideo(null !== $video ? $this->uploadFixtureVideo($video) : null);

        $settings = array_merge($slide->getSlideSettings(), [
            'responsive' => [
                'desktop' => [
                    'headlineElement' => 'h3',
                    'contentHorizontalPosition' => 'start',
                    'contentVerticalPosition' => 'bottom',
                    'contentTextAlign' => 'left',
                    'contentAnimation' => 'fade-up',
                    'title' => $title,
                    'description' => $description,
                ],
                'tablet' => $tabletOverrides,
                'mobile' => $mobileOverrides,
            ],
        ]);
        if (null !== $stylePreset) {
            $settings = $this->applyStylePreset($settings, StylePreset::TYPE_SLIDE, $stylePreset);
        }
        $slide->setSlideSettings($settings);

        $slide->setContentSettings(array_merge($slide->getContentSettings(), [
            'slideCover' => ['alt' => $title, 'title' => $title],
        ]));

        $translation = $slide->getOrCreateTranslation('en_US');
        $translation->setName($title);

        return $slide;
    }

    /**
     * Expands a configured style preset's dot-path settings
     * ("settings.autoplay.enabled") into the given settings array, so demo
     * entities carry exactly what the one-click preset applier would fill in
     * the admin form.
     *
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function applyStylePreset(array $settings, string $type, string $code): array
    {
        $preset = $this->stylePresets[$type][$code] ?? [];
        $presetSettings = \is_array($preset['settings'] ?? null) ? $preset['settings'] : [];

        foreach ($presetSettings as $dotPath => $value) {
            if (!\is_scalar($value)) {
                continue;
            }

            $segments = explode('.', (string) $dotPath);
            if ('settings' === ($segments[0] ?? null)) {
                array_shift($segments);
            }
            if ([] === $segments) {
                continue;
            }

            $leaf = array_pop($segments);
            $cursor = &$settings;
            foreach ($segments as $segment) {
                if (!isset($cursor[$segment]) || !\is_array($cursor[$segment])) {
                    $cursor[$segment] = [];
                }
                $cursor = &$cursor[$segment];
            }
            $cursor[$leaf] = $value;
            unset($cursor);
        }

        return $settings;
    }

    private function uploadFixtureImage(string $device, int $set): ?string
    {
        $path = $this->resolveFixtureImagePath($device, $set);

        return $this->uploadFixtureFile($path, sprintf('fixtures/%s', $device));
    }

    private function uploadFixtureVideo(string $name): ?string
    {
        $path = $this->resolveFixtureVideoPath($name);

        return $this->uploadFixtureFile($path, 'fixtures/videos');
    }

    private function uploadFixtureFile(?string $path, string $subDir): ?string
    {
        if (null === $path || !is_file($path)) {
            return null;
        }

        // Work on a temporary copy: UploadedMediaStorage::store() moves the
        // file, which would otherwise delete the bundled asset from the plugin.
        $temporaryPath = tempnam(sys_get_temp_dir(), 'vanssa_slider_fixture_');
        if (false === $temporaryPath || !copy($path, $temporaryPath)) {
            return null;
        }

        $mimeType = mime_content_type($path) ?: null;
        $uploadedFile = new UploadedFile($temporaryPath, basename($path), $mimeType, null, true);

        return $this->uploadedMediaStorage->store($uploadedFile, $subDir);
    }

    private function resolveFixtureImagePath(string $device, int $set): ?string
    {
        $basePath = dirname(__DIR__, 2) . '/assets/fixtures/images';
        $extensions = ['webp', 'jpg', 'jpeg', 'png'];
        $candidates = [];

        foreach ($extensions as $extension) {
            $candidates[] = sprintf('%s/%s-%d.%s', $basePath, $device, $set, $extension);
        }

        if ('mobile' === $device) {
            foreach ($extensions as $extension) {
                $candidates[] = sprintf('%s/mobile%d.%s', $basePath, $set, $extension);
            }
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveFixtureVideoPath(string $name): ?string
    {
        $basePath = dirname(__DIR__, 2) . '/assets/fixtures/videos';

        foreach (['mp4', 'webm'] as $extension) {
            $candidate = sprintf('%s/%s.%s', $basePath, $name, $extension);
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
