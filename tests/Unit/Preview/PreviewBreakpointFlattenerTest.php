<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Preview;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Preview\PreviewBreakpointFlattener;

final class PreviewBreakpointFlattenerTest extends TestCase
{
    private PreviewBreakpointFlattener $flattener;

    protected function setUp(): void
    {
        $this->flattener = new PreviewBreakpointFlattener();
    }

    public function testNormalizeFallsBackToDesktop(): void
    {
        self::assertSame('tablet', PreviewBreakpointFlattener::normalize('tablet'));
        self::assertSame('desktop', PreviewBreakpointFlattener::normalize('bogus'));
        self::assertSame('desktop', PreviewBreakpointFlattener::normalize(''));
    }

    public function testSliderDesktopKeepsBaseAndClearsResponsive(): void
    {
        $settings = [
            'paginationStyle' => 'dots',
            'responsive' => ['tablet' => ['paginationStyle' => 'lines']],
        ];

        $flattened = $this->flattener->flattenSliderSettings($settings, 'desktop');

        self::assertSame('dots', $flattened['paginationStyle']);
        self::assertSame([], $flattened['responsive']);
    }

    public function testSliderTabletOverlaysTopLevelKeys(): void
    {
        $settings = [
            'paginationStyle' => 'dots',
            'showArrows' => true,
            'responsive' => [
                'tablet' => ['paginationStyle' => 'lines', 'showArrows' => ''],
                'mobile' => ['paginationStyle' => 'numbers'],
            ],
        ];

        $flattened = $this->flattener->flattenSliderSettings($settings, 'tablet');

        self::assertSame('lines', $flattened['paginationStyle']);
        // '' means inherit — the desktop value must survive.
        self::assertTrue($flattened['showArrows']);
        self::assertSame([], $flattened['responsive']);
    }

    public function testSliderMobileCascadesThroughTablet(): void
    {
        $settings = [
            'paginationStyle' => 'dots',
            'navigationIcon' => 'chevron',
            'responsive' => [
                'tablet' => ['navigationIcon' => 'angle'],
                'mobile' => ['paginationStyle' => 'lines'],
            ],
        ];

        $flattened = $this->flattener->flattenSliderSettings($settings, 'mobile');

        // Mobile inherits the tablet override for keys it does not set.
        self::assertSame('angle', $flattened['navigationIcon']);
        self::assertSame('lines', $flattened['paginationStyle']);
    }

    public function testSlideTabletReplacesDesktopVariant(): void
    {
        $settings = [
            'parallax' => ['enabled' => true],
            'responsive' => [
                'desktop' => ['headlineFontSize' => '3rem', 'contentTextAlign' => 'start'],
                'tablet' => ['headlineFontSize' => '1.5rem', 'contentTextAlign' => null],
                'mobile' => ['headlineFontSize' => '1rem'],
            ],
        ];

        $flattened = $this->flattener->flattenSlideSettings($settings, 'tablet');

        self::assertSame('1.5rem', $flattened['responsive']['desktop']['headlineFontSize']);
        // null means inherit — the desktop value must survive.
        self::assertSame('start', $flattened['responsive']['desktop']['contentTextAlign']);
        self::assertSame([], $flattened['responsive']['tablet']);
        self::assertSame([], $flattened['responsive']['mobile']);
        // Non-responsive keys are untouched.
        self::assertSame(['enabled' => true], $flattened['parallax']);
    }

    public function testSlideMobileCascadesThroughTablet(): void
    {
        $settings = [
            'responsive' => [
                'desktop' => ['headlineFontSize' => '3rem', 'contentTextAlign' => 'start'],
                'tablet' => ['headlineFontSize' => '1.5rem'],
                'mobile' => ['contentTextAlign' => 'center'],
            ],
        ];

        $flattened = $this->flattener->flattenSlideSettings($settings, 'mobile');

        self::assertSame('1.5rem', $flattened['responsive']['desktop']['headlineFontSize']);
        self::assertSame('center', $flattened['responsive']['desktop']['contentTextAlign']);
    }
}
