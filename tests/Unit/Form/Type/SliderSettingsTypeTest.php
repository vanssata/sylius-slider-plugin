<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Form\Type;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vanssa\SyliusSliderPlugin\Form\Type\Settings\SliderSettingsType;

final class SliderSettingsTypeTest extends TestCase
{
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
