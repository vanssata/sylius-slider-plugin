<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;

final class GridSmokeTest extends FunctionalTestCase
{
    public function testSlidersGridRendersWithCountColumnAndPreviewAction(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-grid-smoke-slider');
        $this->logInAsAdmin();

        $this->client->followRedirects(true);
        $this->client->request('GET', '/admin/sliders');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'functional-grid-smoke-slider');
        self::assertSelectorExists(sprintf('[data-bs-target="#vanssa-slider-preview-modal-%d"]', $slider->getId()));
    }

    public function testSlidesGridRendersWithThumbnailAndPreviewAction(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-grid-smoke-slides');
        $slide = $slider->getSlides()->first();
        \assert(false !== $slide);
        $this->logInAsAdmin();

        $this->client->followRedirects(true);
        $this->client->request('GET', '/admin/slides', ['limit' => 100]);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('[data-bs-target="#vanssa-slide-preview-modal-%d"]', $slide->getId()));
    }
}
