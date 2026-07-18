<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

final class SlidePreviewTest extends FunctionalTestCase
{
    public function testPreviewRendersSlideForChannelAndLocale(): void
    {
        $channel = $this->ensureChannel();
        $slider = $this->createSlider('functional-slide-preview-slider');
        $slide = $this->firstSlide($slider);
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf(
            '/admin/slides/%d/preview?channel=%s&locale=en_US',
            $slide->getId(),
            $channel->getCode(),
        ));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('section.vanssa-slider');
        self::assertSelectorExists('.vanssa-slide');
    }

    public function testPreviewDefaultsToBaseSlideContentWhenLocaleOmitted(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-slide-preview-default-slider');
        $slide = $this->firstSlide($slider);
        $slide->setSlideSettings([
            'responsive' => [
                'desktop' => ['title' => 'Base Desktop Title'],
            ],
        ]);
        $this->entityManager()->flush();
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/slides/%d/preview', $slide->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.vanssa-slide__headline', 'Base Desktop Title');
    }

    public function testPreviewResolvesChannelAutomaticallyWhenOmitted(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-slide-preview-auto-channel-slider');
        $slide = $this->firstSlide($slider);
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/slides/%d/preview', $slide->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.vanssa-slide');
    }

    public function testPreviewFailsForUnknownSlide(): void
    {
        $channel = $this->ensureChannel();
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/slides/999999/preview?channel=%s', $channel->getCode()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testPreviewRequiresAdminAuthentication(): void
    {
        $channel = $this->ensureChannel();
        $slider = $this->createSlider('functional-slide-preview-auth-slider');
        $slide = $this->firstSlide($slider);

        $this->client->request('GET', sprintf(
            '/admin/slides/%d/preview?channel=%s',
            $slide->getId(),
            $channel->getCode(),
        ));

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
