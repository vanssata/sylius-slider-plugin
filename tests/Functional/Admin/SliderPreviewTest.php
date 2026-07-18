<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;

final class SliderPreviewTest extends FunctionalTestCase
{
    public function testPreviewRendersSliderForChannelAndLocale(): void
    {
        $channel = $this->ensureChannel();
        $slider = $this->createSlider('functional-preview-slider');
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf(
            '/admin/sliders/%d/preview?channel=%s&locale=en_US',
            $slider->getId(),
            $channel->getCode(),
        ));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('section.vanssa-slider');
        self::assertSelectorExists('.vanssa-slide');
    }

    public function testPreviewResolvesChannelAutomaticallyWhenOmitted(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-preview-auto-channel-slider');
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/sliders/%d/preview', $slider->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('section.vanssa-slider');
    }

    public function testPreviewFailsForUnknownChannel(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-preview-slider');
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/sliders/%d/preview?channel=NOPE', $slider->getId()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testPreviewFailsForUnknownSlider(): void
    {
        $channel = $this->ensureChannel();
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/sliders/999999/preview?channel=%s', $channel->getCode()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testPreviewRequiresAdminAuthentication(): void
    {
        $channel = $this->ensureChannel();
        $slider = $this->createSlider('functional-preview-slider');

        $this->client->request('GET', sprintf(
            '/admin/sliders/%d/preview?channel=%s',
            $slider->getId(),
            $channel->getCode(),
        ));

        self::assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/admin/login', $location);
    }
}
