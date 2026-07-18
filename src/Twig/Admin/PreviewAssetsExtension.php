<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Admin;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

final readonly class PreviewAssetsExtension
{
    /**
     * @param array<int, string> $shopEntrypoints
     */
    public function __construct(
        #[Autowire(param: 'vanssa_sylius_slider.preview.shop_entrypoints')]
        private array $shopEntrypoints,
    ) {
    }

    /**
     * @return array<int, string>
     */
    #[AsTwigFunction('vanssa_slider_preview_shop_entrypoints')]
    public function getShopEntrypoints(): array
    {
        return $this->shopEntrypoints;
    }
}
