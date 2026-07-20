<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Shop;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

final class SliderRenderingTest extends FunctionalTestCase
{
    public function testSliderPageRendersWithNewOptions(): void
    {
        $this->ensureChannel();
        $this->createSlider('functional-shop-slider', [
            'showNavigation' => true,
            'showArrows' => true,
            'arrowsPosition' => 'outside',
            'paginationStyle' => 'numbers',
            'paginationPosition' => 'top',
            'keyboardNavigation' => true,
            'touchSwipe' => true,
            'lazyLoadMedia' => true,
        ]);

        $crawler = $this->client->request('GET', '/slider/functional-shop-slider');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('section.vanssa-slider');
        self::assertSelectorExists('section.vanssa-slider.vanssa-slider--arrows-outside');
        self::assertSelectorExists('section.vanssa-slider.vanssa-slider--pagination-top');

        $options = (string) $crawler->filter('section.vanssa-slider')->attr('data-vanssa-slider-options-value');
        self::assertStringContainsString('"paginationStyle":"numbers"', $options);
        self::assertStringContainsString('"keyboardNavigation":true', $options);
        self::assertStringContainsString('"touchSwipe":true', $options);

        self::assertSame('0', (string) $crawler->filter('section.vanssa-slider')->attr('tabindex'));
    }

    public function testNonFirstSlideImagesAreLazyLoaded(): void
    {
        $this->ensureChannel();
        $this->createSlider('functional-lazy-slider', ['lazyLoadMedia' => true], 3);

        $crawler = $this->client->request('GET', '/slider/functional-lazy-slider');

        self::assertResponseIsSuccessful();

        $images = $crawler->filter('.vanssa-slide img.vanssa-slide__media');
        self::assertSame(3, $images->count());
        self::assertNull($images->eq(0)->attr('loading'), 'First slide image must load eagerly.');
        self::assertSame('lazy', $images->eq(1)->attr('loading'));
        self::assertSame('lazy', $images->eq(2)->attr('loading'));
    }

    public function testDisabledLazyLoadingKeepsEagerImages(): void
    {
        $this->ensureChannel();
        $this->createSlider('functional-eager-slider', ['lazyLoadMedia' => false], 2);

        $crawler = $this->client->request('GET', '/slider/functional-eager-slider');

        self::assertResponseIsSuccessful();

        $images = $crawler->filter('.vanssa-slide img.vanssa-slide__media');
        self::assertSame(2, $images->count());
        self::assertNull($images->eq(1)->attr('loading'));
    }

    public function testResponsiveSliderLayoutOverridesRenderMediaBlock(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-bp-slider', [
            'marginTop' => '2rem',
            'responsive' => [
                'tablet' => ['marginTop' => '1rem'],
                'mobile' => ['marginTop' => '0.5rem', 'maxHeight' => '320px'],
            ],
        ]);

        $crawler = $this->client->request('GET', '/slider/functional-bp-slider');

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('data-slider-code="functional-bp-slider"', $html);
        self::assertStringContainsString('--vanssa-slider-margin-top: 1rem', $html);
        self::assertStringContainsString('--vanssa-slider-margin-top: 0.5rem', $html);
        self::assertStringContainsString('--vanssa-slider-max-height: 320px', $html);
    }

    public function testSlideBreakpointOverridesRenderScopedStyleBlock(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-slide-bp-slider', [], 1);
        $slide = $slider->getSlides()->first();
        self::assertInstanceOf(Slide::class, $slide);
        $slide->setSlideSettings(array_replace_recursive($slide->getSlideSettings(), [
            'responsive' => [
                'tablet' => [
                    'textColor' => '#123456',
                    'mediaOverlayColor' => 'rgba(10, 20, 30, 0.4)',
                ],
                'mobile' => [
                    'contentTextAlign' => 'center',
                ],
            ],
        ]));
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-slide-bp-slider');

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        $slideSelector = '.vanssa-slide[data-slide-code="functional-slide-bp-slider-slide-1"]';

        // Desktop vars live in a per-slide <style> base rule, not an inline style attr:
        // inline declarations would beat the breakpoint media rules in the cascade.
        self::assertNull(
            $crawler->filter('.vanssa-slide[data-slide-code="functional-slide-bp-slider-slide-1"] .vanssa-slide__content')->attr('style'),
            'Slide content must not carry inline style vars.',
        );
        $baseRule = $this->extractRule($html, sprintf('/%s\s*\{([^}]*)\}/s', preg_quote($slideSelector, '/')));
        self::assertStringContainsString('--vanssa-slide-content-pos-x:', $baseRule);
        self::assertStringContainsString('--vanssa-slide-overlay-color: transparent', $baseRule);

        $tabletRule = $this->extractRule($html, sprintf(
            '/@media \(max-width: 1024px\)\s*\{\s*%s\s*\{([^}]*)\}/s',
            preg_quote($slideSelector, '/'),
        ));
        self::assertStringContainsString('--vanssa-slide-text-color: #123456', $tabletRule);
        self::assertStringContainsString('--vanssa-slide-overlay-color: rgba(10, 20, 30, 0.4)', $tabletRule);

        $mobileRule = $this->extractRule($html, sprintf(
            '/@media \(max-width: 767px\)\s*\{\s*%s\s*\{([^}]*)\}/s',
            preg_quote($slideSelector, '/'),
        ));
        self::assertStringContainsString('--vanssa-slide-text-align: center', $mobileRule);

        // A breakpoint-only overlay color still renders the overlay element, driven by
        // the CSS var (no inline background that would shadow breakpoint overrides).
        $overlay = $crawler->filter('.vanssa-slide[data-slide-code="functional-slide-bp-slider-slide-1"] .vanssa-slide__overlay');
        self::assertSame(1, $overlay->count(), 'Overlay must render when any breakpoint sets a color.');
        self::assertNull($overlay->attr('style'));
    }

    private function extractRule(string $html, string $pattern): string
    {
        self::assertSame(1, preg_match($pattern, $html, $matches), sprintf('Expected pattern %s in rendered HTML.', $pattern));

        return $matches[1];
    }
}
