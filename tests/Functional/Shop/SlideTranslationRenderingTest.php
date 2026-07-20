<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Shop;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

final class SlideTranslationRenderingTest extends FunctionalTestCase
{
    public function testTitleStoredOnlyOnTranslationRendersOnStorefront(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-translation-title-slider', [], 1);
        $slide = $slider->getSlides()->first();
        self::assertInstanceOf(Slide::class, $slide);

        // Base slide carries no title at all — only the en_US translation does.
        self::assertArrayNotHasKey('title', $slide->getSlideSettings()['responsive']['desktop'] ?? []);

        $translation = $slide->getOrCreateTranslation('en_US');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => ['title' => 'Translation-Only Title'],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-translation-title-slider');

        self::assertResponseIsSuccessful();
        $headline = $crawler->filter('.vanssa-slide__headline .vanssa-breakpoint-text--desktop');
        self::assertSame(1, $headline->count());
        self::assertSame('Translation-Only Title', trim($headline->text()));
    }

    public function testTranslatedTitleNewlineIsPreservedInRenderedOutput(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-translation-newline-slider', [], 1);
        $slide = $slider->getSlides()->first();
        self::assertInstanceOf(Slide::class, $slide);

        $translation = $slide->getOrCreateTranslation('en_US');
        $translation->setSlideSettings([
            'responsive' => [
                'desktop' => ['title' => "New\nCollection"],
                'tablet' => [],
                'mobile' => [],
            ],
        ]);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-translation-newline-slider');

        self::assertResponseIsSuccessful();

        // Assert against the resolved entity data (source of truth for the
        // literal character), and against the raw HTML: Twig auto-escaping
        // only escapes HTML-special characters, never strips newlines — CSS
        // (white-space: pre-line) is what turns it into a visual line break.
        self::assertSame("New\nCollection", $slide->getLocalizedSlideSettings('en_US', 'en_US')['responsive']['desktop']['title']);

        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString("New\nCollection", $html, 'The literal newline must reach the rendered HTML, not just the resolved settings array.');

        $headline = $crawler->filter('.vanssa-slide__headline .vanssa-breakpoint-text--desktop');
        self::assertSame(1, $headline->count());
        // text() normalizes whitespace by default (collapsing the newline into a
        // space); pass normalizeWhitespace=false to see the literal character.
        self::assertStringContainsString("New\nCollection", $headline->text(null, false));
    }
}
