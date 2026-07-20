<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;

final class SlideOverridesTest extends TestCase
{
    /**
     * Bypasses setSlideSettings()'s normalization to simulate a translation
     * persisted before the layout/colors/effects/visibility flags existed —
     * Doctrine hydrates entities via reflection too, so a real legacy row
     * would keep its raw, un-normalized JSON exactly like this.
     *
     * @param array<string, mixed> $slideSettings
     */
    private function setRawSlideSettings(SlideTranslation $translation, array $slideSettings): void
    {
        $property = new \ReflectionProperty(SlideTranslation::class, 'slideSettings');
        $property->setAccessible(true);
        $property->setValue($translation, $slideSettings);
    }

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

    public function testTextsAndTypographyAlwaysApplyRegardlessOfAnyOverrideFlag(): void
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
        $translation->setOverrides([]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('DE Titel', $settings['responsive']['desktop']['title']);
        self::assertSame('DE Beschreibung', $settings['responsive']['desktop']['description']);
        self::assertSame('h1', $settings['responsive']['desktop']['headlineElement'], 'Texts & Typography (title, description, headline tag, font sizes) always applies, with no override flag needed.');
        self::assertSame('left', $settings['responsive']['desktop']['contentTextAlign'], 'Layout must still fall back to the base slide without the layout override.');
    }

    public function testNullTranslatedTitleFallsBackToBaseTitle(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'title' => null,
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('Base title', $settings['responsive']['desktop']['title'], 'A blank/null translated title must not wipe the inherited base title.');
    }

    public function testBlankStringTranslatedTitleFallsBackToBaseTitle(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'title' => '',
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('Base title', $settings['responsive']['desktop']['title'], 'An empty-string translated title must not wipe the inherited base title.');
    }

    public function testNonEmptyTranslatedTitleStillOverridesBaseTitle(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'title' => 'DE Titel',
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('DE Titel', $settings['responsive']['desktop']['title'], 'A genuinely translated title must still override the base title.');
    }

    public function testTranslatedTitleWithNewlinePreservesLineBreak(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'title' => "New\nCollection",
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame("New\nCollection", $settings['responsive']['desktop']['title'], 'The literal newline character must survive resolution.');
    }

    public function testLayoutOverrideAppliesIndependentlyOfColors(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => [
                    'contentTextAlign' => 'right',
                    'textColor' => 'rgba(255, 0, 0, 1)',
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);
        $translation->setOverrides(['layout' => true, 'colors' => false]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('right', $settings['responsive']['desktop']['contentTextAlign'], 'Layout override is enabled, so it must apply.');
        self::assertArrayNotHasKey('textColor', $settings['responsive']['desktop'], 'Colors override is disabled, so the translated color must not apply.');
    }

    public function testLegacyCombinedSettingsFlagStillEnablesAllGranularGroups(): void
    {
        $slide = $this->createSlide();
        $translation = $this->addTranslation($slide, 'de_DE');
        // Legacy data saved before layout/colors/effects/visibility flags
        // existed: only the old combined "settings" flag is present.
        $this->setRawSlideSettings($translation, [
            'overrides' => ['settings' => true],
            'responsive' => [
                'desktop' => [
                    'headlineElement' => 'h1',
                    'contentTextAlign' => 'right',
                    'textColor' => 'rgba(255, 0, 0, 1)',
                ],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);

        $settings = $slide->getLocalizedSlideSettings('de_DE');

        self::assertSame('h1', $settings['responsive']['desktop']['headlineElement']);
        self::assertSame('right', $settings['responsive']['desktop']['contentTextAlign']);
        self::assertSame('rgba(255, 0, 0, 1)', $settings['responsive']['desktop']['textColor']);
    }

    public function testOverrideFlagsSurviveNormalization(): void
    {
        $translation = new SlideTranslation();
        $translation->setSlideSettings([
            'overrides' => ['media' => true, 'settings' => false, 'button' => true, 'layout' => true, 'colors' => false, 'effects' => true, 'visibility' => false],
            'responsive' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
        ]);

        self::assertTrue($translation->isMediaOverrideEnabled());
        self::assertFalse($translation->isSettingsOverrideEnabled());
        self::assertTrue($translation->isButtonOverrideEnabled());
        self::assertTrue($translation->isLayoutOverrideEnabled());
        self::assertFalse($translation->isColorsOverrideEnabled());
        self::assertTrue($translation->isEffectsOverrideEnabled());
        self::assertFalse($translation->isVisibilityOverrideEnabled());
    }

    public function testParallaxStrengthSurvivesNormalization(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideSettings([
            'parallax' => ['strength' => '2rem'],
            'responsive' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
        ]);

        self::assertSame(['strength' => '2rem'], $slide->getSlideSettings()['parallax'] ?? null);
    }

    public function testBlankParallaxStrengthIsDroppedByNormalization(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideSettings([
            'parallax' => ['strength' => '  '],
            'responsive' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
        ]);

        self::assertArrayNotHasKey('parallax', $slide->getSlideSettings());
    }

    public function testExplicitZeroParallaxStrengthIsKept(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideSettings([
            'parallax' => ['strength' => '0'],
            'responsive' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
        ]);

        self::assertSame(['strength' => '0'], $slide->getSlideSettings()['parallax'] ?? null);
    }

    public function testVideoPlaybackSurvivesNormalization(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideSettings([
            'video' => ['playback' => 'click'],
            'responsive' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
        ]);

        self::assertSame(['playback' => 'click'], $slide->getSlideSettings()['video'] ?? null);
    }

    public function testUnknownVideoPlaybackIsDroppedByNormalization(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideSettings([
            'video' => ['playback' => 'bogus'],
            'responsive' => ['desktop' => [], 'tablet' => [], 'mobile' => []],
        ]);

        self::assertArrayNotHasKey('video', $slide->getSlideSettings());
    }

    public function testDesktopVideoAppliesToAllBreakpointsWhenOthersAreEmpty(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideCoverVideo('/media/desktop.mp4');

        $groups = $slide->getLocalizedMediaGroups('en_US');

        self::assertCount(1, $groups);
        self::assertSame('video', $groups[0]['type']);
        self::assertSame('/media/desktop.mp4', $groups[0]['src']);
        self::assertSame(['desktop', 'tablet', 'mobile'], $groups[0]['breakpoints']);
    }

    public function testMobileVideoOverridesDesktopVideoOnMobileOnly(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideCoverVideo('/media/desktop.mp4');
        $slide->setSlideCoverVideoMobile('/media/mobile.mp4');

        $groups = $slide->getLocalizedMediaGroups('en_US');

        self::assertCount(2, $groups);
        self::assertSame(['desktop', 'tablet'], $groups[0]['breakpoints']);
        self::assertSame('/media/desktop.mp4', $groups[0]['src']);
        self::assertSame(['mobile'], $groups[1]['breakpoints']);
        self::assertSame('/media/mobile.mp4', $groups[1]['src']);
    }

    public function testBreakpointWithoutAnyVideoFallsBackToItsImageThenDesktopImage(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideCoverMobile('/media/mobile.jpg');

        $groups = $slide->getLocalizedMediaGroups('en_US');

        self::assertCount(2, $groups);
        self::assertSame('image', $groups[0]['type']);
        self::assertSame('/media/base-cover.jpg', $groups[0]['src']);
        self::assertSame(['desktop', 'tablet'], $groups[0]['breakpoints']);
        self::assertSame('/media/mobile.jpg', $groups[1]['src']);
        self::assertSame(['mobile'], $groups[1]['breakpoints']);
    }

    public function testTranslationVideoAppliesOnlyWithMediaOverride(): void
    {
        $slide = $this->createSlide();
        $slide->setSlideCoverVideo('/media/base.mp4');

        $translation = $this->addTranslation($slide, 'de_DE');
        $translation->setSlideCoverVideo('/media/de.mp4');
        $translation->setOverrides(['media' => false]);

        self::assertSame('/media/base.mp4', $slide->getLocalizedSlideCoverVideo('de_DE'));

        $translation->setOverrides(['media' => true]);

        self::assertSame('/media/de.mp4', $slide->getLocalizedSlideCoverVideo('de_DE'));
        self::assertSame('video', $slide->getLocalizedMediaGroups('de_DE')[0]['type']);
        self::assertSame('/media/de.mp4', $slide->getLocalizedMediaGroups('de_DE')[0]['src']);
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
