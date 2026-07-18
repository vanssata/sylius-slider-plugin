<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

/**
 * "Add slides" browser for the slider's Slides section: lists EVERY slide in
 * the admin with search, a membership filter (in this slider / not in it /
 * all) and pagination; checking/unchecking attaches/detaches the slide —
 * nothing is ever deleted here.
 */
#[AsLiveComponent(name: 'vanssa_sylius_slider:admin:slider_slide_browser', template: '@VanssaSyliusSliderPlugin/components/vanssa_sylius_slider/admin/slider_slide_browser.html.twig')]
final class SliderSlideBrowserComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    private const PER_PAGE = 8;

    #[LiveProp]
    public int $sliderId;

    #[LiveProp(writable: true, onUpdated: 'resetPage')]
    public string $search = '';

    #[LiveProp(writable: true, onUpdated: 'resetPage')]
    public string $membership = 'all';

    #[LiveProp(writable: true)]
    public int $page = 1;

    /**
     * Desired attachment changes not yet saved: slideId => desired attached
     * state (PHP normalizes numeric keys to int on both sides of the JSON
     * round trip). Nothing touches the database until applyChanges().
     *
     * @var array<int, bool>
     */
    #[LiveProp(writable: true)]
    public array $pending = [];

    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function resetPage(): void
    {
        $this->page = 1;
    }

    public function getSlider(): ?Slider
    {
        $slider = $this->sliderRepository->find($this->sliderId);

        return $slider instanceof Slider ? $slider : null;
    }

    /**
     * @return array{items: list<Slide>, total: int, pages: int, page: int}
     */
    public function getResults(): array
    {
        $slider = $this->getSlider();
        if (null === $slider) {
            return ['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
        }

        $queryBuilder = $this->slideRepository->createQueryBuilder('slide')
            ->orderBy('slide.id', 'DESC');

        $search = trim($this->search);
        if ('' !== $search) {
            $queryBuilder
                ->andWhere('slide.name LIKE :search OR slide.code LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ('in' === $this->membership) {
            $queryBuilder->andWhere(':slider MEMBER OF slide.sliders')->setParameter('slider', $slider);
        } elseif ('out' === $this->membership) {
            $queryBuilder->andWhere(':slider NOT MEMBER OF slide.sliders')->setParameter('slider', $slider);
        }

        $paginator = new Paginator($queryBuilder->getQuery());
        $total = count($paginator);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $this->page), $pages);

        $paginator->getQuery()
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE);

        /** @var list<Slide> $items */
        $items = iterator_to_array($paginator->getIterator(), false);

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public function isAttached(Slide $slide): bool
    {
        $slider = $this->getSlider();

        return null !== $slider && $slide->getSliders()->contains($slider);
    }

    /**
     * The checkbox state shown for a row: a pending (unsaved) change wins
     * over the persisted membership.
     */
    public function isChecked(Slide $slide): bool
    {
        return $this->pending[(int) $slide->getId()] ?? $this->isAttached($slide);
    }

    public function getPendingCount(): int
    {
        return count($this->pending);
    }

    /**
     * Records the DESIRED attachment state for a slide (idempotent — a
     * duplicate dispatch is a no-op). A mark that matches the persisted
     * state simply clears the pending change.
     */
    #[LiveAction]
    public function markSlide(#[LiveArg] int $slideId, #[LiveArg] bool $attached): void
    {
        $slide = $this->slideRepository->find($slideId);
        if (!$slide instanceof Slide) {
            return;
        }

        if ($attached === $this->isAttached($slide)) {
            unset($this->pending[$slideId]);

            return;
        }

        $this->pending[$slideId] = $attached;
    }

    /** Discards unsaved marks — wired to the modal's close/cancel path. */
    #[LiveAction]
    public function resetPending(): void
    {
        $this->pending = [];
    }

    /**
     * Commits every pending attach/detach in one go — triggered by the
     * modal's Save button. Idempotent: a duplicate dispatch finds pending
     * empty and does nothing.
     */
    #[LiveAction]
    public function applyChanges(): void
    {
        $slider = $this->getSlider();
        if (null === $slider || [] === $this->pending) {
            return;
        }

        foreach ($this->pending as $slideId => $attached) {
            $slide = $this->slideRepository->find($slideId);
            if (!$slide instanceof Slide) {
                continue;
            }

            if ($attached && !$slide->getSliders()->contains($slider)) {
                $slider->addSlide($slide);
            } elseif (!$attached && $slide->getSliders()->contains($slider)) {
                $slider->removeSlide($slide);
            }
        }

        $this->entityManager->flush();
        $this->pending = [];

        // Let the Slides list (slider_slides_preview component) re-render,
        // then have the modal shell close itself.
        $this->emit('vanssa:slider-slides-changed', ['sliderId' => $this->sliderId]);
        $this->dispatchBrowserEvent('vanssa:slide-browser-saved');
    }

    #[LiveAction]
    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    #[LiveAction]
    public function nextPage(): void
    {
        ++$this->page;
    }
}
