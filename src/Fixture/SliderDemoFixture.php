<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

final class SliderDemoFixture extends AbstractFixture
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
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
                '/media/fixtures/automotive/dashboard-hud.svg',
                null,
            ),
            'predictive-service' => $this->createOrUpdateSlide(
                'predictive-service',
                'Predictive Service',
                'AI diagnostics helps prevent workshop downtime.',
                '/media/fixtures/automotive/service-bay.svg',
                null,
            ),
            'fleet-control' => $this->createOrUpdateSlide(
                'fleet-control',
                'Fleet Control',
                'Live route orchestration for mixed vehicle fleets.',
                '/media/fixtures/automotive/fleet-control.svg',
                null,
            ),
        ];

        $videoSlides = [
            'autonomous-loop' => $this->createOrUpdateSlide(
                'autonomous-loop',
                'Autonomous Drive Loop',
                'Synthetic video loop for ADAS showcase.',
                '/media/fixtures/automotive/diagnostics-ui.svg',
                '/media/fixtures/automotive/autonomous-loop.mp4',
            ),
            'charging-network' => $this->createOrUpdateSlide(
                'charging-network',
                'Charging Network',
                'Synthetic video loop for charging analytics.',
                '/media/fixtures/automotive/route-analytics.svg',
                '/media/fixtures/automotive/charging-network.mp4',
            ),
        ];

        $s1 = $this->createOrUpdateSlide('ev-cockpit', 'EV Cockpit', 'Driver-focused EV cockpit UX blocks.', '/media/fixtures/automotive/electric-sedan.svg');
        $s2 = $this->createOrUpdateSlide('battery-lab', 'Battery Lab', 'Battery analytics and thermal charts.', '/media/fixtures/automotive/battery-lab.svg');
        $s3 = $this->createOrUpdateSlide('assistant', 'In-Car Assistant', 'Voice assistant with contextual commands.', '/media/fixtures/automotive/ai-assistant.svg');

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
        ?string $cover = null,
        ?string $video = null,
    ): Slide {
        $slide = $this->slideRepository->findOneBy(['code' => $code]);
        if (!$slide instanceof Slide) {
            $slide = new Slide();
            $slide->setCode($code);
            $this->entityManager->persist($slide);
        }

        $slide->setName($title);
        $slide->setTitle($title);
        $slide->setDescription($description);
        $slide->setEnabled(true);
        $slide->setSlideCover($cover);
        $slide->setSlideCoverVideo($video);
        $slide->setSlideSettings(array_merge($slide->getSlideSettings(), [
            'headlineElement' => 'h3',
            'contentHorizontalPosition' => 'start',
            'contentVerticalPosition' => 'bottom',
            'contentTextAlign' => 'left',
            'contentAnimation' => 'fade-up',
        ]));
        $slide->setContentSettings(array_merge($slide->getContentSettings(), [
            'slideCover' => ['alt' => $title, 'title' => $title],
        ]));

        $translation = $slide->getOrCreateTranslation('en_US');
        $translation->setName($title);
        $translation->setTitle($title);
        $translation->setDescription($description);

        return $slide;
    }
}
