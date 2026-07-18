<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Admin;

use Twig\Attribute\AsTwigFunction;
use Vanssa\SyliusSliderPlugin\Preset\MockupCatalog;

final readonly class MockupCatalogExtension
{
    public function __construct(
        private MockupCatalog $mockupCatalog,
    ) {
    }

    /**
     * @return list<array{key: string, label: string, path: string}>
     */
    #[AsTwigFunction('vanssa_slider_preset_mockups')]
    public function getMockups(): array
    {
        return $this->mockupCatalog->all();
    }
}
