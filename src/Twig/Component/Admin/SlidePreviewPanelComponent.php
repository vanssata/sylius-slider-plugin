<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Admin;

use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider;

#[AsTwigComponent(name: 'vanssa_sylius_slider:admin:slide_preview_panel', template: '@VanssaSyliusSliderPlugin/admin/slide/preview_modal.html.twig')]
final class SlidePreviewPanelComponent
{
    /**
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.locale')]
        private readonly RepositoryInterface $localeRepository,
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly StylePresetProvider $stylePresetProvider,
    ) {
    }

    public Slide $slide;

    /**
     * Inline mode renders the panel as a sticky card on the edit page
     * (next to the real form) instead of the grid/list modal shell.
     */
    public bool $inline = false;

    /**
     * @return array<int, LocaleInterface>
     */
    public function getLocales(): array
    {
        /** @var array<int, LocaleInterface> $locales */
        $locales = $this->localeRepository->findAll();

        return $locales;
    }

    /**
     * @return array<string, array{label: string, fields: array<string, bool|float|int|string>}>
     */
    public function getStylePresets(): array
    {
        return $this->stylePresetProvider->slidePresets();
    }

    /**
     * The channels this slide's preview may be viewed under: its own
     * channelCodes restriction, falling back to the parent slider's, falling
     * back to every enabled channel — each intersected with enabled channels
     * so a preview never resolves to a disabled one.
     *
     * @return array<int, BaseChannelInterface>
     */
    public function getChannels(): array
    {
        $enabled = [];
        foreach ($this->channelRepository->findEnabled() as $channel) {
            $enabled[] = $channel;
        }

        $codes = $this->slide->getChannelCodes();
        if ([] === $codes) {
            $codes = $this->slide->getSlider()?->getChannelCodes() ?? [];
        }

        if ([] === $codes) {
            return $enabled;
        }

        return array_values(array_filter(
            $enabled,
            static fn (BaseChannelInterface $channel): bool => in_array($channel->getCode(), $codes, true),
        ));
    }
}
