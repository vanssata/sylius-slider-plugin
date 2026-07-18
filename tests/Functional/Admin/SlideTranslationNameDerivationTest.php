<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Symfony\Component\Form\FormFactoryInterface;
use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Form\Type\Translation\SlideTranslationType;

final class SlideTranslationNameDerivationTest extends FunctionalTestCase
{
    public function testNameIsDerivedFromDesktopTitleOnSubmit(): void
    {
        $translation = $this->submitTranslation('name-derivation-slide', [
            'settings' => [
                'responsive' => [
                    'desktop' => ['title' => 'Derived Title'],
                ],
            ],
        ]);

        self::assertSame('Derived Title', $translation->getName());
    }

    public function testNameFallsBackToSlideCodeWhenDesktopTitleIsEmpty(): void
    {
        $translation = $this->submitTranslation('fallback-code-slide', []);

        self::assertSame('fallback-code-slide', $translation->getName());
    }

    /**
     * @param array<string, mixed> $submittedData
     */
    private function submitTranslation(string $slideCode, array $submittedData): SlideTranslation
    {
        $slide = new Slide();
        $slide->setCode($slideCode);

        $translation = new SlideTranslation();
        $translation->setLocale('en_US');
        $slide->addTranslation($translation);

        $formFactory = self::getContainer()->get('form.factory');
        \assert($formFactory instanceof FormFactoryInterface);

        $form = $formFactory->create(SlideTranslationType::class, $translation);
        $form->submit($submittedData);

        return $translation;
    }
}
