<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig;

use Symfony\Component\Routing\RouterInterface;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;
use Twig\Markup;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

final readonly class SliderExtension
{
    public function __construct(
        private SliderRepository $sliderRepository,
        private SlideRepository  $slideRepository,
        private RouterInterface  $router,
        private Environment      $twig,
    ) {
    }

    #[AsTwigFunction('sylius_slider_by_code')]
    public function findSliderByCode(string $code): mixed
    {
        return $this->sliderRepository->findEnabledOneByCode($code);
    }

    #[AsTwigFunction('sylius_slide_by_code')]
    public function findSlideByCode(string $code): mixed
    {
        return $this->slideRepository->findEnabledOneByCode($code);
    }

    #[AsTwigFunction('sylius_slider_render_content', isSafe: ['html'])]
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

    #[AsTwigFunction('vanssa_route_exists')]
    public function routeExists(string $routeName): bool
    {
        return null !== $this->router->getRouteCollection()->get($routeName);
    }
}
