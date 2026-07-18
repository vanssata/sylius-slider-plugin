<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Factory;

use Sylius\Resource\Factory\FactoryInterface;
use Vanssa\SyliusSliderPlugin\Cloner\SlideCloner;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;
use Vanssa\SyliusSliderPlugin\Preset\DotPathApplier;

/**
 * @implements FactoryInterface<Slider>
 *
 * Like SlideFactory, the resource layer constructs this with only the model
 * class name, so the (dependency-free) helpers are instantiated inline.
 */
final readonly class SliderFactory implements FactoryInterface
{
    /**
     * @param class-string<Slider> $className
     */
    public function __construct(private string $className)
    {
    }

    public function createNew(): object
    {
        $className = $this->className;

        return new $className();
    }

    /**
     * New slider pre-configured from a slider-type style preset: the preset's
     * dot-path settings become the slider settings, and each source slide is
     * cloned (independent copies — editing them never touches the preset).
     */
    public function createFromStylePreset(StylePreset $preset): Slider
    {
        /** @var Slider $slider */
        $slider = $this->createNew();

        $slider->setName($preset->getLabel());
        $settings = (new DotPathApplier())->apply($slider->getSettings(), $preset->getSettings(), 'settings');
        $slider->setSettings($settings);

        $cloner = new SlideCloner();
        foreach ($preset->getSlides() as $sourceSlide) {
            $clone = $cloner->clone($sourceSlide);
            $clone->addSlider($slider);
        }

        return $slider;
    }
}
