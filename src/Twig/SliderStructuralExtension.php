<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig;

use Twig\Attribute\AsTwigFunction;
use Vanssa\SyliusSliderPlugin\Renderer\SliderStructuralSettings;

final readonly class SliderStructuralExtension
{
    public function __construct(
        private SliderStructuralSettings $sliderStructuralSettings,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array{desktop: array<string, mixed>, tablet: array<string, mixed>, mobile: array<string, mixed>}
     */
    #[AsTwigFunction('vanssa_slider_structural_maps')]
    public function maps(array $settings): array
    {
        return $this->sliderStructuralSettings->maps($settings);
    }
}
