<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

final class SliderLocalizationTest extends TestCase
{
    public function testItFallsBackToBaseName(): void
    {
        $slider = new Slider();
        $slider->setName('Base slider');

        self::assertSame('Base slider', $slider->getLocalizedName('de_DE', 'en_US'));
    }

    public function testItUsesTranslationName(): void
    {
        $slider = new Slider();
        $slider->setName('Base slider');

        $translation = $slider->getOrCreateTranslation('de_DE');
        $translation->setName('DE slider');

        self::assertSame('DE slider', $slider->getLocalizedName('de_DE', 'en_US'));
    }

    public function testItUsesBaseSettingsOnly(): void
    {
        $slider = new Slider();
        $slider->setSettings([
            'slideEffect' => 'fade',
            'autoplay' => [
                'active' => false,
                'interval' => 7000,
            ],
            'channelCodes' => ['WEB-US'],
        ]);

        $translation = $slider->getOrCreateTranslation('de_DE');
        $translation->setSettings([
            'autoplay' => [
                'active' => true,
            ],
            'channelCodes' => [],
        ]);

        $localized = $slider->getLocalizedSettings('de_DE', 'en_US');

        self::assertSame('fade', $localized['slideEffect']);
        self::assertFalse($localized['autoplay']['active']);
        self::assertSame(7000, $localized['autoplay']['interval']);
        self::assertSame(['WEB-US'], $localized['channelCodes']);
    }
}
