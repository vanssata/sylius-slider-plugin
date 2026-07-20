<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;

#[ORM\Entity(repositoryClass: SlideRepository::class)]
#[ORM\Table(
    name: 'vanssa_sylius_slide',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_876619a677153098', columns: ['code'])],
)]
#[UniqueEntity(fields: ['code'], message: 'This slide code is already in use.')]
class Slide implements ResourceInterface, TranslatableInterface
{
    /** @var list<string> Title, description and typography always apply for a translation, without needing an override flag. */
    private const ALWAYS_APPLIED_RESPONSIVE_FIELDS = ['title', 'description', 'headlineElement', 'headlineFontSize', 'descriptionFontSize', 'buttonFontSize'];

    /** @var list<string> */
    private const LAYOUT_RESPONSIVE_FIELDS = ['contentHorizontalPosition', 'contentVerticalPosition', 'contentTextAlign', 'contentPadding', 'contentMargin', 'contentWidth', 'contentMaxHeight', 'borderRadius', 'customCssClass'];

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

    /** @var Collection<int, Slider> */
    #[ORM\ManyToMany(targetEntity: Slider::class, inversedBy: 'slides')]
    #[ORM\JoinTable(name: 'vanssa_sylius_slide_slider')]
    #[ORM\JoinColumn(name: 'slide_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'slider_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $sliders;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $code = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $name = '';

    #[ORM\Column(name: 'button_label', type: 'string', length: 255, nullable: true)]
    private ?string $buttonLabel = null;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(name: 'product_code', type: 'string', length: 64, nullable: true)]
    private ?string $productCode = null;

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

    #[ORM\Column(name: 'presentation_media', type: 'string', length: 1024, nullable: true)]
    private ?string $presentationMedia = null;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    /** @var array<int, string> */
    #[ORM\Column(name: 'channel_codes', type: 'json')]
    private array $channelCodes = [];

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'slide_settings', type: 'json')]
    private array $slideSettings = [];

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'content_settings', type: 'json')]
    private array $contentSettings = [];

    /** @var Collection<int, SlideTranslation> */
    #[ORM\OneToMany(mappedBy: 'slide', targetEntity: SlideTranslation::class, cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'localeCode')]
    private Collection $translations;

    private ?string $currentLocale = null;

    private ?string $fallbackLocale = null;

    public function __construct()
    {
        $this->sliders = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Slider>
     */
    public function getSliders(): Collection
    {
        return $this->sliders;
    }

    public function addSlider(Slider $slider): void
    {
        if ($this->sliders->contains($slider)) {
            return;
        }

        $this->sliders->add($slider);
        $slider->addSlide($this);
    }

    public function removeSlider(Slider $slider): void
    {
        if (!$this->sliders->removeElement($slider)) {
            return;
        }

        $slider->removeSlide($this);
    }

    // Legacy helper for code paths that still treat relation as single-slider.
    public function getSlider(): ?Slider
    {
        $first = $this->sliders->first();

        return $first instanceof Slider ? $first : null;
    }

    // Legacy helper for code paths that still treat relation as single-slider.
    public function setSlider(?Slider $slider): void
    {
        foreach ($this->sliders as $existingSlider) {
            $this->removeSlider($existingSlider);
        }

        if (null !== $slider) {
            $this->addSlider($slider);
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLocalizedName(string $locale, ?string $fallbackLocale = null): string
    {
        return $this->getTranslation($locale, $fallbackLocale)?->getName() ?: $this->name;
    }

    public function getButtonLabel(): ?string
    {
        return $this->buttonLabel;
    }

    public function setButtonLabel(?string $buttonLabel): void
    {
        $this->buttonLabel = $buttonLabel;
    }

    public function getLocalizedButtonLabel(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isButtonOverrideEnabled() ? $translation->getButtonLabel() : null,
        ) ?? $this->buttonLabel;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getLocalizedUrl(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isButtonOverrideEnabled() ? $translation->getUrl() : null,
        ) ?? $this->url;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): void
    {
        $this->productCode = $productCode;
    }

    public function getSlideCover(): ?string
    {
        return $this->slideCover;
    }

    public function setSlideCover(?string $slideCover): void
    {
        $this->slideCover = $slideCover;
    }

    public function getLocalizedSlideCover(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isMediaOverrideEnabled() ? $translation->getSlideCover() : null,
        ) ?? $this->slideCover;
    }

    public function getSlideCoverMobile(): ?string
    {
        return $this->slideCoverMobile;
    }

    public function setSlideCoverMobile(?string $slideCoverMobile): void
    {
        $this->slideCoverMobile = $slideCoverMobile;
    }

    public function getLocalizedSlideCoverMobile(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isMediaOverrideEnabled() ? $translation->getSlideCoverMobile() : null,
        ) ?? $this->slideCoverMobile;
    }

    public function getSlideCoverTablet(): ?string
    {
        return $this->slideCoverTablet;
    }

    public function setSlideCoverTablet(?string $slideCoverTablet): void
    {
        $this->slideCoverTablet = $slideCoverTablet;
    }

    public function getLocalizedSlideCoverTablet(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isMediaOverrideEnabled() ? $translation->getSlideCoverTablet() : null,
        ) ?? $this->slideCoverTablet;
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

    public function getLocalizedSlideCoverVideo(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isMediaOverrideEnabled() ? $translation->getSlideCoverVideo() : null,
        ) ?? $this->slideCoverVideo;
    }

    public function getLocalizedSlideCoverVideoMobile(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isMediaOverrideEnabled() ? $translation->getSlideCoverVideoMobile() : null,
        ) ?? $this->slideCoverVideoMobile;
    }

    public function getLocalizedSlideCoverVideoTablet(string $locale, ?string $fallbackLocale = null): ?string
    {
        return $this->resolveLocalizedValue(
            $locale,
            $fallbackLocale,
            static fn (SlideTranslation $translation): ?string => $translation->isMediaOverrideEnabled() ? $translation->getSlideCoverVideoTablet() : null,
        ) ?? $this->slideCoverVideoTablet;
    }

    /**
     * Resolves the effective media for a breakpoint: the breakpoint's own
     * video wins, then its image; anything missing falls back to desktop.
     * Returns null when the slide has no media at all.
     *
     * @return array{type: 'video'|'image', src: string}|null
     */
    public function getLocalizedMediaForBreakpoint(string $breakpoint, string $locale, ?string $fallbackLocale = null): ?array
    {
        $video = match ($breakpoint) {
            'mobile' => $this->getLocalizedSlideCoverVideoMobile($locale, $fallbackLocale),
            'tablet' => $this->getLocalizedSlideCoverVideoTablet($locale, $fallbackLocale),
            default => null,
        } ?? $this->getLocalizedSlideCoverVideo($locale, $fallbackLocale);

        if (null !== $video && '' !== $video) {
            return ['type' => 'video', 'src' => $video];
        }

        $image = match ($breakpoint) {
            'mobile' => $this->getLocalizedSlideCoverMobile($locale, $fallbackLocale),
            'tablet' => $this->getLocalizedSlideCoverTablet($locale, $fallbackLocale),
            default => null,
        } ?? $this->getLocalizedSlideCover($locale, $fallbackLocale)
            ?? $this->getLocalizedSlideCoverTablet($locale, $fallbackLocale)
            ?? $this->getLocalizedSlideCoverMobile($locale, $fallbackLocale);

        if (null !== $image && '' !== $image) {
            return ['type' => 'image', 'src' => $image];
        }

        return null;
    }

    /**
     * Groups identical breakpoint media so the template renders each
     * distinct video/image only once, tagged with its breakpoints.
     *
     * @return array<int, array{type: 'video'|'image', src: string, breakpoints: array<int, string>}>
     */
    public function getLocalizedMediaGroups(string $locale, ?string $fallbackLocale = null): array
    {
        $groups = [];

        foreach (['desktop', 'tablet', 'mobile'] as $breakpoint) {
            $media = $this->getLocalizedMediaForBreakpoint($breakpoint, $locale, $fallbackLocale);
            if (null === $media) {
                continue;
            }

            $key = $media['type'] . '|' . $media['src'];
            if (!isset($groups[$key])) {
                $groups[$key] = ['type' => $media['type'], 'src' => $media['src'], 'breakpoints' => []];
            }

            $groups[$key]['breakpoints'][] = $breakpoint;
        }

        return array_values($groups);
    }

    public function getPresentationMedia(): ?string
    {
        return $this->presentationMedia;
    }

    public function setPresentationMedia(?string $presentationMedia): void
    {
        $this->presentationMedia = $presentationMedia;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array<int, string>
     */
    public function getChannelCodes(): array
    {
        return $this->channelCodes;
    }

    /**
     * @param array<int, string> $channelCodes
     */
    public function setChannelCodes(array $channelCodes): void
    {
        $this->channelCodes = array_values(array_unique(array_filter($channelCodes, static fn (mixed $code): bool => is_string($code) && '' !== $code)));
    }

    public function isAvailableForChannel(string $channelCode): bool
    {
        if ([] === $this->channelCodes) {
            return true;
        }

        return in_array($channelCode, $this->channelCodes, true);
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
     * @return array<string, mixed>
     */
    public function getLocalizedSlideSettings(string $locale, ?string $fallbackLocale = null): array
    {
        $base = self::normalizeSlideSettings($this->slideSettings);
        $fallbackOverrides = null;
        $currentOverrides = null;

        if (null !== $fallbackLocale) {
            $fallbackOverrides = $this->resolveTranslationSettingsOverride($fallbackLocale, false);
        }

        $currentOverrides = $this->resolveTranslationSettingsOverride($locale, false);

        $settings = self::mergeLocalizedSettings($base, self::normalizeSlideSettings($fallbackOverrides ?? []));

        return self::mergeLocalizedSettings($settings, self::normalizeSlideSettings($currentOverrides ?? []));
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
    public function getLocalizedContentSettings(string $locale, ?string $fallbackLocale = null): array
    {
        $base = $this->contentSettings;
        $fallbackOverrides = null;
        $currentOverrides = null;

        if (null !== $fallbackLocale) {
            $fallbackOverrides = $this->resolveTranslationSettingsOverride($fallbackLocale, true);
        }

        $currentOverrides = $this->resolveTranslationSettingsOverride($locale, true);

        $settings = self::mergeLocalizedSettings($base, $fallbackOverrides ?? []);

        return self::mergeLocalizedSettings($settings, $currentOverrides ?? []);
    }

    /**
     * @return Collection<int, SlideTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function hasTranslation(TranslationInterface $translation): bool
    {
        if (!$translation instanceof SlideTranslation) {
            return false;
        }

        $localeCode = $translation->getLocaleCode();
        if ('' !== $localeCode && $this->translations->containsKey($localeCode)) {
            return true;
        }

        return $this->translations->contains($translation);
    }

    public function addTranslation(TranslationInterface $translation): void
    {
        if (!$translation instanceof SlideTranslation) {
            throw new \InvalidArgumentException('Expected translation to be instance of SlideTranslation.');
        }

        if ($this->translations->contains($translation)) {
            return;
        }

        $localeCode = $translation->getLocaleCode();
        $translation->setSlide($this);
        if ('' !== $localeCode) {
            $this->translations->set($localeCode, $translation);

            return;
        }

        $this->translations->add($translation);
    }

    public function removeTranslation(TranslationInterface $translation): void
    {
        if (!$translation instanceof SlideTranslation) {
            return;
        }

        $localeCode = $translation->getLocaleCode();
        $wasRemoved = '' !== $localeCode
            ? null !== $this->translations->remove($localeCode)
            : $this->translations->removeElement($translation);

        if (!$wasRemoved) {
            return;
        }

        if ($translation->getSlide() === $this) {
            $translation->setSlide(null);
        }
    }

    public function setCurrentLocale(string $locale): void
    {
        $this->currentLocale = $locale;
    }

    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
    }

    public function getOrCreateTranslation(string $locale): SlideTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocaleCode() === $locale) {
                return $translation;
            }
        }

        $translation = new SlideTranslation();
        $translation->setLocaleCode($locale);
        $translation->setSlideSettings([]);
        $translation->setContentSettings([]);
        $this->addTranslation($translation);

        return $translation;
    }

    public function getTranslation(?string $locale = null, ?string $fallbackLocale = null): TranslationInterface
    {
        $resolvedLocale = $locale ?? $this->currentLocale;
        $resolvedFallbackLocale = $fallbackLocale ?? $this->fallbackLocale;

        if (null !== $resolvedLocale) {
            $translation = $this->findTranslation($resolvedLocale);
            if (null !== $translation) {
                return $translation;
            }
        }

        if (null !== $resolvedFallbackLocale) {
            $translation = $this->findTranslation($resolvedFallbackLocale);
            if (null !== $translation) {
                return $translation;
            }
        }

        if (null !== $resolvedLocale) {
            return $this->getOrCreateTranslation($resolvedLocale);
        }

        if (null !== $resolvedFallbackLocale) {
            return $this->getOrCreateTranslation($resolvedFallbackLocale);
        }

        $first = $this->translations->first();
        if ($first instanceof SlideTranslation) {
            return $first;
        }

        throw new \RuntimeException('Cannot resolve translation locale for slide.');
    }

    private function findTranslation(string $locale): ?SlideTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocaleCode() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Returns the first non-empty value produced by $resolver for the
     * current locale translation, then the fallback locale translation.
     *
     * @param callable(SlideTranslation): ?string $resolver
     */
    private function resolveLocalizedValue(string $locale, ?string $fallbackLocale, callable $resolver): ?string
    {
        $translation = $this->findTranslation($locale);
        if (null !== $translation) {
            $value = $resolver($translation);
            if (null !== $value && '' !== $value) {
                return $value;
            }
        }

        if (null !== $fallbackLocale && $fallbackLocale !== $locale) {
            $translation = $this->findTranslation($fallbackLocale);
            if (null !== $translation) {
                $value = $resolver($translation);
                if (null !== $value && '' !== $value) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $baseSettings
     * @param array<string, mixed> $localizedSettings
     *
     * @return array<string, mixed>
     */
    private static function mergeLocalizedSettings(array $baseSettings, array $localizedSettings): array
    {
        if ([] === $localizedSettings) {
            return $baseSettings;
        }

        $merged = $baseSettings;

        foreach ($localizedSettings as $key => $value) {
            // A null or empty-array localized value means "no override for this
            // key" — fall back to the base/fallback-locale value instead of
            // wiping it (a translated field the admin left blank is stored as
            // null; without this it would clear the inherited title/text).
            if (null === $value || (is_array($value) && [] === $value)) {
                continue;
            }

            if (
                is_string($key) &&
                isset($baseSettings[$key], $localizedSettings[$key]) &&
                is_array($baseSettings[$key]) &&
                is_array($value) &&
                !array_is_list($baseSettings[$key]) &&
                !array_is_list($value)
            ) {
                /** @var array<string, mixed> $baseNested */
                $baseNested = $baseSettings[$key];
                /** @var array<string, mixed> $localizedNested */
                $localizedNested = $value;
                $merged[$key] = self::mergeLocalizedSettings($baseNested, $localizedNested);

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTranslationSettingsOverride(string $locale, bool $contentSettings): ?array
    {
        $translation = $this->findTranslation($locale);
        if (null === $translation) {
            return null;
        }

        if ($contentSettings) {
            $settings = $translation->getContentSettings();

            return [] === $settings ? null : $settings;
        }

        $settings = $translation->getSlideSettings();
        if ([] === $settings) {
            return null;
        }

        return self::buildGranularSettingsOverride($settings, $translation);
    }

    /**
     * Texts & Typography (title, description, headline tag, font sizes) always
     * apply for a translation. Linking and each responsive setting group
     * (Layout / Colors & Surface / Effects / Visibility) only apply when their
     * own override flag is enabled, so an admin can translate e.g. colors
     * without also overriding layout.
     *
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>|null
     */
    private static function buildGranularSettingsOverride(array $settings, SlideTranslation $translation): ?array
    {
        $override = [];

        $linking = $settings['linking'] ?? null;
        if ($translation->isButtonOverrideEnabled() && is_array($linking) && [] !== $linking) {
            $override['linking'] = $linking;
        }

        $responsive = $settings['responsive'] ?? null;
        if (is_array($responsive)) {
            $responsiveOverride = [];

            foreach (['desktop', 'tablet', 'mobile'] as $breakpoint) {
                $breakpointSettings = $responsive[$breakpoint] ?? null;
                if (!is_array($breakpointSettings)) {
                    continue;
                }

                $fields = array_filter(
                    array_intersect_key($breakpointSettings, array_flip(self::ALWAYS_APPLIED_RESPONSIVE_FIELDS)),
                    // Drop null and empty-string values so a blank translated
                    // field never overrides (and wipes) the inherited value.
                    static fn (mixed $value): bool => null !== $value && '' !== $value,
                );

                if ($translation->isLayoutOverrideEnabled()) {
                    $fields += array_intersect_key($breakpointSettings, array_flip(self::LAYOUT_RESPONSIVE_FIELDS));
                }

                if ($translation->isColorsOverrideEnabled()) {
                    $fields += array_intersect_key($breakpointSettings, array_flip(self::COLORS_RESPONSIVE_FIELDS));
                }

                if ($translation->isEffectsOverrideEnabled()) {
                    $fields += array_intersect_key($breakpointSettings, array_flip(self::EFFECTS_RESPONSIVE_FIELDS));
                }

                if ($translation->isVisibilityOverrideEnabled()) {
                    $fields += array_intersect_key($breakpointSettings, array_flip(self::VISIBILITY_RESPONSIVE_FIELDS));
                }

                if ([] !== $fields) {
                    $responsiveOverride[$breakpoint] = $fields;
                }
            }

            if ([] !== $responsiveOverride) {
                $override['responsive'] = $responsiveOverride;
            }
        }

        return [] === $override ? null : $override;
    }

    /**
     * Keep only responsive/linking/parallax settings for slide configuration.
     *
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

        if (isset($slideSettings['parallax']) && is_array($slideSettings['parallax'])) {
            $strength = $slideSettings['parallax']['strength'] ?? null;
            if (is_string($strength) && trim($strength) !== '') {
                $normalized['parallax'] = ['strength' => trim($strength)];
            }
        }

        if (isset($slideSettings['video']) && is_array($slideSettings['video'])) {
            $playback = $slideSettings['video']['playback'] ?? null;
            if (in_array($playback, ['autoplay', 'click'], true)) {
                $normalized['video'] = ['playback' => $playback];
            }
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
