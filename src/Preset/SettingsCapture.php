<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Preset;

use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

/**
 * Serializes an existing slide's/slider's persisted settings into the flat
 * dot-path map used by style presets, so a preset can be bootstrapped from a
 * resource that already looks right.
 */
final class SettingsCapture
{
    /**
     * @return array<string, bool|float|int|string>
     */
    public function fromSlide(Slide $slide): array
    {
        $slideSettings = $slide->getSlideSettings();
        $captured = [];

        $responsive = $slideSettings['responsive'] ?? [];
        if (is_array($responsive)) {
            foreach (['desktop', 'tablet', 'mobile'] as $breakpoint) {
                $breakpointSettings = $responsive[$breakpoint] ?? [];
                if (!is_array($breakpointSettings)) {
                    continue;
                }
                $captured += $this->flatten($breakpointSettings, sprintf('settings.responsive.%s', $breakpoint));
            }
        }

        $linking = $slideSettings['linking'] ?? [];
        if (is_array($linking)) {
            $captured += $this->flatten($linking, 'settings.linking');
        }

        $parallax = $slideSettings['parallax'] ?? [];
        if (is_array($parallax)) {
            $captured += $this->flatten($parallax, 'settings.parallax');
        }

        return $captured;
    }

    /**
     * @return array<string, bool|float|int|string>
     */
    public function fromSlider(Slider $slider): array
    {
        $settings = $slider->getSettings();

        // channelCodes and slideOrder are instance data, not style.
        unset($settings['channelCodes'], $settings['slideOrder']);

        return $this->flatten($settings, 'settings');
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, bool|float|int|string>
     */
    private function flatten(array $values, string $prefix): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = sprintf('%s.%s', $prefix, $key);
            if (is_array($value)) {
                $flat += $this->flatten($value, $path);

                continue;
            }

            if (is_scalar($value)) {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }
}
