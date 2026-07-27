<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

#[AsLiveComponent(name: 'vanssa_sylius_slider:admin:slider_slides_preview', template: '@VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/admin/slider_slides_preview.html.twig')]
final class SliderSlidesPreviewComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $sliderId;

    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChannelContextInterface $channelContext,
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {
    }

    /**
     * Fallback locale for the preview texts. The admin host does not have
     * to match any channel hostname, so the request-based channel context
     * may fail — fall back to the slider's own channels, then to any
     * enabled channel. A tier that resolves a channel without a default
     * locale falls through to the next one.
     */
    public function getFallbackLocaleCode(): ?string
    {
        try {
            $channel = $this->channelContext->getChannel();
            if ($channel instanceof ChannelInterface) {
                $localeCode = $channel->getDefaultLocale()?->getCode();
                if (null !== $localeCode) {
                    return $localeCode;
                }
            }
        } catch (ChannelNotFoundException) {
        }

        foreach ($this->getSlider()?->getChannelCodes() ?? [] as $channelCode) {
            $channel = $this->channelRepository->findOneByCode($channelCode);
            if ($channel instanceof ChannelInterface) {
                $localeCode = $channel->getDefaultLocale()?->getCode();
                if (null !== $localeCode) {
                    return $localeCode;
                }
            }
        }

        foreach ($this->channelRepository->findEnabled() as $channel) {
            if ($channel instanceof ChannelInterface) {
                $localeCode = $channel->getDefaultLocale()?->getCode();
                if (null !== $localeCode) {
                    return $localeCode;
                }
            }
        }

        return null;
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

    /**
     * Re-renders the list when the "Add slides" browser attaches/detaches a
     * slide (nothing to do — rendering re-reads the association).
     */
    #[LiveListener('vanssa:slider-slides-changed')]
    public function onSlidesChanged(): void
    {
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
