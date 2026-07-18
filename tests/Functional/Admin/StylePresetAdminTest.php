<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Entity\StylePreset;

final class StylePresetAdminTest extends FunctionalTestCase
{
    public function testStylePresetIndexRenders(): void
    {
        $this->ensureChannel();
        $this->createStylePreset('functional-preset-index', StylePreset::TYPE_SLIDE);
        $this->logInAsAdmin();

        $this->client->request('GET', '/admin/style-presets/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'functional-preset-index');
    }

    public function testStylePresetCreateFormRenders(): void
    {
        $this->ensureChannel();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', '/admin/style-presets/new');

        self::assertResponseIsSuccessful();

        foreach (['code', 'type', 'label', 'settings', 'mockupImage'] as $field) {
            self::assertGreaterThan(
                0,
                $crawler->filter(sprintf('[name*="[%s]"]', $field))->count(),
                sprintf('Expected the preset create form to contain the "%s" field.', $field),
            );
        }
    }

    public function testCreateSliderFromPresetClonesSlides(): void
    {
        $this->ensureChannel();

        // Leftovers from earlier runs (no per-test DB reset here).
        $staleSlider = $this->entityManager()->getRepository(Slider::class)->findOneBy(['code' => 'functional-from-preset']);
        if ($staleSlider !== null) {
            foreach ($staleSlider->getSlides()->toArray() as $staleSlide) {
                $this->entityManager()->remove($staleSlide);
            }
            $this->entityManager()->remove($staleSlider);
            $this->entityManager()->flush();
        }

        $slider = $this->createSlider('functional-preset-source-slider');
        $sourceSlides = $slider->getSlides()->toArray();
        self::assertNotSame([], $sourceSlides);

        $preset = $this->createStylePreset('functional-slider-preset', StylePreset::TYPE_SLIDER, [
            'settings.autoplay.enabled' => true,
            'settings.speed' => 750,
        ]);
        foreach ($sourceSlides as $sourceSlide) {
            $preset->addSlide($sourceSlide);
        }
        $this->entityManager()->flush();

        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', '/admin/sliders/new/from-preset/functional-slider-preset');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="slider"]')->form();
        $form['slider[code]'] = 'functional-from-preset';
        if ($form->has('slider[translations][en_US][name]')) {
            $form['slider[translations][en_US][name]'] = 'From Preset';
        }
        $this->client->submit($form);

        $this->entityManager()->clear();
        /** @var Slider|null $created */
        $created = $this->entityManager()->getRepository(Slider::class)->findOneBy(['code' => 'functional-from-preset']);
        self::assertNotNull($created, 'Slider created from preset must be persisted.');

        $settings = $created->getSettings();
        self::assertTrue($settings['autoplay']['enabled'] ?? false);
        self::assertSame(750, $settings['speed'] ?? null);

        $clonedSlides = $created->getSlides()->toArray();
        self::assertCount(count($sourceSlides), $clonedSlides);

        $sourceIds = array_map(static fn (Slide $slide): ?int => $slide->getId(), $sourceSlides);
        foreach ($clonedSlides as $clonedSlide) {
            self::assertNotContains($clonedSlide->getId(), $sourceIds, 'Cloned slides must be new rows, not links to the preset sources.');
            self::assertStringContainsString('-copy-', $clonedSlide->getCode());
        }
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createStylePreset(string $code, string $type, array $settings = []): StylePreset
    {
        $existing = $this->entityManager()->getRepository(StylePreset::class)->findOneBy(['code' => $code]);
        if ($existing !== null) {
            $this->entityManager()->remove($existing);
            $this->entityManager()->flush();
        }

        $preset = new StylePreset();
        $preset->setCode($code);
        $preset->setType($type);
        $preset->setLabel(ucwords(str_replace('-', ' ', $code)));
        $preset->setSettings($settings !== [] ? $settings : ['settings.responsive.desktop.textColor' => 'rgba(255, 255, 255, 1)']);

        $this->entityManager()->persist($preset);
        $this->entityManager()->flush();

        return $preset;
    }
}
