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
        // limit=100: the shared test DB accumulates functional fixtures across
        // runs, so the default page size could paginate this row away (same
        // reasoning as SliderAdminTest::testSliderIndexRenders).
        $this->client->request('GET', '/admin/sliders', ['limit' => 100]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'functional-grid-smoke-slider');
        self::assertSelectorExists(sprintf('[data-bs-target="#vanssa-slider-preview-modal-%d"]', $slider->getId()));
    }

    public function testSlidesGridRendersWithThumbnailAndEditAction(): void
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

    public function testSlidesGridEditActionOpensModalAndHasNoPlainEditLink(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-grid-smoke-edit-action');
        $slide = $slider->getSlides()->first();
        \assert(false !== $slide);
        $this->logInAsAdmin();

        $this->client->followRedirects(true);
        $crawler = $this->client->request('GET', '/admin/slides', ['limit' => 100]);

        self::assertResponseIsSuccessful();

        // Pencil edit-action button targeting the modal, with the "Edit" title/label.
        $editButton = $crawler->filter(sprintf(
            'button[data-bs-toggle="modal"][data-bs-target="#vanssa-slide-preview-modal-%d"]',
            $slide->getId(),
        ));
        self::assertGreaterThan(0, $editButton->count(), 'Expected a pencil edit-action button targeting the slide preview modal.');
        self::assertSame('Edit', trim((string) $editButton->attr('title')));

        // The old plain row-action link to the full edit page must be gone.
        // Note: the modal itself legitimately contains an "Open full editor"
        // anchor with this same href, so the check must exclude anchors
        // nested inside the (hidden-by-default) modal dialog.
        $router = self::getContainer()->get('router');
        \assert($router instanceof \Symfony\Component\Routing\RouterInterface);
        $editHref = $router->generate('vanssa_sylius_slider_admin_slide_update', ['id' => $slide->getId()]);

        $plainEditLink = $crawler->filterXPath(sprintf(
            '//a[@href="%s" and not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " modal ")])]',
            $editHref,
        ));
        self::assertSame(0, $plainEditLink->count(), 'Did not expect a plain (non-modal) row-action link to the full edit page.');
    }

    public function testSlidesGridEditModalContainsEditSlideTitleAndFullEditorLink(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-grid-smoke-modal-content');
        $slide = $slider->getSlides()->first();
        \assert(false !== $slide);
        $this->logInAsAdmin();

        $this->client->followRedirects(true);
        $crawler = $this->client->request('GET', '/admin/slides', ['limit' => 100]);

        self::assertResponseIsSuccessful();

        $modal = $crawler->filter(sprintf('#vanssa-slide-preview-modal-%d', $slide->getId()));
        self::assertGreaterThan(0, $modal->count());
        self::assertStringContainsString('Edit slide', $modal->filter('.modal-title')->text());

        $router = self::getContainer()->get('router');
        \assert($router instanceof \Symfony\Component\Routing\RouterInterface);
        $expectedHref = $router->generate('vanssa_sylius_slider_admin_slide_update', ['id' => $slide->getId()]);

        $fullEditorLink = $modal->filter(sprintf('a[href="%s"]', $expectedHref));
        self::assertGreaterThan(0, $fullEditorLink->count(), 'Expected an "Open full editor" link to the slide update route.');
        self::assertStringContainsString('Open full editor', $fullEditorLink->text());
    }
}
