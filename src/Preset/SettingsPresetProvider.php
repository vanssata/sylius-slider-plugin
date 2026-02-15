<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Preset;

final class SettingsPresetProvider
{
    /**
     * @param array<string, mixed> $sliderPresets
     */
    public function __construct(
        private readonly array $sliderPresets,
    ) {
    }

    /**
     * @param array<int, scalar> $fallback
     *
     * @return array<int, scalar>
     */
    public function values(string $section, string $name, array $fallback): array
    {
        $values = $this->resolve(sprintf('%s.%s.values', $section, $name), null);
        if (!is_array($values) || [] === $values) {
            return $fallback;
        }

        $normalized = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $normalized[] = $value;
        }

        return [] !== $normalized ? array_values($normalized) : $fallback;
    }

    public function default(string $section, string $name, mixed $fallback): mixed
    {
        $value = $this->resolve(sprintf('%s.%s.default', $section, $name), $fallback);

        return is_scalar($value) ? $value : $fallback;
    }

    /**
     * @param array<int, scalar> $values
     */
    public function safeDefault(string $section, string $name, mixed $fallback, array $values): mixed
    {
        $default = $this->default($section, $name, $fallback);
        if (in_array($default, $values, true)) {
            return $default;
        }

        if ([] === $values) {
            return $fallback;
        }

        return $values[0];
    }

    /**
     * @param array<int, string> $fallback
     *
     * @return array<int, string>
     */
    public function stringList(string $path, array $fallback = []): array
    {
        $value = $this->resolve($path, $fallback);
        if (!is_array($value)) {
            return $fallback;
        }

        $list = [];
        foreach ($value as $item) {
            if (is_string($item) && '' !== trim($item)) {
                $list[] = $item;
            }
        }

        return [] !== $list ? array_values($list) : $fallback;
    }

    public function bool(string $path, bool $fallback = false): bool
    {
        $value = $this->resolve($path, $fallback);

        return is_bool($value) ? $value : $fallback;
    }

    private function resolve(string $path, mixed $fallback): mixed
    {
        $parts = explode('.', $path);
        $current = $this->sliderPresets;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $fallback;
            }

            $current = $current[$part];
        }

        return $current;
    }
}
