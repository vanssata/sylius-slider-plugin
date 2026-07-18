<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Preset;

use Vanssa\SyliusSliderPlugin\Entity\StylePreset;
use Vanssa\SyliusSliderPlugin\Repository\StylePresetRepository;

/**
 * Exposes the one-click style presets to the admin preview panels and the
 * creation gallery: config-defined presets (vanssa_sylius_slider.style_presets)
 * merged with admin-managed database presets — a database preset with the same
 * code overrides the config one. Each preset's dot-path settings
 * ("settings.responsive.desktop.textColor") are converted into the bracket
 * form-field name the preset applier fills client-side
 * ("slide[settings][responsive][desktop][textColor]").
 */
final class StylePresetProvider
{
    /** @var array<string, array<string, array{label: string, fields: array<string, bool|float|int|string>, source: string, mockup: string|null, slideIds: list<int>}>> */
    private array $memoized = [];

    /**
     * @param array<string, array<string, array<string, mixed>>> $stylePresets
     */
    public function __construct(
        private readonly array $stylePresets,
        private readonly StylePresetRepository $stylePresetRepository,
        private readonly MockupCatalog $mockupCatalog,
    ) {
    }

    /**
     * @return array<string, array{label: string, fields: array<string, bool|float|int|string>, source: string, mockup: string|null, slideIds: list<int>}>
     */
    public function slidePresets(): array
    {
        return $this->build(StylePreset::TYPE_SLIDE);
    }

    /**
     * @return array<string, array{label: string, fields: array<string, bool|float|int|string>, source: string, mockup: string|null, slideIds: list<int>}>
     */
    public function sliderPresets(): array
    {
        return $this->build(StylePreset::TYPE_SLIDER);
    }

    /**
     * @return array<string, array{label: string, fields: array<string, bool|float|int|string>, source: string, mockup: string|null, slideIds: list<int>}>
     */
    private function build(string $formRoot): array
    {
        if (isset($this->memoized[$formRoot])) {
            return $this->memoized[$formRoot];
        }

        $presets = [];

        foreach ($this->stylePresets[$formRoot] ?? [] as $name => $preset) {
            $settings = \is_array($preset['settings'] ?? null) ? $preset['settings'] : [];
            $label = $preset['label'] ?? null;

            $presets[$name] = [
                'label' => \is_string($label) && '' !== $label ? $label : $name,
                'fields' => $this->toFields($formRoot, $settings),
                'source' => 'config',
                'mockup' => $this->mockupCatalog->defaultMockupFor($formRoot, (string) $name),
                'slideIds' => [],
            ];
        }

        foreach ($this->stylePresetRepository->findEnabledByType($formRoot) as $preset) {
            $slideIds = [];
            foreach ($preset->getSlides() as $slide) {
                $id = $slide->getId();
                if (null !== $id) {
                    $slideIds[] = $id;
                }
            }

            $presets[$preset->getCode()] = [
                'label' => $preset->getLabel() !== '' ? $preset->getLabel() : $preset->getCode(),
                'fields' => $this->toFields($formRoot, $preset->getSettings()),
                'source' => 'database',
                'mockup' => $preset->getMockupImage(),
                'slideIds' => $slideIds,
            ];
        }

        return $this->memoized[$formRoot] = $presets;
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, bool|float|int|string>
     */
    private function toFields(string $formRoot, array $settings): array
    {
        $fields = [];
        foreach ($settings as $dotPath => $value) {
            if (!\is_scalar($value)) {
                continue;
            }

            $fields[$this->toFieldName($formRoot, (string) $dotPath)] = $value;
        }

        return $fields;
    }

    private function toFieldName(string $formRoot, string $dotPath): string
    {
        $name = $formRoot;
        foreach (explode('.', $dotPath) as $segment) {
            $name .= '[' . $segment . ']';
        }

        return $name;
    }
}
