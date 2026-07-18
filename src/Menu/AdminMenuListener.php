<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AdminMenuListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.main' => 'addAdminMenuItems',
        ];
    }

    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $configuration = $event->getMenu()->addChild('sliders');
        $configuration
            ->setLabel('vanssa.sylius.slider.admin.menu.sliders')
            ->setLabelAttribute('icon', 'tabler:adjustments-horizontal');
        if (null === $configuration) {
            return;
        }

        $configuration
            ->addChild('vanssa_sylius_slider_sliders', ['route' => 'vanssa_sylius_slider_admin_slider_index', 'extras' => ['routes' => [
                ['route' => 'vanssa_sylius_slider_admin_slider_create'],
                ['route' => 'vanssa_sylius_slider_admin_slider_update'],
            ]]])
            ->setLabel('vanssa_sylius_slider.ui.sliders')
            ->setLabelAttribute('icon', 'bi:sliders')
        ;

        $configuration
            ->addChild('vanssa_sylius_slider_slides', ['route' => 'vanssa_sylius_slider_admin_slide_index', 'extras' => ['routes' => [
                ['route' => 'vanssa_sylius_slider_admin_slide_create'],
                ['route' => 'vanssa_sylius_slider_admin_slide_update'],
            ]]])
            ->setLabel('vanssa_sylius_slider.ui.slides')
            ->setLabelAttribute('icon', 'bi:images')
        ;

        $configuration
            ->addChild('vanssa_sylius_slider_slider_presets', [
                'route' => 'vanssa_sylius_slider_admin_style_preset_index',
                'routeParameters' => ['criteria' => ['type' => 'slider']],
            ])
            ->setLabel('vanssa_sylius_slider.ui.slider_presets')
            ->setLabelAttribute('icon', 'tabler:wand')
        ;

        $configuration
            ->addChild('vanssa_sylius_slider_slide_presets', [
                'route' => 'vanssa_sylius_slider_admin_style_preset_index',
                'routeParameters' => ['criteria' => ['type' => 'slide']],
                'extras' => ['routes' => [
                    ['route' => 'vanssa_sylius_slider_admin_style_preset_create'],
                    ['route' => 'vanssa_sylius_slider_admin_style_preset_update'],
                ]],
            ])
            ->setLabel('vanssa_sylius_slider.ui.slide_presets')
            ->setLabelAttribute('icon', 'tabler:wand')
        ;
    }
}
