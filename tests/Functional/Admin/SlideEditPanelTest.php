<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

final class SlideEditPanelTest extends FunctionalTestCase
{
    public function testPanelRendersFrameAndForm(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-edit-panel-slider');
        $slide = $this->firstSlide($slider);
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slide->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('turbo-frame#vanssa-slide-edit-panel-%d', $slide->getId()));
        self::assertSelectorExists('form#slide');
        self::assertSelectorExists('[name="slide[settings][responsive][desktop][headlineFontSize]"]');
    }

    public function testPanelRendersStandaloneSubmitWrapperWithButton(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-edit-panel-standalone-submit-slider');
        $slide = $this->firstSlide($slider);
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slide->getId()));

        self::assertResponseIsSuccessful();
        $wrapper = $crawler->filter('[data-vanssa-standalone-submit]');
        self::assertGreaterThan(0, $wrapper->count());
        self::assertGreaterThan(0, $wrapper->filter('button[type="submit"]')->count());
    }

    public function testPanelSavesWithoutWipingAssociations(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-edit-panel-save-slider');
        $slide = $this->firstSlide($slider);
        $slide->setChannelCodes(['FUNCTIONAL']);
        $this->entityManager()->flush();

        $slideId = $slide->getId();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slideId));
        $form = $crawler->selectButton('Update')->form();
        $form['slide[settings][responsive][desktop][headlineFontSize]'] = '2rem';

        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-success');

        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Slide::class, $slideId);
        self::assertInstanceOf(Slide::class, $reloaded);
        $settings = $reloaded->getSlideSettings();
        self::assertIsArray($settings['responsive'] ?? null);
        $responsive = $settings['responsive'];
        self::assertIsArray($responsive['desktop'] ?? null);
        self::assertSame('2rem', $responsive['desktop']['headlineFontSize'] ?? null);

        // Guard against the clear-missing wipe: sliders / channels / enabled
        // must survive an edit-panel round-trip untouched.
        self::assertTrue($reloaded->isEnabled());
        self::assertSame(['FUNCTIONAL'], $reloaded->getChannelCodes());
        self::assertCount(1, $reloaded->getSliders());
    }

    public function testPanelRejectsInvalidSubmission(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-edit-panel-invalid-slider');
        $slide = $this->firstSlide($slider);
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slide->getId()));
        $form = $crawler->selectButton('Update')->form();
        $form['slide[url]'] = 'not a url';

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('form#slide');
    }

    public function testPanelFailsForUnknownSlide(): void
    {
        $this->ensureChannel();
        $this->logInAsAdmin();

        $this->client->request('GET', '/admin/slides/999999/edit-panel');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPanelRequiresAdminAuthentication(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-edit-panel-auth-slider');
        $slide = $this->firstSlide($slider);

        $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slide->getId()));

        self::assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/admin/login', $location);
    }

    private function firstSlide(\Vanssa\SyliusSliderPlugin\Entity\Slider $slider): Slide
    {
        $slide = $slider->getSlides()->first();
        self::assertInstanceOf(Slide::class, $slide);

        return $slide;
    }
}
