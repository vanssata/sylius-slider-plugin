<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Shop;

use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'vanssa_sylius_slider:shop:slide', template: '@VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/shop/slide.html.twig')]
final class SlideComponent
{
    public Slide $slide;

    public int $index = 0;

    public string $localeCode;

    public ?string $fallbackLocaleCode = null;

    /**
     * @var array<string, mixed>
     */
    public array $sliderSettings = [];
}
