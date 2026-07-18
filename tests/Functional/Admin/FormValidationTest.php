<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;

/**
 * Invalid create submissions must come back as form errors, never as 500s
 * (blank code used to hit the strict-typed setters with null).
 */
final class FormValidationTest extends FunctionalTestCase
{
    public function testCreatingSliderWithoutCodeShowsFormError(): void
    {
        $this->ensureChannel();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', '/admin/sliders/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="slider"]')->form();
        $form['slider[code]'] = '';
        $this->client->submit($form);

        $statusCode = $this->client->getResponse()->getStatusCode();
        self::assertLessThan(500, $statusCode, sprintf('Blank code must not produce a server error (got %d).', $statusCode));
        self::assertStringContainsString('should not be blank', (string) $this->client->getResponse()->getContent());

        $this->entityManager()->clear();
        self::assertNull(
            $this->entityManager()->getRepository(\Vanssa\SyliusSliderPlugin\Entity\Slider::class)->findOneBy(['code' => '']),
            'No slider row may be persisted for the invalid submission.',
        );
    }

    public function testCreatingSlideWithoutCodeShowsFormError(): void
    {
        $this->ensureChannel();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', '/admin/slides/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="slide"]')->form();
        $form['slide[code]'] = '';
        $this->client->submit($form);

        $statusCode = $this->client->getResponse()->getStatusCode();
        self::assertLessThan(500, $statusCode, sprintf('Blank code must not produce a server error (got %d).', $statusCode));
        self::assertStringContainsString('should not be blank', (string) $this->client->getResponse()->getContent());
    }

    public function testCreatingStylePresetWithoutCodeAndLabelShowsFormErrors(): void
    {
        $this->ensureChannel();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', '/admin/style-presets/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="style_preset"]')->form();
        $form['style_preset[code]'] = '';
        $form['style_preset[label]'] = '';
        $this->client->submit($form);

        $statusCode = $this->client->getResponse()->getStatusCode();
        self::assertLessThan(500, $statusCode, sprintf('Blank code/label must not produce a server error (got %d).', $statusCode));
        self::assertStringContainsString('should not be blank', (string) $this->client->getResponse()->getContent());
    }

    public function testStylePresetCreateFormPreselectsTypeFromQuery(): void
    {
        $this->ensureChannel();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', '/admin/style-presets/new?type=slider');
        self::assertResponseIsSuccessful();

        $selected = $crawler->filter('[name="style_preset[type]"] option[selected]');
        self::assertSame('slider', $selected->attr('value'));
    }
}
