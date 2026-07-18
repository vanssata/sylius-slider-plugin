<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Factory\SlideFactory;
use Vanssa\SyliusSliderPlugin\Form\Type\SlideType;
use Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

/**
 * Serves the SlideType CREATE form as a turbo-frame fragment so a new slide
 * can be added from the slider's Slides section without leaving the page —
 * same flow as the regular creation (style presets included), pre-attached
 * to the slider (SlideFactory::createForSlider).
 */
final class SlideCreatePanelController
{
    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly SlideFactory $slideFactory,
        private readonly FormFactoryInterface $formFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly StylePresetProvider $stylePresetProvider,
        private readonly Environment $twig,
        private readonly ?Profiler $profiler = null,
    ) {
    }

    public function __invoke(Request $request, int $sliderId): Response
    {
        $this->profiler?->disable();

        $slider = $this->sliderRepository->find($sliderId);
        if (!$slider instanceof Slider) {
            throw new NotFoundHttpException(sprintf('Slider "%d" does not exist.', $sliderId));
        }

        $slide = $this->slideFactory->createForSlider($slider);
        $form = $this->formFactory->create(SlideType::class, $slide);
        $created = false;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->persist($slide);
                $this->entityManager->flush();
                $created = true;
            }
        }

        $response = new Response($this->twig->render('@VanssaSyliusSliderPlugin/admin/slide/create_panel.html.twig', [
            'slider' => $slider,
            'slide' => $slide,
            'form' => $form->createView(),
            'created' => $created,
            'style_presets' => $this->stylePresetProvider->slidePresets(),
        ]));

        // Turbo only renders non-2xx form responses when they are 422.
        if ($request->isMethod('POST') && !$created && $form->isSubmitted()) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }
}
