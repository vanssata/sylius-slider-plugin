<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;

final class SliderDemoFixture extends AbstractFixture
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
        private readonly UploadedMediaStorage $uploadedMediaStorage,
    ) {
    }

    public function getName(): string
    {
        return 'vanssa_slider_demo';
    }

    public function load(array $options): void
    {
        $shared = [
            'platform-overview' => $this->createOrUpdateSlide(
                'platform-overview',
                'Platform Overview',
                'Unified cockpit software for EV and connected fleets.',
                1,
                null,
            ),
            'predictive-service' => $this->createOrUpdateSlide(
                'predictive-service',
                'Predictive Service',
                'AI diagnostics helps prevent workshop downtime.',
                2,
                null,
            ),
            'fleet-control' => $this->createOrUpdateSlide(
                'fleet-control',
                'Fleet Control',
                'Live route orchestration for mixed vehicle fleets.',
                3,
                null,
            ),
        ];

        $videoSlides = [
            'autonomous-loop' => $this->createOrUpdateSlide(
                'autonomous-loop',
                'Autonomous Drive Loop',
                'Synthetic video loop for ADAS showcase.',
                1,
                '/media/fixtures/automotive/autonomous-loop.mp4',
            ),
            'charging-network' => $this->createOrUpdateSlide(
                'charging-network',
                'Charging Network',
                'Synthetic video loop for charging analytics.',
                2,
                '/media/fixtures/automotive/charging-network.mp4',
            ),
        ];

        $s1 = $this->createOrUpdateSlide('ev-cockpit', 'EV Cockpit', 'Driver-focused EV cockpit UX blocks.', 3);
        $s2 = $this->createOrUpdateSlide('battery-lab', 'Battery Lab', 'Battery analytics and thermal charts.', 1);
        $s3 = $this->createOrUpdateSlide('assistant', 'In-Car Assistant', 'Voice assistant with contextual commands.', 2);

        $this->entityManager->flush();

        $homepage = $this->createOrUpdateSlider('homepage-main', 'Homepage Main Slider', [
            $shared['platform-overview'],
            $s1,
            $videoSlides['autonomous-loop'],
            $shared['predictive-service'],
        ]);

        $fleet = $this->createOrUpdateSlider('fleet-suite', 'Fleet Suite Slider', [
            $shared['fleet-control'],
            $shared['platform-overview'],
            $videoSlides['charging-network'],
            $s2,
        ]);

        $service = $this->createOrUpdateSlider('service-ops', 'Service Operations Slider', [
            $shared['predictive-service'],
            $s3,
            $shared['fleet-control'],
        ]);

        $homepage->setEnabled(true);
        $fleet->setEnabled(true);
        $service->setEnabled(true);

        $this->entityManager->flush();
    }

    private function createOrUpdateSlider(string $code, string $name, array $slides): Slider
    {
        $slider = $this->sliderRepository->findOneBy(['code' => $code]);
        if (!$slider instanceof Slider) {
            $slider = new Slider();
            $slider->setCode($code);
            $this->entityManager->persist($slider);
        }

        $slider->setName($name);
        $slider->setSettings(array_merge($slider->getSettings(), [
            'showTitle' => true,
            'overlay' => false,
            'showNavigation' => true,
            'showArrows' => true,
            'slideEffect' => 'slide',
            'speed' => 500,
            'paginationShape' => 'square',
        ]));

        foreach ($slider->getSlides()->toArray() as $existing) {
            $slider->removeSlide($existing);
        }

        foreach ($slides as $slide) {
            $slider->addSlide($slide);
        }

        $slider->setSlideOrder(array_map(static fn (Slide $slide): int => (int) $slide->getId(), array_filter($slides, static fn (Slide $slide): bool => null !== $slide->getId())));

        $translation = $slider->getOrCreateTranslation('en_US');
        $translation->setName(ucwords(str_replace('-', ' ', $code)));

        return $slider;
    }

    private function createOrUpdateSlide(
        string $code,
        string $title,
        string $description,
        int $imageSet = 1,
        ?string $video = null,
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
        $slide->setSlideCoverVideo($video);
        $slide->setSlideSettings(array_merge($slide->getSlideSettings(), [
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
                'tablet' => [],
                'mobile' => [],
            ],
        ]));
        $slide->setContentSettings(array_merge($slide->getContentSettings(), [
            'slideCover' => ['alt' => $title, 'title' => $title],
        ]));

        $translation = $slide->getOrCreateTranslation('en_US');
        $translation->setName($title);

        return $slide;
    }

    private function uploadFixtureImage(string $device, int $set): ?string
    {
        $path = $this->resolveFixtureImagePath($device, $set);
        if (null === $path || !is_file($path)) {
            return null;
        }

        $mimeType = mime_content_type($path) ?: null;
        $uploadedFile = new UploadedFile($path, basename($path), $mimeType, null, true);

        return $this->uploadedMediaStorage->store($uploadedFile, sprintf('fixtures/%s', $device));
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
}
