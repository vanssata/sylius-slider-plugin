<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Shop;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;

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
}
