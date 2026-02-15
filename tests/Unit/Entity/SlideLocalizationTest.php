<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

final class SlideLocalizationTest extends TestCase
{
    public function testItFallsBackToBaseFieldsWhenTranslationIsMissing(): void
    {
        $slide = new Slide();
        $slide->setName('Base name');
        $slide->setTitle('Base title');
        $slide->setDescription('Base description');

        self::assertSame('Base name', $slide->getLocalizedName('en_US', 'en_US'));
        self::assertSame('Base title', $slide->getLocalizedTitle('en_US', 'en_US'));
        self::assertSame('Base description', $slide->getLocalizedDescription('en_US', 'en_US'));
    }

    public function testItUsesLocaleSpecificTranslationWhenAvailable(): void
    {
        $slide = new Slide();
        $slide->setName('Base name');
        $slide->setTitle('Base title');

        $translation = $slide->getOrCreateTranslation('bg_BG');
        $translation->setName('BG name');
        $translation->setTitle('BG title');

        self::assertSame('BG name', $slide->getLocalizedName('bg_BG', 'en_US'));
        self::assertSame('BG title', $slide->getLocalizedTitle('bg_BG', 'en_US'));
    }

    public function testItMergesLocalizedSlideSettingsWithBaseSettings(): void
    {
        $slide = new Slide();
        $slide->setSlideSettings([
            'headlineElement' => 'h2',
            'contentHorizontalPosition' => 'end',
            'linking' => [
                'type' => 'custom',
                'buttonSize' => 'md',
            ],
        ]);

        $translation = $slide->getOrCreateTranslation('de_DE');
        $translation->setSlideSettings([
            'enabled' => true,
            'linking' => [
                'type' => 'product',
            ],
        ]);

        $localized = $slide->getLocalizedSlideSettings('de_DE', 'en_US');

        self::assertSame('h2', $localized['headlineElement']);
        self::assertSame('end', $localized['contentHorizontalPosition']);
        self::assertSame('product', $localized['linking']['type']);
        self::assertSame('md', $localized['linking']['buttonSize']);
    }

    public function testItFallsBackToFallbackLocaleSettingsWhenCurrentLocaleHasEnabledButEmptyOverrides(): void
    {
        $slide = new Slide();
        $slide->setSlideSettings([
            'headlineElement' => 'h3',
            'contentHorizontalPosition' => 'start',
        ]);

        $fallbackTranslation = $slide->getOrCreateTranslation('en_US');
        $fallbackTranslation->setSlideSettings([
            'enabled' => true,
            'headlineElement' => 'h1',
            'contentHorizontalPosition' => 'end',
        ]);

        $currentTranslation = $slide->getOrCreateTranslation('de_DE');
        $currentTranslation->setSlideSettings([
            'enabled' => true,
        ]);

        $localized = $slide->getLocalizedSlideSettings('de_DE', 'en_US');

        self::assertSame('h1', $localized['headlineElement']);
        self::assertSame('end', $localized['contentHorizontalPosition']);
    }
}
