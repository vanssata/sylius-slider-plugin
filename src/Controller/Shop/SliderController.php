<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Controller\Shop;

use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SliderController extends AbstractController
{
    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
        private readonly LocaleContextInterface $localeContext,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    #[Route('/slider/{code}', name: 'vanssa_sylius_slider_shop_slider_show', methods: ['GET'])]
    public function showSliderAction(string $code): Response
    {
        $localeCode = $this->localeContext->getLocaleCode();
        $channel = $this->channelContext->getChannel();
        $fallbackLocaleCode = $channel->getDefaultLocale()?->getCode();

        $slider = $this->sliderRepository->findEnabledOneByCodeForChannel(
            $code,
            $channel->getCode(),
            $localeCode,
            $fallbackLocaleCode,
        );

        if (null === $slider) {
            throw $this->createNotFoundException(sprintf('Slider "%s" does not exist or is disabled.', $code));
        }

        return $this->render('@VanssaSyliusSliderPlugin/shop/slider/show.html.twig', [
            'slider' => $slider,
            'localeCode' => $localeCode,
            'fallbackLocaleCode' => $fallbackLocaleCode,
        ]);
    }

    #[Route('/banner/{code}', name: 'vanssa_sylius_slider_shop_banner_show', methods: ['GET'])]
    public function showBannerAction(string $code): Response
    {
        $slide = $this->slideRepository->findEnabledOneByCode($code);

        if (null === $slide) {
            throw $this->createNotFoundException(sprintf('Slide "%s" does not exist or is disabled.', $code));
        }

        $localeCode = $this->localeContext->getLocaleCode();
        $channel = $this->channelContext->getChannel();
        $fallbackLocaleCode = $channel->getDefaultLocale()?->getCode();

        if (count($slide->getSliders()) > 0) {
            $isAvailableInAnySlider = false;
            foreach ($slide->getSliders() as $slider) {
                if ($slider->isAvailableForChannel($channel->getCode(), $localeCode, $fallbackLocaleCode)) {
                    $isAvailableInAnySlider = true;
                    break;
                }
            }

            if (!$isAvailableInAnySlider) {
                throw $this->createNotFoundException(sprintf('Slide "%s" is not available for channel "%s".', $code, $channel->getCode()));
            }
        }

        return $this->render('@VanssaSyliusSliderPlugin/shop/slider/banner.html.twig', [
            'slide' => $slide,
            'localeCode' => $localeCode,
            'fallbackLocaleCode' => $fallbackLocaleCode,
        ]);
    }
}
