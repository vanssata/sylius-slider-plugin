<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

final class SlideCreatePanelTest extends FunctionalTestCase
{
    public function testPanelRendersCreateForm(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-create-panel-slider');
        $this->logInAsAdmin();

        $this->client->request('GET', sprintf('/admin/slides/create-panel/%d', $slider->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('turbo-frame#vanssa-slide-create-panel-%d', $slider->getId()));
        self::assertSelectorExists('[name="slide[code]"]');
    }

    public function testPostCreatesSlideAttachedToTheSlider(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-create-panel-slider');
        $this->logInAsAdmin();

        $stale = $this->entityManager()->getRepository(Slide::class)->findOneBy(['code' => 'panel-created-slide']);
        if (null !== $stale) {
            $this->entityManager()->remove($stale);
            $this->entityManager()->flush();
        }

        $crawler = $this->client->request('GET', sprintf('/admin/slides/create-panel/%d', $slider->getId()));
        $form = $crawler->filter('form[name="slide"]')->form();
        $form['slide[code]'] = 'panel-created-slide';
        $form['slide[position]'] = '0';
        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        $created = $this->entityManager()->getRepository(Slide::class)->findOneBy(['code' => 'panel-created-slide']);
        self::assertInstanceOf(Slide::class, $created);
        self::assertTrue($created->getSliders()->exists(
            static fn ($key, $attached) => $attached->getCode() === 'functional-create-panel-slider',
        ), 'The new slide must be attached to the slider it was created from.');
    }

    public function testInvalidPostReturns422(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-create-panel-slider');
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/create-panel/%d', $slider->getId()));
        $form = $crawler->filter('form[name="slide"]')->form();
        $form['slide[code]'] = '';
        $this->client->submit($form);

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }
}
