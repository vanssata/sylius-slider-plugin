<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Factory;

use Sylius\Resource\Factory\FactoryInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

/**
 * @implements FactoryInterface<Slide>
 */
final readonly class SlideFactory implements FactoryInterface
{
    /**
     * @param class-string<Slide> $className
     */
    public function __construct(private string $className)
    {
    }

    public function createNew(): object
    {
        $className = $this->className;

        return new $className();
    }

    public function createForSlider(Slider $slider): Slide
    {
        /** @var Slide $slide */
        $slide = $this->createNew();
        $slide->addSlider($slider);

        return $slide;
    }
}
