<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Shop;

use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'vanssa_sylius_slider:shop:homepage_slider', template: '@VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/shop/homepage_slider.html.twig')]
final class HomepageSliderComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $code = 'homepage-main';

    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly LocaleContextInterface $localeContext,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function getSlider(): ?Slider
    {
        $channel = $this->channelContext->getChannel();
        $localeCode = $this->localeContext->getLocaleCode();
        $fallbackLocaleCode = $channel->getDefaultLocale()?->getCode();

        return $this->sliderRepository->findEnabledOneByCodeForChannel(
            $this->code,
            $channel->getCode(),
            $localeCode,
            $fallbackLocaleCode,
        );
    }
}
