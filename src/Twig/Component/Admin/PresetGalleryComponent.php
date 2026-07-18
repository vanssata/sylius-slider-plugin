<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Admin;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;
use Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider;

/**
 * Modal preset gallery shown on the slider/slide create pages: "start blank"
 * or pick a config/database style preset. Database slider presets that carry
 * source slides link to the create-from-preset route (server-side cloning);
 * everything else applies client-side via the preset-gallery controller.
 */
#[AsTwigComponent(name: 'vanssa_sylius_slider:admin:preset_gallery', template: '@VanssaSyliusSliderPlugin/admin/shared/preset_gallery/modal.html.twig')]
final class PresetGalleryComponent
{
    public function __construct(
        private readonly StylePresetProvider $stylePresetProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /** Either StylePreset::TYPE_SLIDER or StylePreset::TYPE_SLIDE. */
    public string $type = StylePreset::TYPE_SLIDE;

    /**
     * 'apply' (create pages): picking a preset fills the open form
     * client-side. 'choose' (grid index pages): the gallery opens BEFORE any
     * form exists, so every card links to the create page — presets carry
     * ?preset=<code>, which the create page's gallery auto-applies.
     */
    public string $mode = 'apply';

    /**
     * @return array<string, array{label: string, fields: array<string, bool|float|int|string>, source: string, mockup: string|null, slideIds: list<int>, createUrl: string|null}>
     */
    public function getPresets(): array
    {
        $presets = $this->type === StylePreset::TYPE_SLIDER
            ? $this->stylePresetProvider->sliderPresets()
            : $this->stylePresetProvider->slidePresets();

        $enriched = [];
        foreach ($presets as $code => $preset) {
            $preset['createUrl'] = null;
            if ($this->type === StylePreset::TYPE_SLIDER && $preset['source'] === 'database' && $preset['slideIds'] !== []) {
                $preset['createUrl'] = $this->urlGenerator->generate(
                    'vanssa_sylius_slider_admin_slider_create_from_preset',
                    ['presetCode' => $code],
                );
            } elseif ('choose' === $this->mode) {
                $preset['createUrl'] = $this->urlGenerator->generate($this->createRoute(), ['preset' => $code]);
            }

            $enriched[$code] = $preset;
        }

        return $enriched;
    }

    /** Where the "Blank" card leads in choose mode: the plain create page. */
    public function getBlankUrl(): string
    {
        return $this->urlGenerator->generate($this->createRoute());
    }

    private function createRoute(): string
    {
        return $this->type === StylePreset::TYPE_SLIDER
            ? 'vanssa_sylius_slider_admin_slider_create'
            : 'vanssa_sylius_slider_admin_slide_create';
    }

    public function getModalId(): string
    {
        return sprintf('vanssa-preset-gallery-%s', $this->type);
    }
}
