<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig;

use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class SliderExtension extends AbstractExtension
{
    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly SlideRepository $slideRepository,
        private readonly Environment $twig,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sylius_slider_by_code', $this->findSliderByCode(...)),
            new TwigFunction('sylius_slide_by_code', $this->findSlideByCode(...)),
            new TwigFunction('sylius_slider_render_content', $this->renderContent(...), ['is_safe' => ['html']]),
        ];
    }

    public function findSliderByCode(string $code): mixed
    {
        return $this->sliderRepository->findEnabledOneByCode($code);
    }

    public function findSlideByCode(string $code): mixed
    {
        return $this->slideRepository->findEnabledOneByCode($code);
    }

    public function renderContent(?string $content): Markup
    {
        $content ??= '';

        $filter = $this->twig->getFilter('monsieurbiz_richeditor_render_field');
        if (null === $filter) {
            return new Markup($content, 'UTF-8');
        }

        $template = $this->twig->createTemplate('{{ content|monsieurbiz_richeditor_render_field }}');

        return new Markup($template->render(['content' => $content]), 'UTF-8');
    }
}
