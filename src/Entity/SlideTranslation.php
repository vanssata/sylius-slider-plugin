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
    name: 'vanssa_sylius_slide_translation',
    indexes: [new ORM\Index(name: 'idx_95a50d9998e46b87', columns: ['slide_id'])]
)]
#[ORM\UniqueConstraint(name: 'uniq_slide_locale', columns: ['slide_id', 'locale_code'])]
class SlideTranslation implements ResourceInterface, TranslationInterface
{
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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

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

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'content_settings', type: 'json')]
    private array $contentSettings = [];

    /**
     * @var array<string, mixed>
     */
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
        $this->slideSettings = $slideSettings;
    }
}
