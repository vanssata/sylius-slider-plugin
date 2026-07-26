<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class VanssaSyliusSliderExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('vanssa_sylius_slider.presets', $config['presets']);
        $container->setParameter('vanssa_sylius_slider.style_presets', $config['style_presets']);
        $container->setParameter('vanssa_sylius_slider.preview.shop_entrypoints', $config['preview']['shop_entrypoints']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Custom grid action types are resolved by name from this global
        // map (sylius.grid.templates.action), not from the per-action
        // `options.template` key in grids/admin/*.yaml (that key only
        // reaches the template as a Twig variable, it doesn't select it).
        // Slide and slider each need their own type name since the map has
        // no per-grid scoping — "preview" alone would collide.
        $container->prependExtensionConfig('sylius_grid', [
            'templates' => [
                'action' => [
                    'slide_edit_modal' => '@VanssaSyliusSliderPlugin/admin/slide/grid/action/edit_modal.html.twig',
                    'slider_preview' => '@VanssaSyliusSliderPlugin/admin/slider/grid/action/preview.html.twig',
                    'slide_preset_create' => '@VanssaSyliusSliderPlugin/admin/slide/grid/action/preset_create.html.twig',
                ],
            ],
        ]);

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'VanssaSyliusSliderPlugin' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => \dirname(__DIR__) . '/Entity',
                        'prefix' => 'Vanssa\\SyliusSliderPlugin\\Entity',
                        'alias' => 'VanssaSyliusSliderPlugin',
                    ],
                ],
            ],
        ]);

        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'VanssaSyliusSliderPluginMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@VanssaSyliusSliderPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
