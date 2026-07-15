<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Admin;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

#[AsTwigComponent(name: 'vanssa_sylius_slider:admin:slider_preview_panel', template: '@VanssaSyliusSliderPlugin/admin/slider/preview_panel.html.twig')]
final class SliderPreviewPanelComponent
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
        #[Autowire(service: 'sylius.repository.locale')]
        private readonly RepositoryInterface $localeRepository,
    ) {
    }

    public Slider $slider;

    /**
     * @return array<int, ChannelInterface>
     */
    public function getChannels(): array
    {
        /** @var array<int, ChannelInterface> $channels */
        $channels = $this->channelRepository->findBy(['enabled' => true]);

        return $channels;
    }

    /**
     * @return array<int, LocaleInterface>
     */
    public function getLocales(): array
    {
        /** @var array<int, LocaleInterface> $locales */
        $locales = $this->localeRepository->findAll();

        return $locales;
    }
}
