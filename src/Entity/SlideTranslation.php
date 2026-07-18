<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;
use Webmozart\Assert\Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'vanssa_sylius_slide_translation',
    indexes: [new ORM\Index(name: 'idx_95a50d9998e46b87', columns: ['slide_id'])],
)]
#[ORM\UniqueConstraint(name: 'uniq_slide_locale', columns: ['slide_id', 'locale_code'])]
class SlideTranslation implements ResourceInterface, TranslationInterface
{
    /** @var list<string> */
    private const LAYOUT_RESPONSIVE_FIELDS = ['contentHorizontalPosition', 'contentVerticalPosition', 'contentTextAlign', 'contentPadding', 'contentMargin', 'borderRadius', 'customCssClass'];

    /** @var list<string> */
    private const COLORS_RESPONSIVE_FIELDS = ['textColor', 'headlineColor', 'descriptionColor', 'backgroundColor', 'mediaOverlayColor'];

    /** @var list<string> */
    private const EFFECTS_RESPONSIVE_FIELDS = ['contentAnimation', 'animationDuration', 'animationDelay', 'backgroundBlurPreset', 'enableTextBlur', 'contentBlurStrength'];

    /** @var list<string> */
    private const VISIBILITY_RESPONSIVE_FIELDS = ['hideTitle', 'hideDescription', 'hideButton'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Slide::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'slide_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Slide $slide = null;

    #[ORM\Column(name: 'locale_code', type: 'string', length: 16)]
    private string $localeCode = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: 'button_label', type: 'string', length: 255, nullable: true)]
    private ?string $buttonLabel = null;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(name: 'slide_cover', type: 'string', length: 1024, nullable: true)]
    private ?string $slideCover = null;

    #[ORM\Column(name: 'slide_cover_mobile', type: 'string', length: 1024, nullable: true)]
    private ?string $slideCoverMobile = null;

    #[ORM\Column(name: 'slide_cover_tablet', type: 'string', length: 1024, nullable: true)]
    private ?string $slideCoverTablet = null;

    #[ORM\Column(name: 'slide_cover_video', type: 'string', length: 1024, nullable: true)]
    private ?string $slideCoverVideo = null;

    #[ORM\Column(name: 'slide_cover_video_mobile', type: 'string', length: 1024, nullable: true)]
    private ?string $slideCoverVideoMobile = null;

    #[ORM\Column(name: 'slide_cover_video_tablet', type: 'string', length: 1024, nullable: true)]
    private ?string $slideCoverVideoTablet = null;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'content_settings', type: 'json')]
    private array $contentSettings = [];

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'slide_settings', type: 'json')]
    private array $slideSettings = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlide(): ?Slide
    {
        return $this->slide;
    }

    public function setSlide(?Slide $slide): void
    {
        $this->slide = $slide;
    }

    public function getTranslatable(): Slide
    {
        $slide = $this->slide;
        Assert::notNull($slide);

        return $slide;
    }

    public function setTranslatable(?TranslatableInterface $translatable): void
    {
        if (null !== $translatable && !$translatable instanceof Slide) {
            throw new \InvalidArgumentException('Expected translatable to be instance of Slide.');
        }

        $this->setSlide($translatable);
    }

    public function getLocale(): ?string
    {
        return '' === $this->localeCode ? null : $this->localeCode;
    }

    public function setLocale(?string $locale): void
    {
        $this->localeCode = (string) ($locale ?? '');
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(string $localeCode): void
    {
        $this->localeCode = $localeCode;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getButtonLabel(): ?string
    {
        return $this->buttonLabel;
    }

    public function setButtonLabel(?string $buttonLabel): void
    {
        $this->buttonLabel = $buttonLabel;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getSlideCover(): ?string
    {
        return $this->slideCover;
    }

    public function setSlideCover(?string $slideCover): void
    {
        $this->slideCover = $slideCover;
    }

    public function getSlideCoverMobile(): ?string
    {
        return $this->slideCoverMobile;
    }

    public function setSlideCoverMobile(?string $slideCoverMobile): void
    {
        $this->slideCoverMobile = $slideCoverMobile;
    }

    public function getSlideCoverTablet(): ?string
    {
        return $this->slideCoverTablet;
    }

    public function setSlideCoverTablet(?string $slideCoverTablet): void
    {
        $this->slideCoverTablet = $slideCoverTablet;
    }

    public function getSlideCoverVideo(): ?string
    {
        return $this->slideCoverVideo;
    }

    public function setSlideCoverVideo(?string $slideCoverVideo): void
    {
        $this->slideCoverVideo = $slideCoverVideo;
    }

    public function getSlideCoverVideoMobile(): ?string
    {
        return $this->slideCoverVideoMobile;
    }

    public function setSlideCoverVideoMobile(?string $slideCoverVideoMobile): void
    {
        $this->slideCoverVideoMobile = $slideCoverVideoMobile;
    }

    public function getSlideCoverVideoTablet(): ?string
    {
        return $this->slideCoverVideoTablet;
    }

    public function setSlideCoverVideoTablet(?string $slideCoverVideoTablet): void
    {
        $this->slideCoverVideoTablet = $slideCoverVideoTablet;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentSettings(): array
    {
        return $this->contentSettings;
    }

    /**
     * @param array<string, mixed> $contentSettings
     */
    public function setContentSettings(array $contentSettings): void
    {
        $this->contentSettings = $contentSettings;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSlideSettings(): array
    {
        return $this->slideSettings;
    }

    /**
     * @param array<string, mixed> $slideSettings
     */
    public function setSlideSettings(array $slideSettings): void
    {
        $this->slideSettings = self::normalizeSlideSettings($slideSettings);
    }

    /**
     * @param array<string, bool> $overrides
     */
    public function setOverrides(array $overrides): void
    {
        $this->slideSettings['overrides'] = [
            'button' => (bool) ($overrides['button'] ?? false),
            'media' => (bool) ($overrides['media'] ?? false),
            'settings' => (bool) ($overrides['settings'] ?? false),
            'layout' => (bool) ($overrides['layout'] ?? false),
            'colors' => (bool) ($overrides['colors'] ?? false),
            'effects' => (bool) ($overrides['effects'] ?? false),
            'visibility' => (bool) ($overrides['visibility'] ?? false),
        ];
    }

    /**
     * Legacy translations (saved before override flags existed) keep their
     * previous behavior: an override counts as enabled when it has data.
     */
    public function isButtonOverrideEnabled(): bool
    {
        $flag = $this->overrideFlag('button');
        if (null !== $flag) {
            return $flag;
        }

        if (null !== $this->buttonLabel || null !== $this->url) {
            return true;
        }

        $linking = $this->slideSettings['linking'] ?? null;

        return is_array($linking) && [] !== array_filter($linking, static fn (mixed $value): bool => is_array($value) ? [] !== $value : null !== $value);
    }

    public function isMediaOverrideEnabled(): bool
    {
        $flag = $this->overrideFlag('media');
        if (null !== $flag) {
            return $flag;
        }

        return null !== $this->slideCover ||
            null !== $this->slideCoverMobile ||
            null !== $this->slideCoverTablet ||
            null !== $this->slideCoverVideo ||
            null !== $this->slideCoverVideoMobile ||
            null !== $this->slideCoverVideoTablet;
    }

    public function isSettingsOverrideEnabled(): bool
    {
        $flag = $this->overrideFlag('settings');
        if (null !== $flag) {
            return $flag;
        }

        foreach (['linking', 'responsive'] as $key) {
            $section = $this->slideSettings[$key] ?? null;
            if (is_array($section) && [] !== array_filter($section, static fn (mixed $value): bool => is_array($value) ? [] !== $value : null !== $value)) {
                return true;
            }
        }

        return false;
    }

    public function isLayoutOverrideEnabled(): bool
    {
        return $this->granularOverrideFlag('layout', self::LAYOUT_RESPONSIVE_FIELDS);
    }

    public function isColorsOverrideEnabled(): bool
    {
        return $this->granularOverrideFlag('colors', self::COLORS_RESPONSIVE_FIELDS);
    }

    public function isEffectsOverrideEnabled(): bool
    {
        return $this->granularOverrideFlag('effects', self::EFFECTS_RESPONSIVE_FIELDS);
    }

    public function isVisibilityOverrideEnabled(): bool
    {
        return $this->granularOverrideFlag('visibility', self::VISIBILITY_RESPONSIVE_FIELDS);
    }

    /**
     * Falls back to the legacy combined "settings" flag, then to plain data
     * presence, for translations saved before display settings were split
     * per accordion section.
     *
     * @param list<string> $fields
     */
    private function granularOverrideFlag(string $name, array $fields): bool
    {
        $flag = $this->overrideFlag($name);
        if (null !== $flag) {
            return $flag;
        }

        $legacy = $this->overrideFlag('settings');
        if (null !== $legacy) {
            return $legacy;
        }

        return $this->hasResponsiveFieldData($fields);
    }

    /**
     * @param list<string> $fields
     */
    private function hasResponsiveFieldData(array $fields): bool
    {
        $responsive = $this->slideSettings['responsive'] ?? null;
        if (!is_array($responsive)) {
            return false;
        }

        foreach (['desktop', 'tablet', 'mobile'] as $breakpoint) {
            $breakpointSettings = $responsive[$breakpoint] ?? null;
            if (!is_array($breakpointSettings)) {
                continue;
            }

            foreach ($fields as $field) {
                $value = $breakpointSettings[$field] ?? null;
                if (is_array($value) ? [] !== $value : null !== $value) {
                    return true;
                }
            }
        }

        return false;
    }

    private function overrideFlag(string $name): ?bool
    {
        $overrides = $this->slideSettings['overrides'] ?? null;
        if (!is_array($overrides) || !array_key_exists($name, $overrides)) {
            return null;
        }

        return (bool) $overrides[$name];
    }

    /**
     * @param array<string, mixed> $slideSettings
     *
     * @return array<string, mixed>
     */
    private static function normalizeSlideSettings(array $slideSettings): array
    {
        $normalized = [];

        if (isset($slideSettings['linking']) && is_array($slideSettings['linking'])) {
            $normalized['linking'] = $slideSettings['linking'];
        }

        if (isset($slideSettings['overrides']) && is_array($slideSettings['overrides'])) {
            $normalized['overrides'] = [
                'button' => (bool) ($slideSettings['overrides']['button'] ?? false),
                'media' => (bool) ($slideSettings['overrides']['media'] ?? false),
                'settings' => (bool) ($slideSettings['overrides']['settings'] ?? false),
                'layout' => (bool) ($slideSettings['overrides']['layout'] ?? false),
                'colors' => (bool) ($slideSettings['overrides']['colors'] ?? false),
                'effects' => (bool) ($slideSettings['overrides']['effects'] ?? false),
                'visibility' => (bool) ($slideSettings['overrides']['visibility'] ?? false),
            ];
        }

        $responsive = [];
        if (isset($slideSettings['responsive']) && is_array($slideSettings['responsive'])) {
            $responsive = $slideSettings['responsive'];
        }

        $normalized['responsive'] = [
            'desktop' => isset($responsive['desktop']) && is_array($responsive['desktop']) ? $responsive['desktop'] : [],
            'tablet' => isset($responsive['tablet']) && is_array($responsive['tablet']) ? $responsive['tablet'] : [],
            'mobile' => isset($responsive['mobile']) && is_array($responsive['mobile']) ? $responsive['mobile'] : [],
        ];

        return $normalized;
    }
}
