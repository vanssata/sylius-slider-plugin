<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;

final class SliderAdminTest extends FunctionalTestCase
{
    public function testSliderIndexRenders(): void
    {
        $this->ensureChannel();
        $this->createSlider('functional-index-slider');
        $this->logInAsAdmin();

        $this->client->followRedirects(true);
        // limit=100: the shared test DB accumulates functional fixtures, so
        // the default page size could paginate this row away.
        $this->client->request('GET', '/admin/sliders/?limit=100');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'functional-index-slider');
    }

    public function testSliderCreateFormContainsNewOptionFields(): void
    {
        $this->ensureChannel();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', '/admin/sliders/new');

        self::assertResponseIsSuccessful();

        foreach (['arrowsPosition', 'arrowsVerticalAlign', 'paginationPosition', 'paginationStyle', 'keyboardNavigation', 'touchSwipe', 'showProgressBar', 'lazyLoadMedia'] as $field) {
            self::assertGreaterThan(
                0,
                $crawler->filter(sprintf('[name*="[settings][%s]"]', $field))->count(),
                sprintf('Expected the create form to contain the "%s" settings field.', $field),
            );
        }
    }

    public function testSliderUpdateShowsPersistedNewOptions(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-options-slider', [
            'arrowsPosition' => 'outside',
            'paginationStyle' => 'numbers',
            'paginationPosition' => 'bottom-outside',
        ]);
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/sliders/%d/edit', $slider->getId()));

        self::assertResponseIsSuccessful();

        $arrowsPosition = $crawler->filter('[name*="[settings][arrowsPosition]"] option[selected]');
        self::assertSame('outside', $arrowsPosition->attr('value'));

        $paginationStyle = $crawler->filter('[name*="[settings][paginationStyle]"] option[selected]');
        self::assertSame('numbers', $paginationStyle->attr('value'));
    }
}
