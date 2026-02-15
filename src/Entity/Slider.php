<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Entity;

use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;

#[ORM\Entity(repositoryClass: SliderRepository::class)]
#[ORM\Table(
    name: 'vanssa_sylius_slider',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_844454b177153098', columns: ['code'])]
)]
class Slider implements ResourceInterface, TranslatableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $code = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $settings = [];

    /**
     * @var Collection<int, Slide>
     */
    #[ORM\ManyToMany(targetEntity: Slide::class, mappedBy: 'sliders', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $slides;

    /**
     * @var Collection<int, SliderTranslation>
     */
    #[ORM\OneToMany(mappedBy: 'slider', targetEntity: SliderTranslation::class, cascade: ['persist', 'remove'], orphanRemoval: true, indexBy: 'localeCode')]
    private Collection $translations;

    private ?string $currentLocale = null;

    private ?string $fallbackLocale = null;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
        $translation = $this->getTranslation($locale, $fallbackLocale);

        return $translation?->getName() ?: $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
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
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function setSettings(array $settings): void
    {
        if (!array_key_exists('slideOrder', $settings) && array_key_exists('slideOrder', $this->settings)) {
            $settings['slideOrder'] = $this->settings['slideOrder'];
        }

        $this->settings = $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLocalizedSettings(string $locale, ?string $fallbackLocale = null): array
    {
        return $this->settings;
    }

    public function isAvailableForChannel(string $channelCode, string $locale, ?string $fallbackLocale = null): bool
    {
        $settings = $this->getLocalizedSettings($locale, $fallbackLocale);
        $channels = $settings['channelCodes'] ?? [];

        if (!is_array($channels) || [] === $channels) {
            return true;
        }

        return in_array($channelCode, $channels, true);
    }

    /**
     * @return Collection<int, Slide>
     */
    public function getSlides(): Collection
    {
        return $this->slides;
    }

    public function addSlide(Slide $slide): void
    {
        if ($this->slides->contains($slide)) {
            return;
        }

        $this->slides->add($slide);
        $slide->addSlider($this);
    }

    public function removeSlide(Slide $slide): void
    {
        if (!$this->slides->removeElement($slide)) {
            return;
        }

        $slide->removeSlider($this);

        $slideId = $slide->getId();
        if (null !== $slideId) {
            $this->setSlideOrder(array_values(array_filter(
                $this->getSlideOrder(),
                static fn (int $id): bool => $id !== $slideId,
            )));
        }
    }

    /**
     * @return array<int, Slide>
     */
    public function getOrderedSlides(): array
    {
        /** @var array<int, Slide> $slides */
        $slides = $this->slides->toArray();
        $order = array_flip($this->getSlideOrder());

        usort($slides, static function (Slide $left, Slide $right) use ($order): int {
            $leftId = $left->getId();
            $rightId = $right->getId();

            $leftWeight = null !== $leftId && array_key_exists($leftId, $order) ? $order[$leftId] : null;
            $rightWeight = null !== $rightId && array_key_exists($rightId, $order) ? $order[$rightId] : null;

            if (null !== $leftWeight && null !== $rightWeight) {
                return $leftWeight <=> $rightWeight;
            }

            if (null !== $leftWeight) {
                return -1;
            }

            if (null !== $rightWeight) {
                return 1;
            }

            if ($left->getPosition() !== $right->getPosition()) {
                return $left->getPosition() <=> $right->getPosition();
            }

            return ($leftId ?? 0) <=> ($rightId ?? 0);
        });

        return $slides;
    }

    /**
     * @return array<int, int>
     */
    public function getSlideOrder(): array
    {
        $order = $this->settings['slideOrder'] ?? [];
        if (!is_array($order)) {
            return [];
        }

        $normalized = [];
        foreach ($order as $slideId) {
            if (!is_int($slideId) && !is_string($slideId)) {
                continue;
            }

            $id = (int) $slideId;
            if ($id <= 0 || in_array($id, $normalized, true)) {
                continue;
            }

            $normalized[] = $id;
        }

        return $normalized;
    }

    /**
     * @param array<int, int|string> $slideOrder
     */
    public function setSlideOrder(array $slideOrder): void
    {
        $normalized = [];
        foreach ($slideOrder as $slideId) {
            $id = (int) $slideId;
            if ($id <= 0 || in_array($id, $normalized, true)) {
                continue;
            }

            $normalized[] = $id;
        }

        if ([] === $normalized) {
            unset($this->settings['slideOrder']);

            return;
        }

        $this->settings['slideOrder'] = $normalized;
    }

    /**
     * @return Collection<int, SliderTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function hasTranslation(TranslationInterface $translation): bool
    {
        if (!$translation instanceof SliderTranslation) {
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
        if (!$translation instanceof SliderTranslation) {
            throw new \InvalidArgumentException('Expected translation to be instance of SliderTranslation.');
        }

        if ($this->translations->contains($translation)) {
            return;
        }

        $localeCode = $translation->getLocaleCode();
        $translation->setSlider($this);
        if ('' !== $localeCode) {
            $this->translations->set($localeCode, $translation);

            return;
        }

        $this->translations->add($translation);
    }

    public function removeTranslation(TranslationInterface $translation): void
    {
        if (!$translation instanceof SliderTranslation) {
            return;
        }

        $localeCode = $translation->getLocaleCode();
        $wasRemoved = '' !== $localeCode
            ? null !== $this->translations->remove($localeCode)
            : $this->translations->removeElement($translation);

        if (!$wasRemoved) {
            return;
        }

        if ($translation->getSlider() === $this) {
            $translation->setSlider(null);
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

    public function getOrCreateTranslation(string $locale): SliderTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocaleCode() === $locale) {
                return $translation;
            }
        }

        $translation = new SliderTranslation();
        $translation->setLocaleCode($locale);
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
        if ($first instanceof SliderTranslation) {
            return $first;
        }

        throw new \RuntimeException('Cannot resolve translation locale for slider.');
    }

    private function findTranslation(string $locale): ?SliderTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocaleCode() === $locale) {
                return $translation;
            }
        }

        return null;
    }

}
