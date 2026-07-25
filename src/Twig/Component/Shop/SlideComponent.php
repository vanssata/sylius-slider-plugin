<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Shop;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

#[AsTwigComponent(name: 'vanssa_sylius_slider:shop:slide', template: '@VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/shop/slide.html.twig')]
final class SlideComponent
{
    public Slide $slide;

    public int $index = 0;

    public string $localeCode;

    public ?string $fallbackLocaleCode = null;

    /** @var array<string, mixed> */
    public array $sliderSettings = [];

    public bool $lazyLoad = false;

    /**
     * Admin previews only: renders the slide as ONE breakpoint would look,
     * because the preview frame shares the admin page's viewport and no media
     * query can select the tablet/mobile variants there. Null on the
     * storefront, where the real viewport does the selecting.
     */
    public ?string $previewBreakpoint = null;
}
