<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Form\Type;

use Vanssa\SyliusSliderPlugin\Form\Type\SlideType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class SlideTypeTest extends TestCase
{
    public function testItUsesTextareaWhenRichEditorPluginIsNotInstalled(): void
    {
        self::assertSame(TextareaType::class, SlideType::resolveDescriptionTypeClass());
    }
}
