<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Form\Type;

use Vanssa\SyliusSliderPlugin\Form\DataTransformer\JsonArrayTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class JsonArrayTransformerTest extends TestCase
{
    public function testItTransformsArrayToJson(): void
    {
        $transformer = new JsonArrayTransformer();

        $json = $transformer->transform(['foo' => 'bar']);

        self::assertStringContainsString('"foo": "bar"', $json);
    }

    public function testItReverseTransformsJsonToArray(): void
    {
        $transformer = new JsonArrayTransformer();

        $data = $transformer->reverseTransform('{"foo":"bar"}');

        self::assertSame(['foo' => 'bar'], $data);
    }

    public function testItThrowsOnInvalidJson(): void
    {
        $transformer = new JsonArrayTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('{foo');
    }
}
