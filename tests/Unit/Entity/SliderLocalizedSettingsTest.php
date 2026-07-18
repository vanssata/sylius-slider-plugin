<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Entity\SliderTranslation;
use Vanssa\SyliusSliderPlugin\Renderer\SliderStructuralSettings;

final class SliderLocalizedSettingsTest extends TestCase
{
    public function testLocaleBaseOverridesOverlayTopLevelSettings(): void
    {
        $slider = $this->createSlider([
            'showArrows' => true,
            'navigationIcon' => 'chevron',
            'paginationStyle' => 'dots',
        ]);
        $this->addTranslation($slider, 'fr_FR', [
            'base' => ['navigationIcon' => 'angle', 'paginationStyle' => '', 'showArrows' => null],
        ]);

        $localized = $slider->getLocalizedSettings('fr_FR');

        self::assertSame('angle', $localized['navigationIcon']);
        self::assertSame('dots', $localized['paginationStyle'], 'Empty override values must inherit.');
        self::assertTrue($localized['showArrows'], 'Null override values must inherit.');

        $untouched = $slider->getLocalizedSettings('en_US');
        self::assertSame('chevron', $untouched['navigationIcon'], 'Other locales keep the base settings.');
    }

    public function testLocaleResponsiveOverridesOverlayBreakpointVariants(): void
    {
        $slider = $this->createSlider([
            'showArrows' => true,
            'responsive' => [
                'tablet' => ['navigationSize' => '3rem'],
            ],
        ]);
        $this->addTranslation($slider, 'fr_FR', [
            'responsive' => [
                'tablet' => ['navigationIcon' => 'chevron', 'showNavigation' => '0'],
            ],
        ]);

        $localized = $slider->getLocalizedSettings('fr_FR');

        self::assertSame('3rem', $localized['responsive']['tablet']['navigationSize'], 'Base breakpoint values survive.');
        self::assertSame('chevron', $localized['responsive']['tablet']['navigationIcon']);
        self::assertSame('0', $localized['responsive']['tablet']['showNavigation']);
    }

    public function testStructuralMapsCascadeDesktopTabletMobile(): void
    {
        $maps = (new SliderStructuralSettings())->maps([
            'showArrows' => true,
            'navigationIcon' => 'chevron',
            'paginationStyle' => 'dots',
            'responsive' => [
                'tablet' => ['navigationIcon' => 'angle', 'showNavigation' => '0'],
                'mobile' => ['paginationStyle' => 'lines'],
            ],
        ]);

        self::assertSame('chevron', $maps['desktop']['navigationIcon']);
        self::assertTrue($maps['desktop']['showNavigation']);

        self::assertSame('angle', $maps['tablet']['navigationIcon']);
        self::assertFalse($maps['tablet']['showNavigation']);

        // Mobile cascades from tablet…
        self::assertSame('angle', $maps['mobile']['navigationIcon']);
        self::assertFalse($maps['mobile']['showNavigation']);
        // …and applies its own override.
        self::assertSame('lines', $maps['mobile']['paginationStyle']);
    }

    public function testStructuralMapsResolveSizeAndShadowPresets(): void
    {
        $maps = (new SliderStructuralSettings())->maps([
            'navigationSize' => 'sm',
            'navigationShadow' => 'soft',
            'responsive' => ['tablet' => ['navigationSize' => '4rem', 'paginationShadow' => 'glow']],
        ]);

        self::assertSame('1rem', $maps['desktop']['navigationSize']);
        self::assertStringContainsString('rgba(15, 23, 42, 0.25)', $maps['desktop']['navigationShadow']);
        self::assertSame('4rem', $maps['tablet']['navigationSize']);
        self::assertStringContainsString('rgba(250, 204, 21, 0.55)', $maps['tablet']['paginationShadow']);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createSlider(array $settings): Slider
    {
        $slider = new Slider();
        $slider->setCode('localized-settings');
        $slider->setName('Localized');
        $slider->setSettings($settings);

        return $slider;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function addTranslation(Slider $slider, string $locale, array $overrides): void
    {
        $translation = new SliderTranslation();
        $translation->setLocaleCode($locale);
        $translation->setSettings($overrides);
        $translation->setSlider($slider);
        $slider->addTranslation($translation);
    }
}
