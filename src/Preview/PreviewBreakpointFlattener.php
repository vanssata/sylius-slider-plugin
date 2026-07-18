<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Preview;

/**
 * The admin previews render the shop markup inside a turbo-frame in the
 * admin page, so real CSS media queries always see the admin viewport and
 * the shop's matchMedia-driven Stimulus controller never runs — the tablet
 * and mobile variants could never show. Instead, the preview controllers
 * bake the requested breakpoint in server-side: the breakpoint's cascaded
 * override values (desktop -> tablet -> mobile, empty values inherit) are
 * flattened onto the base settings and the responsive variants are cleared,
 * so the "desktop" render path shows exactly what the requested breakpoint
 * would look like in the shop.
 */
final class PreviewBreakpointFlattener
{
    public const BREAKPOINTS = ['desktop', 'tablet', 'mobile'];

    public static function normalize(string $breakpoint): string
    {
        return in_array($breakpoint, self::BREAKPOINTS, true) ? $breakpoint : 'desktop';
    }

    /**
     * Slider settings: breakpoint overrides live in settings['responsive']
     * [tablet|mobile] and overlay the top-level (desktop) keys.
     *
     * @param array<string, mixed> $settings localized slider settings
     *
     * @return array<string, mixed>
     */
    public function flattenSliderSettings(array $settings, string $breakpoint): array
    {
        $breakpoint = self::normalize($breakpoint);
        $responsive = is_array($settings['responsive'] ?? null) ? $settings['responsive'] : [];

        if ('desktop' !== $breakpoint) {
            $settings = self::overlay($settings, is_array($responsive['tablet'] ?? null) ? $responsive['tablet'] : []);
        }

        if ('mobile' === $breakpoint) {
            $settings = self::overlay($settings, is_array($responsive['mobile'] ?? null) ? $responsive['mobile'] : []);
        }

        $settings['responsive'] = [];

        return $settings;
    }

    /**
     * Slide settings: per-breakpoint values live in settings['responsive']
     * [desktop|tablet|mobile]; the effective cascade replaces the desktop
     * variant and the narrower ones are cleared.
     *
     * @param array<string, mixed> $settings localized slide settings
     *
     * @return array<string, mixed>
     */
    public function flattenSlideSettings(array $settings, string $breakpoint): array
    {
        $breakpoint = self::normalize($breakpoint);
        $responsive = is_array($settings['responsive'] ?? null) ? $settings['responsive'] : [];
        $effective = is_array($responsive['desktop'] ?? null) ? $responsive['desktop'] : [];

        if ('desktop' !== $breakpoint) {
            $effective = self::overlay($effective, is_array($responsive['tablet'] ?? null) ? $responsive['tablet'] : []);
        }

        if ('mobile' === $breakpoint) {
            $effective = self::overlay($effective, is_array($responsive['mobile'] ?? null) ? $responsive['mobile'] : []);
        }

        $settings['responsive'] = ['desktop' => $effective, 'tablet' => [], 'mobile' => []];

        return $settings;
    }

    /**
     * Same inherit semantics as the stored overrides everywhere else:
     * '' / null / [] mean "inherit from the wider breakpoint".
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function overlay(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (null === $value || '' === $value || [] === $value) {
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
