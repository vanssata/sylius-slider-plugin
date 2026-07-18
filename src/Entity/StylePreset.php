<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Vanssa\SyliusSliderPlugin\Repository\StylePresetRepository;

#[ORM\Entity(repositoryClass: StylePresetRepository::class)]
#[ORM\Table(name: 'vanssa_sylius_style_preset')]
#[UniqueEntity(fields: ['code'], message: 'This preset code is already in use.')]
class StylePreset implements ResourceInterface, \Stringable
{
    public const TYPE_SLIDER = 'slider';

    public const TYPE_SLIDE = 'slide';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $code = '';

    #[ORM\Column(type: 'string', length: 16)]
    private string $type = self::TYPE_SLIDE;

    #[ORM\Column(type: 'string', length: 255)]
    private string $label = '';

    /**
     * Flat dot-path => scalar map, the same shape as the bundle's
     * style_presets configuration (e.g. "settings.responsive.desktop.textColor").
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $settings = [];

    /**
     * Either a bundled mockup path (relative to the installed bundle assets)
     * or an uploaded media path.
     */
    #[ORM\Column(name: 'mockup_image', type: 'string', length: 1024, nullable: true)]
    private ?string $mockupImage = null;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    /**
     * Source slides for slider-type presets; cloned (not linked) when a
     * slider is created from this preset.
     *
     * @var Collection<int, Slide>
     */
    #[ORM\ManyToMany(targetEntity: Slide::class)]
    #[ORM\JoinTable(name: 'vanssa_sylius_style_preset_slide')]
    #[ORM\JoinColumn(name: 'preset_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'slide_id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $slides;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->label !== '' ? $this->label : $this->code;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /** @return array<string, mixed> */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /** @param array<string, mixed> $settings */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function getMockupImage(): ?string
    {
        return $this->mockupImage;
    }

    public function setMockupImage(?string $mockupImage): void
    {
        $this->mockupImage = $mockupImage;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /** @return Collection<int, Slide> */
    public function getSlides(): Collection
    {
        return $this->slides;
    }

    public function addSlide(Slide $slide): void
    {
        if (!$this->slides->contains($slide)) {
            $this->slides->add($slide);
        }
    }

    public function removeSlide(Slide $slide): void
    {
        $this->slides->removeElement($slide);
    }
}
