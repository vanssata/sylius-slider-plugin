<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Cloner;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Cloner\SlideCloner;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;

final class SlideClonerTest extends TestCase
{
    public function testClonesScalarsMediaAndSettings(): void
    {
        $source = new Slide();
        $source->setCode('hero-1');
        $source->setName('Hero');
        $source->setButtonLabel('Shop now');
        $source->setUrl('https://example.com');
        $source->setProductCode('MUG');
        $source->setSlideCover('/media/cover.jpg');
        $source->setSlideCoverMobile('/media/cover-m.jpg');
        $source->setSlideCoverVideo('/media/cover.mp4');
        $source->setPosition(3);
        $source->setEnabled(false);
        $source->setChannelCodes(['WEB', 'POS']);
        $source->setSlideSettings([
            'linking' => ['type' => 'custom'],
            'parallax' => ['strength' => '1rem'],
            'responsive' => ['desktop' => ['textColor' => 'red'], 'tablet' => [], 'mobile' => []],
        ]);

        $clone = (new SlideCloner())->clone($source);

        self::assertNotSame($source->getCode(), $clone->getCode());
        self::assertStringStartsWith('hero-1-copy-', $clone->getCode());
        self::assertLessThanOrEqual(64, strlen($clone->getCode()));
        self::assertSame('Hero', $clone->getName());
        self::assertSame('Shop now', $clone->getButtonLabel());
        self::assertSame('https://example.com', $clone->getUrl());
        self::assertSame('MUG', $clone->getProductCode());
        self::assertSame('/media/cover.jpg', $clone->getSlideCover());
        self::assertSame('/media/cover-m.jpg', $clone->getSlideCoverMobile());
        self::assertSame('/media/cover.mp4', $clone->getSlideCoverVideo());
        self::assertSame(3, $clone->getPosition());
        self::assertFalse($clone->isEnabled());
        self::assertSame(['WEB', 'POS'], $clone->getChannelCodes());
        self::assertSame($source->getSlideSettings(), $clone->getSlideSettings());
    }

    public function testClonedCodesAreUniquePerInvocation(): void
    {
        $source = new Slide();
        $source->setCode('hero-1');
        $source->setName('Hero');

        $cloner = new SlideCloner();

        self::assertNotSame($cloner->clone($source)->getCode(), $cloner->clone($source)->getCode());
    }

    public function testLongSourceCodesStayWithinLimit(): void
    {
        $source = new Slide();
        $source->setCode(str_repeat('a', 64));
        $source->setName('Long');

        $clone = (new SlideCloner())->clone($source);

        self::assertSame(64, strlen($clone->getCode()));
        self::assertStringContainsString('-copy-', $clone->getCode());
    }

    public function testClonesTranslationsIndependently(): void
    {
        $source = new Slide();
        $source->setCode('hero-1');
        $source->setName('Hero');

        $translation = new SlideTranslation();
        $translation->setLocaleCode('de_DE');
        $translation->setName('Held');
        $translation->setButtonLabel('Jetzt kaufen');
        $translation->setSlideCover('/media/de-cover.jpg');
        $translation->setSlideSettings([
            'overrides' => ['colors' => true, 'media' => true],
            'responsive' => ['desktop' => ['textColor' => 'blue'], 'tablet' => [], 'mobile' => []],
        ]);
        $translation->setSlide($source);
        $source->addTranslation($translation);

        $clone = (new SlideCloner())->clone($source);

        self::assertCount(1, $clone->getTranslations());
        /** @var SlideTranslation $clonedTranslation */
        $clonedTranslation = $clone->getTranslations()->first();
        self::assertNotSame($translation, $clonedTranslation);
        self::assertSame('de_DE', $clonedTranslation->getLocaleCode());
        self::assertSame('Held', $clonedTranslation->getName());
        self::assertSame('Jetzt kaufen', $clonedTranslation->getButtonLabel());
        self::assertSame('/media/de-cover.jpg', $clonedTranslation->getSlideCover());
        self::assertTrue($clonedTranslation->isColorsOverrideEnabled());
        self::assertTrue($clonedTranslation->isMediaOverrideEnabled());
        self::assertSame($clone, $clonedTranslation->getSlide());

        // Mutating the clone's translation must not touch the source.
        $clonedTranslation->setName('Changed');
        self::assertSame('Held', $translation->getName());
    }
}
