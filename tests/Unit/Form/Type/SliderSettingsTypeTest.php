<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Form\Type;

use ReflectionClass;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Vanssa\SyliusSliderPlugin\Form\Type\ColorPickerType;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\AutoplaySettingsType;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\ParallaxSettingsType;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SliderSettingsType;
use Vanssa\SyliusSliderPlugin\Preset\SettingsPresetProvider;

final class SliderSettingsTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $presetProvider = new SettingsPresetProvider([]);

        return [
            new PreloadedExtension([
                new SliderSettingsType($presetProvider),
                new AutoplaySettingsType($presetProvider),
                new ParallaxSettingsType($presetProvider),
                new ColorPickerType($presetProvider),
            ], []),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testItNormalizesParallaxWithStrengthWhenSet(): void
    {
        $normalized = self::normalizeParallax(['strength' => '2rem']);

        self::assertSame(['strength' => '2rem'], $normalized);
    }

    public function testItNormalizesParallaxAsDisabledWhenStrengthIsMissing(): void
    {
        self::assertSame(['strength' => null], self::normalizeParallax(null));
        self::assertSame(['strength' => null], self::normalizeParallax(['strength' => '']));
        self::assertSame(['strength' => null], self::normalizeParallax(['foo' => 'bar']));
    }

    public function testItExposesTheNewOptionFields(): void
    {
        $form = $this->factory->create(SliderSettingsType::class);

        foreach ([
            'arrowsPosition',
            'arrowsVerticalAlign',
            'paginationPosition',
            'paginationStyle',
            'keyboardNavigation',
            'touchSwipe',
            'showProgressBar',
            'lazyLoadMedia',
        ] as $field) {
            self::assertTrue($form->has($field), sprintf('Field "%s" is missing.', $field));
        }
    }

    public function testEmptyDataProvidesDefaultsForNewOptions(): void
    {
        $form = $this->factory->create(SliderSettingsType::class);
        $emptyData = $form->getConfig()->getEmptyData();
        $defaults = $emptyData instanceof \Closure ? $emptyData() : $emptyData;

        self::assertIsArray($defaults);
        self::assertSame('overlay', $defaults['arrowsPosition']);
        self::assertSame('center', $defaults['arrowsVerticalAlign']);
        self::assertSame('bottom-inside', $defaults['paginationPosition']);
        self::assertSame('dots', $defaults['paginationStyle']);
        self::assertFalse($defaults['showProgressBar']);
        self::assertTrue($defaults['keyboardNavigation']);
        self::assertTrue($defaults['touchSwipe']);
        self::assertTrue($defaults['lazyLoadMedia']);
    }

    public function testItAcceptsValidNewOptionValues(): void
    {
        $form = $this->factory->create(SliderSettingsType::class);
        $form->submit([
            'arrowsPosition' => 'outside',
            'arrowsVerticalAlign' => 'bottom',
            'paginationPosition' => 'right',
            'paginationStyle' => 'numbers',
            'keyboardNavigation' => '1',
            'touchSwipe' => '1',
            'showProgressBar' => '1',
            'lazyLoadMedia' => '1',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));

        $data = $form->getData();
        self::assertSame('outside', $data['arrowsPosition']);
        self::assertSame('numbers', $data['paginationStyle']);
        self::assertSame('right', $data['paginationPosition']);
        self::assertTrue($data['showProgressBar']);
    }

    public function testItRejectsUnknownArrowsPosition(): void
    {
        $form = $this->factory->create(SliderSettingsType::class);
        $form->submit(['arrowsPosition' => 'diagonal']);

        self::assertFalse($form->get('arrowsPosition')->isValid());
    }

    public function testLegacySettingsBlobWithoutNewKeysStillWorks(): void
    {
        $legacySettings = [
            'overlay' => false,
            'showTitle' => true,
            'showNavigation' => true,
            'showArrows' => true,
            'slideEffect' => 'slide',
            'speed' => 500,
            'paginationShape' => 'square',
        ];

        $form = $this->factory->create(SliderSettingsType::class, $legacySettings);
        $form->submit(array_merge($legacySettings, ['speed' => '500']));

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));

        $data = $form->getData();
        self::assertSame('square', $data['paginationShape']);
        self::assertArrayHasKey('arrowsPosition', $data);
        self::assertArrayHasKey('paginationStyle', $data);
    }

    /**
     * @param mixed $input
     *
     * @return array{strength: ?string}
     */
    private static function normalizeParallax(mixed $input): array
    {
        $reflection = new ReflectionClass(SliderSettingsType::class);
        $method = $reflection->getMethod('normalizeParallax');
        $method->setAccessible(true);

        /** @var array{strength: ?string} $result */
        $result = $method->invoke(null, $input);

        return $result;
    }
}
