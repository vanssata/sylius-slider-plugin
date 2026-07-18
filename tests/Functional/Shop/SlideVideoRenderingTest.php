<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Shop;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;

final class SlideVideoRenderingTest extends FunctionalTestCase
{
    public function testYouTubeReferenceRendersPrivacyEnhancedEmbed(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-video-embed-slider', [], 1);
        $slide = $slider->getSlides()->first();
        $slide->setSlideCoverVideo('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-video-embed-slider');

        self::assertResponseIsSuccessful();
        $iframe = $crawler->filter('iframe.vanssa-slide__media--embed');
        self::assertGreaterThan(0, $iframe->count(), 'Expected an embedded player for the YouTube reference.');
        self::assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?', (string) $iframe->attr('src'));
        self::assertStringContainsString('enablejsapi=1', (string) $iframe->attr('src'));
        self::assertStringContainsString('autoplay=0', (string) $iframe->attr('src'));
    }

    public function testSelfHostedVideoLoopsOnlyWithoutSliderAutoplay(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-video-loop-slider', ['autoplay' => ['enabled' => false]], 1);
        $slide = $slider->getSlides()->first();
        $slide->setSlideCoverVideo('/media/functional/video.mp4');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-video-loop-slider');
        self::assertResponseIsSuccessful();
        $video = $crawler->filter('video.vanssa-slide__media');
        self::assertGreaterThan(0, $video->count());
        self::assertNotNull($video->attr('loop'), 'Without slider autoplay the video keeps looping.');

        $slider->setSettings(array_replace($slider->getSettings(), ['autoplay' => ['enabled' => true, 'interval' => 5000]]));
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-video-loop-slider');
        $video = $crawler->filter('video.vanssa-slide__media');
        self::assertNull($video->attr('loop'), 'With slider autoplay the video must fire ended, so no loop.');
    }

    public function testClickPlaybackModeRendersPlayButton(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-video-click-slider', [], 1);
        $slide = $slider->getSlides()->first();
        $slide->setSlideCoverVideo('/media/functional/video.mp4');
        $slide->setSlideSettings(array_replace($slide->getSlideSettings(), ['video' => ['playback' => 'click']]));
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-video-click-slider');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.vanssa-slide__video-play');
        self::assertSame('click', $crawler->filter('.vanssa-slide')->attr('data-vanssa-video-playback'));
    }

    public function testAutoplayPlaybackModeHasNoPlayButton(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-video-auto-slider', [], 1);
        $slide = $slider->getSlides()->first();
        $slide->setSlideCoverVideo('/media/functional/video.mp4');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/slider/functional-video-auto-slider');
        self::assertResponseIsSuccessful();
        self::assertSame(0, $crawler->filter('.vanssa-slide__video-play')->count());
        self::assertSame('autoplay', $crawler->filter('.vanssa-slide')->attr('data-vanssa-video-playback'));
    }
}
