<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

final class SlideLocalizationTest extends TestCase
{
    public function testItUsesLocalizedNameWithoutTextFallbackFields(): void
    {
        $slide = new Slide();
        $slide->setName('Base name');

        self::assertSame('Base name', $slide->getLocalizedName('en_US', 'en_US'));
    }

    public function testItMergesLocalizedSlideSettingsWithBaseSettings(): void
    {
        $slide = new Slide();
        $slide->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'headlineElement' => 'h2',
                    'contentHorizontalPosition' => 'end',
                ],
            ],
            'linking' => [
                'type' => 'custom',
                'buttonSize' => 'md',
            ],
        ]);

        $translation = $slide->getOrCreateTranslation('de_DE');
        $translation->setSlideSettings([
            'linking' => [
                'type' => 'product',
            ],
        ]);

        $localized = $slide->getLocalizedSlideSettings('de_DE', 'en_US');

        self::assertSame('h2', $localized['responsive']['desktop']['headlineElement']);
        self::assertSame('end', $localized['responsive']['desktop']['contentHorizontalPosition']);
        self::assertSame('product', $localized['linking']['type']);
        self::assertSame('md', $localized['linking']['buttonSize']);
    }

    public function testItFallsBackToFallbackLocaleSettingsWhenCurrentLocaleHasEmptyOverrides(): void
    {
        $slide = new Slide();
        $slide->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'headlineElement' => 'h3',
                    'contentHorizontalPosition' => 'start',
                ],
            ],
        ]);

        $fallbackTranslation = $slide->getOrCreateTranslation('en_US');
        $fallbackTranslation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'headlineElement' => 'h1',
                    'contentHorizontalPosition' => 'end',
                ],
            ],
        ]);

        $currentTranslation = $slide->getOrCreateTranslation('de_DE');
        $currentTranslation->setSlideSettings([]);

        $localized = $slide->getLocalizedSlideSettings('de_DE', 'en_US');

        self::assertSame('h1', $localized['responsive']['desktop']['headlineElement']);
        self::assertSame('end', $localized['responsive']['desktop']['contentHorizontalPosition']);
    }
}
