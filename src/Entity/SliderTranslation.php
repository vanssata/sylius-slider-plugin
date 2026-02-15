<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslationInterface;
use Webmozart\Assert\Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'vanssa_sylius_slider_translation',
    indexes: [new ORM\Index(name: 'idx_3f40008748d6cc1e', columns: ['slider_id'])]
)]
#[ORM\UniqueConstraint(name: 'uniq_slider_locale', columns: ['slider_id', 'locale_code'])]
class SliderTranslation implements ResourceInterface, TranslationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Slider::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'slider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Slider $slider = null;

    #[ORM\Column(name: 'locale_code', type: 'string', length: 16)]
    private string $localeCode = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $settings = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlider(): ?Slider
    {
        return $this->slider;
    }

    public function setSlider(?Slider $slider): void
    {
        $this->slider = $slider;
    }

    public function getTranslatable(): Slider
    {
        $slider = $this->slider;
        Assert::notNull($slider);

        return $slider;
    }

    public function setTranslatable(?TranslatableInterface $translatable): void
    {
        if (null !== $translatable && !$translatable instanceof Slider) {
            throw new \InvalidArgumentException('Expected translatable to be instance of Slider.');
        }

        $this->setSlider($translatable);
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
        $this->settings = $settings;
    }
}
