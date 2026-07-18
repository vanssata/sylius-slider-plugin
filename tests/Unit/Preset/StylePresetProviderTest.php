<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Preset;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;
use Vanssa\SyliusSliderPlugin\Preset\MockupCatalog;
use Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider;
use Vanssa\SyliusSliderPlugin\Repository\StylePresetRepository;

final class StylePresetProviderTest extends TestCase
{
    public function testConvertsDotPathsToBracketFieldNames(): void
    {
        $provider = $this->createProvider([
            'slide' => [
                'demo' => [
                    'label' => 'Demo',
                    'settings' => [
                        'settings.responsive.desktop.textColor' => 'rgba(255, 255, 255, 1)',
                        'addButton' => true,
                    ],
                ],
            ],
            'slider' => [
                'auto' => [
                    'label' => 'Auto',
                    'settings' => [
                        'settings.autoplay.enabled' => true,
                        'settings.speed' => 500,
                    ],
                ],
            ],
        ]);

        $slidePresets = $provider->slidePresets();
        self::assertSame(['demo'], array_keys($slidePresets));
        self::assertSame('Demo', $slidePresets['demo']['label']);
        self::assertSame('config', $slidePresets['demo']['source']);
        self::assertSame([
            'slide[settings][responsive][desktop][textColor]' => 'rgba(255, 255, 255, 1)',
            'slide[addButton]' => true,
        ], $slidePresets['demo']['fields']);

        $sliderPresets = $provider->sliderPresets();
        self::assertSame([
            'slider[settings][autoplay][enabled]' => true,
            'slider[settings][speed]' => 500,
        ], $sliderPresets['auto']['fields']);
    }

    public function testSkipsNonScalarValuesAndMissingRoots(): void
    {
        $provider = $this->createProvider([
            'slide' => [
                'weird' => [
                    'label' => 'Weird',
                    'settings' => [
                        'settings.responsive.desktop.textColor' => ['not' => 'scalar'],
                        'settings.responsive.desktop.borderRadius' => 8,
                    ],
                ],
            ],
        ]);

        self::assertSame(
            ['slide[settings][responsive][desktop][borderRadius]' => 8],
            $provider->slidePresets()['weird']['fields'],
        );
        self::assertSame([], $provider->sliderPresets());
    }

    public function testDatabasePresetsAreMergedAfterConfigPresets(): void
    {
        $databasePreset = $this->createDatabasePreset('custom_hero', StylePreset::TYPE_SLIDE, 'Custom Hero', [
            'settings.responsive.desktop.textColor' => 'rgba(0, 0, 0, 1)',
        ]);
        $databasePreset->setMockupImage('/media/slider/preset-mockups/custom.png');

        $provider = $this->createProvider(
            ['slide' => ['config_one' => ['label' => 'Config One', 'settings' => ['settings.responsive.desktop.borderRadius' => 8]]]],
            [StylePreset::TYPE_SLIDE => [$databasePreset]],
        );

        $presets = $provider->slidePresets();

        self::assertSame(['config_one', 'custom_hero'], array_keys($presets));
        self::assertSame('config', $presets['config_one']['source']);
        self::assertSame('database', $presets['custom_hero']['source']);
        self::assertSame('/media/slider/preset-mockups/custom.png', $presets['custom_hero']['mockup']);
        self::assertSame(
            ['slide[settings][responsive][desktop][textColor]' => 'rgba(0, 0, 0, 1)'],
            $presets['custom_hero']['fields'],
        );
    }

    public function testDatabasePresetOverridesConfigPresetWithSameCode(): void
    {
        $databasePreset = $this->createDatabasePreset('shared_code', StylePreset::TYPE_SLIDE, 'From Database', [
            'settings.responsive.desktop.borderRadius' => 20,
        ]);

        $provider = $this->createProvider(
            ['slide' => ['shared_code' => ['label' => 'From Config', 'settings' => ['settings.responsive.desktop.borderRadius' => 8]]]],
            [StylePreset::TYPE_SLIDE => [$databasePreset]],
        );

        $presets = $provider->slidePresets();

        self::assertCount(1, $presets);
        self::assertSame('From Database', $presets['shared_code']['label']);
        self::assertSame('database', $presets['shared_code']['source']);
        self::assertSame(
            ['slide[settings][responsive][desktop][borderRadius]' => 20],
            $presets['shared_code']['fields'],
        );
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $configPresets
     * @param array<string, list<StylePreset>> $databasePresets
     */
    private function createProvider(array $configPresets, array $databasePresets = []): StylePresetProvider
    {
        $repository = $this->createStub(StylePresetRepository::class);
        $repository->method('findEnabledByType')->willReturnCallback(
            static fn (string $type): array => $databasePresets[$type] ?? [],
        );

        return new StylePresetProvider($configPresets, $repository, new MockupCatalog());
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createDatabasePreset(string $code, string $type, string $label, array $settings): StylePreset
    {
        $preset = new StylePreset();
        $preset->setCode($code);
        $preset->setType($type);
        $preset->setLabel($label);
        $preset->setSettings($settings);

        return $preset;
    }
}
