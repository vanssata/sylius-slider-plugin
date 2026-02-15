<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Menu;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Vanssa\SyliusSliderPlugin\Menu\AdminMenuListener;

final class AdminMenuListenerTest extends TestCase
{
    public function testItAddsSliderSectionWithSliderAndSlideItemsToConfigurationMenu(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');

        $event = new MenuBuilderEvent($factory, $menu);

        $listener = new AdminMenuListener();
        $listener->addAdminMenuItems($event);

        $section = $menu->getChild('sliders');
        self::assertNotNull($section);
        self::assertNotNull($section->getChild('vanssa_sylius_slider_sliders'));
        self::assertNotNull($section->getChild('vanssa_sylius_slider_slides'));
        self::assertSame('vanssa.sylius.slider.admin.menu.sliders', $section->getLabel());
        self::assertSame('vanssa_sylius_slider.ui.sliders', $section->getChild('vanssa_sylius_slider_sliders')?->getLabel());
        self::assertSame('vanssa_sylius_slider.ui.slides', $section->getChild('vanssa_sylius_slider_slides')?->getLabel());
    }
}
