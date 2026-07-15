<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;

final class SlideOverridesTest extends TestCase
{
    public function testMediaOverrideIsIgnoredWithoutTheFlag(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideCover('/media/de-cover.jpg');
        $translation->setOverrides(['media' => false]);

        self::assertSame('/media/base-cover.jpg', $slide->getLocalizedSlideCover('de_DE'));
    }

    public function testMediaOverrideAppliesWithTheFlag(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideCover('/media/de-cover.jpg');
        $translation->setOverrides(['media' => true]);

        self::assertSame('/media/de-cover.jpg', $slide->getLocalizedSlideCover('de_DE'));
    }

    public function testLegacyTranslationMediaStillApplies(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        // No override flags saved at all (legacy data).
        $translation->setSlideCover('/media/de-cover.jpg');

        self::assertSame('/media/de-cover.jpg', $slide->getLocalizedSlideCover('de_DE'));
    }

    public function testButtonOverrideGatesTranslatedLabelAndUrl(): void
    {
        $slide = $this->createSlide();
        $slide->setButtonLabel('Base label');
        $slide->setUrl('/base');

        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setButtonLabel('DE label');
        $translation->setUrl('/de');
        $translation->setOverrides(['button' => false]);

        self::assertSame('Base label', $slide->getLocalizedButtonLabel('de_DE'));
        self::assertSame('/base', $slide->getLocalizedUrl('de_DE'));

        $translation->setOverrides(['button' => true]);

        self::assertSame('DE label', $slide->getLocalizedButtonLabel('de_DE'));
        self::assertSame('/de', $slide->getLocalizedUrl('de_DE'));
    }

    public function testTranslatedTextsAlwaysApplyWithoutSettingsOverride(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'title' => 'DE Titel',
                    'description' => 'DE Beschreibung',
                    'headlineElement' => 'h1',
                    'contentTextAlign' => 'right',
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);
        $translation->setOverrides(['settings' => false]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('DE Titel', $settings['responsive']['desktop']['title']);
        self::assertSame('DE Beschreibung', $settings['responsive']['desktop']['description']);
        self::assertSame('h3', $settings['responsive']['desktop']['headlineElement'], 'Heading tag must come from the base slide without the settings override.');
        self::assertSame('left', $settings['responsive']['desktop']['contentTextAlign']);
    }

    public function testSettingsOverrideAppliesAllDisplaySettings(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'title' => 'DE Titel',
                    'headlineElement' => 'h1',
                    'contentTextAlign' => 'right',
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);
        $translation->setOverrides(['settings' => true]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('h1', $settings['responsive']['desktop']['headlineElement']);
        self::assertSame('right', $settings['responsive']['desktop']['contentTextAlign']);
    }

    public function testOverrideFlagsSurviveNormalization(): void
    {
        $translation = new SlideTranslation();
        $translation->setSlideSettings([
            'overrides' => ['media' => true, 'settings' => false, 'button' => true],
            'responsive' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
        ]);

        self::assertTrue($translation->isMediaOverrideEnabled());
        self::assertFalse($translation->isSettingsOverrideEnabled());
        self::assertTrue($translation->isButtonOverrideEnabled());
    }

    private function createSlide(): Slide
    {
        $slide = new Slide();
        $slide->setCode('test-slide');
        $slide->setSlideCover('/media/base-cover.jpg');
        $slide->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'title' => 'Base title',
                    'headlineElement' => 'h3',
                    'contentTextAlign' => 'left',
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);

        return $slide;
    }

    private function addTranslation(Slide $slide, string $localeCode): SlideTranslation
    {
        $translation = new SlideTranslation();
        $translation->setLocaleCode($localeCode);
        $slide->addTranslation($translation);

        return $translation;
    }
}
