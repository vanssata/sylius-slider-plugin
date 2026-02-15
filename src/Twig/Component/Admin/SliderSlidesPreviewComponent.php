<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'vanssa_sylius_slider:admin:slider_slides_preview', template: '@VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/admin/slider_slides_preview.html.twig')]
final class SliderSlidesPreviewComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $sliderId;

    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getSlider(): ?Slider
    {
        $slider = $this->sliderRepository->find($this->sliderId);

        return $slider instanceof Slider ? $slider : null;
    }

    /**
     * @return array<int, Slide>
     */
    public function getSlides(): array
    {
        $slider = $this->getSlider();
        if (null === $slider) {
            return [];
        }

        return $slider->getOrderedSlides();
    }

    #[LiveAction]
    public function removeSlide(#[LiveArg] int $slideId): void
    {
        $slider = $this->getSlider();
        if (null === $slider) {
            return;
        }

        $slide = $this->slideRepository->find($slideId);
        if (!$slide instanceof Slide) {
            return;
        }

        $slider->removeSlide($slide);
        $this->entityManager->flush();
    }

    #[LiveAction]
    public function reorderSlides(#[LiveArg] string $orderedSlideIds): void
    {
        $slider = $this->getSlider();
        if (null === $slider) {
            return;
        }

        $existingSlideIds = [];
        foreach ($slider->getSlides() as $slide) {
            $slideId = $slide->getId();
            if (null !== $slideId) {
                $existingSlideIds[] = $slideId;
            }
        }

        $requestedOrder = $this->normalizeSlideIds($orderedSlideIds);
        $requestedOrder = array_values(array_filter(
            $requestedOrder,
            static fn (int $slideId): bool => in_array($slideId, $existingSlideIds, true),
        ));

        foreach ($existingSlideIds as $existingSlideId) {
            if (!in_array($existingSlideId, $requestedOrder, true)) {
                $requestedOrder[] = $existingSlideId;
            }
        }

        $slider->setSlideOrder($requestedOrder);
        $this->entityManager->flush();
    }

    /**
     * @return array<int, int>
     */
    private function normalizeSlideIds(string $orderedSlideIds): array
    {
        $normalized = [];
        foreach (explode(',', $orderedSlideIds) as $part) {
            $id = (int) trim($part);
            if ($id <= 0 || in_array($id, $normalized, true)) {
                continue;
            }

            $normalized[] = $id;
        }

        return $normalized;
    }
}
