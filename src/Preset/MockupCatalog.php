<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Preset;

use Vanssa\SyliusSliderPlugin\Entity\StylePreset;

/**
 * Bundled preset mockup images. A deliberate static list (not a directory
 * scan) so the catalog is deterministic and testable. Files live in
 * Resources/public/preset-mockups and are published by assets:install under
 * /bundles/vanssasyliussliderplugin/preset-mockups.
 */
final class MockupCatalog
{
    private const PATH_PREFIX = '/bundles/vanssasyliussliderplugin/preset-mockups';

    /**
     * @return list<array{key: string, label: string, path: string}>
     */
    public function all(): array
    {
        $entries = [];
        foreach ([
            'dark' => 'Dark',
            'light' => 'Light',
            'with-text' => 'With Text',
            'center-bold' => 'Center Bold',
            'minimal' => 'Minimal',
            'gradient' => 'Gradient',
            'glass' => 'Glass',
        ] as $key => $label) {
            $entries[] = [
                'key' => $key,
                'label' => $label,
                'path' => sprintf('%s/%s.svg', self::PATH_PREFIX, $key),
            ];
        }

        return $entries;
    }

    public function pathFor(string $key): ?string
    {
        foreach ($this->all() as $entry) {
            if ($entry['key'] === $key) {
                return $entry['path'];
            }
        }

        return null;
    }

    /**
     * Config presets carry no mockup of their own — map the shipped ones to a
     * fitting bundled image so they still render nicely in the gallery.
     */
    public function defaultMockupFor(string $type, string $name): ?string
    {
        $map = [
            StylePreset::TYPE_SLIDE => [
                'hero_dark' => 'dark',
                'clean_light' => 'light',
                'minimal' => 'minimal',
                'bold_center' => 'center-bold',
                'split_left_light' => 'light',
                'gradient_overlay' => 'gradient',
                'glass_card' => 'glass',
                'promo_badge_right' => 'with-text',
            ],
            StylePreset::TYPE_SLIDER => [
                'classic_arrows' => 'with-text',
                'minimal_fade' => 'minimal',
                'autoplay_showcase' => 'gradient',
                'fullscreen_hero' => 'dark',
                'compact_banner' => 'light',
                'parallax_showcase' => 'glass',
            ],
        ];

        $key = $map[$type][$name] ?? null;

        return $key === null ? null : $this->pathFor($key);
    }
}
