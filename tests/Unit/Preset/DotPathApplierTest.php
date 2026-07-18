<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Preset;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Preset\DotPathApplier;

final class DotPathApplierTest extends TestCase
{
    public function testAppliesNestedPathsAndStripsPrefix(): void
    {
        $result = (new DotPathApplier())->apply(
            ['channelCodes' => ['WEB']],
            [
                'settings.autoplay.enabled' => true,
                'settings.autoplay.interval' => 5000,
                'settings.speed' => 500,
            ],
            'settings',
        );

        self::assertSame([
            'channelCodes' => ['WEB'],
            'autoplay' => ['enabled' => true, 'interval' => 5000],
            'speed' => 500,
        ], $result);
    }

    public function testWithoutPrefixKeepsFullPath(): void
    {
        $result = (new DotPathApplier())->apply([], ['a.b.c' => 'x']);

        self::assertSame(['a' => ['b' => ['c' => 'x']]], $result);
    }

    public function testOverwritesScalarIntermediatesAndSkipsNonScalars(): void
    {
        $result = (new DotPathApplier())->apply(
            ['autoplay' => 'legacy-string'],
            [
                'autoplay.enabled' => true,
                'bogus' => ['array' => 'skipped'],
            ],
        );

        self::assertSame(['autoplay' => ['enabled' => true]], $result);
    }
}
