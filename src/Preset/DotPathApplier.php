<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Preset;

/**
 * Applies a flat dot-path => value map (the style-preset settings shape,
 * e.g. "settings.autoplay.enabled" => true) onto a nested array.
 */
final class DotPathApplier
{
    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $dotPathValues
     *
     * @return array<string, mixed>
     */
    public function apply(array $target, array $dotPathValues, string $stripPrefix = ''): array
    {
        foreach ($dotPathValues as $path => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            if ($stripPrefix !== '' && str_starts_with($path, $stripPrefix . '.')) {
                $path = substr($path, strlen($stripPrefix) + 1);
            }

            $segments = array_filter(explode('.', $path), static fn (string $segment): bool => $segment !== '');
            if ($segments === []) {
                continue;
            }

            $cursor = &$target;
            $lastSegment = array_pop($segments);
            foreach ($segments as $segment) {
                if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                    $cursor[$segment] = [];
                }
                $cursor = &$cursor[$segment];
            }
            $cursor[$lastSegment] = $value;
            unset($cursor);
        }

        return $target;
    }
}
