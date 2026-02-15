<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vanssa_sylius_slider');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
