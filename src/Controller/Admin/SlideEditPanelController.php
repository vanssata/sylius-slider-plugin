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
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Form\Type\SlideType;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;

/**
 * Serves the REAL SlideType edit form as a turbo-frame fragment, so the
 * slide preview modal (grid rows, slider slide lists) can edit and save a
 * slide without leaving the page. The full form is always round-tripped —
 * a partial one would either wipe omitted fields (handleRequest clears
 * missing) or break checkbox unchecking (submit with clearMissing=false).
 */
final class SlideEditPanelController
{
    public function __construct(
        private readonly SlideRepository $slideRepository,
        private readonly FormFactoryInterface $formFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
        private readonly ?Profiler $profiler = null,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        // The panel renders inside a turbo-frame; keep the web debug toolbar
        // out of it in dev environments.
        $this->profiler?->disable();

        $slide = $this->slideRepository->find($id);
        if (!$slide instanceof Slide) {
            throw new NotFoundHttpException(sprintf('Slide "%d" does not exist.', $id));
        }

        $form = $this->formFactory->create(SlideType::class, $slide);
        $saved = false;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->flush();
                $saved = true;
                // Re-render from a pristine form so submitted-but-normalized
                // values (and no stale errors) come back to the frame.
                $form = $this->formFactory->create(SlideType::class, $slide);
            }
        }

        $response = new Response($this->twig->render('@VanssaSyliusSliderPlugin/admin/slide/edit_panel.html.twig', [
            'slide' => $slide,
            'form' => $form->createView(),
            'saved' => $saved,
        ]));

        // Turbo only renders non-2xx form-submission responses into the
        // frame when they are 422; a plain 200 with errors would be ignored.
        if ($request->isMethod('POST') && !$saved) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }
}
