<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Renderer;

/**
 * Resolves the slider's STRUCTURAL settings (arrows, pagination, container,
 * effect — everything expressed as classes/markup/vars) into effective
 * per-breakpoint maps: desktop is the base; tablet overlays desktop; mobile
 * overlays tablet. Empty override values inherit. The storefront controller
 * applies the map matching the current viewport (matchMedia), so EVERY
 * setting can differ per breakpoint even when it is not var-driven.
 */
final class SliderStructuralSettings
{
    private const NAV_SHADOWS = [
        'none' => 'none',
        'soft' => '0 3px 10px rgba(15, 23, 42, 0.25)',
        'medium' => '0 6px 16px rgba(15, 23, 42, 0.35)',
        'strong' => '0 10px 26px rgba(15, 23, 42, 0.46)',
        'glow' => '0 0 16px rgba(250, 204, 21, 0.55)',
    ];

    private const PAGINATION_SHADOWS = [
        'none' => 'none',
        'soft' => '0 2px 7px rgba(15, 23, 42, 0.22)',
        'medium' => '0 3px 9px rgba(15, 23, 42, 0.3)',
        'strong' => '0 6px 16px rgba(15, 23, 42, 0.4)',
        'glow' => '0 0 10px rgba(250, 204, 21, 0.55)',
    ];

    private const NAV_SIZES = ['sm' => '1rem', 'md' => '1.5rem', 'lg' => '2rem'];

    private const PAGINATION_SIZES = ['sm' => '0.5rem', 'md' => '0.625rem', 'lg' => '0.8rem'];

    /**
     * @param array<string, mixed> $settings localized slider settings
     *
     * @return array{desktop: array<string, mixed>, tablet: array<string, mixed>, mobile: array<string, mixed>}
     */
    public function maps(array $settings): array
    {
        $autoplay = is_array($settings['autoplay'] ?? null) ? $settings['autoplay'] : [];
        $autoplayEnabled = (bool) ($autoplay['enabled'] ?? $autoplay['active'] ?? false);

        $desktop = [
            'showNavigation' => (bool) ($settings['showNavigation'] ?? true),
            'showArrows' => (bool) ($settings['showArrows'] ?? true),
            'showProgressBar' => (bool) ($settings['showProgressBar'] ?? false) && $autoplayEnabled,
            'containerWidth' => self::stringValue($settings, 'containerWidth', 'content'),
            'slideEffect' => self::stringValue($settings, 'slideEffect', 'slide'),
            'arrowsPosition' => self::stringValue($settings, 'arrowsPosition', 'overlay'),
            'arrowsVerticalAlign' => self::stringValue($settings, 'arrowsVerticalAlign', 'center'),
            'navigationIcon' => self::stringValue($settings, 'navigationIcon', 'chevron'),
            'navigationSize' => $this->resolveSize(self::stringValue($settings, 'navigationSize', '1.5rem'), self::NAV_SIZES),
            'navigationShadow' => $this->resolveShadow(self::stringValue($settings, 'navigationShadow', 'none'), self::NAV_SHADOWS),
            'navigationColor' => self::stringValue($settings, 'navigationColor', 'rgba(250, 204, 21, 1)'),
            'navigationBackgroundColor' => self::stringValue($settings, 'navigationBackgroundColor', 'rgba(17, 24, 39, 0.85)'),
            'paginationStyle' => self::stringValue($settings, 'paginationStyle', 'dots'),
            'paginationPosition' => self::stringValue($settings, 'paginationPosition', 'bottom-inside'),
            'paginationShape' => self::stringValue($settings, 'paginationShape', 'circle'),
            'paginationSize' => $this->resolveSize(self::stringValue($settings, 'paginationSize', '0.625rem'), self::PAGINATION_SIZES),
            'paginationShadow' => $this->resolveShadow(self::stringValue($settings, 'paginationShadow', 'none'), self::PAGINATION_SHADOWS),
            'paginationColor' => self::stringValue($settings, 'paginationColor', 'rgba(250, 204, 21, 0.45)'),
            'paginationActiveColor' => self::stringValue($settings, 'paginationActiveColor', 'rgba(250, 204, 21, 1)'),
        ];

        $responsive = is_array($settings['responsive'] ?? null) ? $settings['responsive'] : [];
        $tablet = $this->overlay($desktop, is_array($responsive['tablet'] ?? null) ? $responsive['tablet'] : []);
        $mobile = $this->overlay($tablet, is_array($responsive['mobile'] ?? null) ? $responsive['mobile'] : []);

        return ['desktop' => $desktop, 'tablet' => $tablet, 'mobile' => $mobile];
    }

    /**
     * @param array<string, mixed> $base effective map of the wider breakpoint
     * @param array<string, mixed> $overrides raw stored overrides
     *
     * @return array<string, mixed>
     */
    private function overlay(array $base, array $overrides): array
    {
        $result = $base;
        foreach ($overrides as $key => $value) {
            if (!array_key_exists($key, $base) || null === $value || '' === $value || !is_scalar($value)) {
                continue;
            }

            $result[$key] = match ($key) {
                'showNavigation', 'showArrows', 'showProgressBar' => '1' === $value || true === $value,
                'navigationSize' => $this->resolveSize((string) $value, self::NAV_SIZES),
                'navigationShadow' => $this->resolveShadow((string) $value, self::NAV_SHADOWS),
                'paginationSize' => $this->resolveSize((string) $value, self::PAGINATION_SIZES),
                'paginationShadow' => $this->resolveShadow((string) $value, self::PAGINATION_SHADOWS),
                default => (string) $value,
            };
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function stringValue(array $settings, string $key, string $default): string
    {
        $value = $settings[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param array<string, string> $presetMap
     */
    private function resolveSize(string $value, array $presetMap): string
    {
        return $presetMap[$value] ?? $value;
    }

    /**
     * @param array<string, string> $presetMap
     */
    private function resolveShadow(string $value, array $presetMap): string
    {
        return $presetMap[$value] ?? self::NAV_SHADOWS['medium'];
    }
}
