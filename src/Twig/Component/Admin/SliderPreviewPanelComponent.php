<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Admin;

use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider;

#[AsTwigComponent(name: 'vanssa_sylius_slider:admin:slider_preview_panel', template: '@VanssaSyliusSliderPlugin/admin/slider/preview_modal.html.twig')]
final class SliderPreviewPanelComponent
{
    /**
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.locale')]
        private readonly RepositoryInterface $localeRepository,
        private readonly StylePresetProvider $stylePresetProvider,
    ) {
    }

    public Slider $slider;

    /**
     * Inline mode renders the panel as a sticky card on the edit page
     * (next to the real form) instead of the grid modal shell.
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
        return $this->stylePresetProvider->sliderPresets();
    }
}
