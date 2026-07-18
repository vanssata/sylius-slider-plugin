<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Vanssa\SyliusSliderPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    public function testShipsDefaultStylePresets(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertArrayHasKey('style_presets', $config);
        self::assertSame(
            ['hero_dark', 'clean_light', 'minimal', 'bold_center', 'split_left_light', 'gradient_overlay', 'glass_card', 'bottom_banner', 'promo_badge_right'],
            array_keys($config['style_presets']['slide']),
        );
        self::assertSame(
            ['classic_arrows', 'minimal_fade', 'autoplay_showcase', 'fullscreen_hero', 'compact_banner', 'parallax_showcase'],
            array_keys($config['style_presets']['slider']),
        );

        foreach (['slide', 'slider'] as $root) {
            foreach ($config['style_presets'][$root] as $preset) {
                self::assertNotSame('', $preset['label']);
                self::assertIsArray($preset['settings']);
                self::assertNotSame([], $preset['settings']);
            }
        }

        self::assertSame(
            'rgba(15, 23, 42, 0.75)',
            $config['style_presets']['slide']['hero_dark']['settings']['settings.responsive.desktop.backgroundColor'],
        );
        self::assertTrue($config['style_presets']['slider']['autoplay_showcase']['settings']['settings.autoplay.enabled']);
    }

    public function testProjectCanAddItsOwnStylePreset(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [
            [
                'style_presets' => [
                    'slide' => [
                        'brand_hero' => [
                            'label' => 'Brand Hero',
                            'settings' => [
                                'settings.responsive.desktop.headlineColor' => 'rgba(250, 204, 21, 1)',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertArrayHasKey('brand_hero', $config['style_presets']['slide']);
        self::assertSame('Brand Hero', $config['style_presets']['slide']['brand_hero']['label']);
    }
}
