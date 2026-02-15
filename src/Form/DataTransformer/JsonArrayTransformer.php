<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class JsonArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if (!\is_array($value)) {
            return '{}';
        }

        try {
            return (string) json_encode($value, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        } catch (\JsonException $exception) {
            throw new TransformationFailedException('Cannot encode settings array to JSON.', 0, $exception);
        }
    }

    public function reverseTransform(mixed $value): array
    {
        if (!\is_string($value) || '' === trim($value)) {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TransformationFailedException('Invalid JSON.', 0, $exception);
        }

        if (!\is_array($decoded)) {
            throw new TransformationFailedException('JSON must decode to an object or array.');
        }

        return $decoded;
    }
}
