<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Shop;

use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'vanssa_sylius_slider:shop:slider', template: '@VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/shop/slider.html.twig')]
final class SliderComponent
{
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public Slider $slider;

    public string $localeCode;

    public ?string $fallbackLocaleCode = null;

    /**
     * @return array<int, Slide>
     */
    public function getEnabledSlides(): array
    {
        $channelCode = $this->channelContext->getChannel()->getCode();

        return array_values(array_filter(
            $this->slider->getOrderedSlides(),
            static fn (Slide $slide): bool => $slide->isEnabled() && $slide->isAvailableForChannel($channelCode),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->slider->getLocalizedSettings($this->localeCode, $this->fallbackLocaleCode);
    }
}
