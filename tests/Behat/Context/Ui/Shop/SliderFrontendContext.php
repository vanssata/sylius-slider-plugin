<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

final class SliderFrontendContext extends RawMinkContext implements Context
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FixtureInterface $sliderDemoFixture,
    ) {
    }

    /**
     * @Given slider demo fixtures are loaded
     */
    public function sliderDemoFixturesAreLoaded(): void
    {
        $this->sliderDemoFixture->load([]);
    }

    /**
     * @When I visit the slider page for code :code
     */
    public function iVisitTheSliderPageForCode(string $code): void
    {
        $this->visitPath('/slider/' . $code);
    }

    /**
     * @Given slider :code has parallax strength :strength
     */
    public function sliderHasParallaxStrength(string $code, string $strength): void
    {
        /** @var Slider|null $slider */
        $slider = $this->entityManager->getRepository(Slider::class)->findOneBy(['code' => $code]);
        if (null === $slider) {
            throw new \RuntimeException(sprintf('Cannot find slider by code "%s".', $code));
        }

        $settings = $slider->getSettings();
        $parallax = $settings['parallax'] ?? [];
        if (!is_array($parallax)) {
            $parallax = [];
        }

        $parallax['strength'] = $strength;
        $settings['parallax'] = $parallax;
        $slider->setSettings($settings);

        $this->entityManager->flush();
    }

    /**
     * @Then I should see the storefront slider component
     */
    public function iShouldSeeTheStorefrontSliderComponent(): void
    {
        $page = $this->getSession()->getPage();
        if (null === $page->find('css', 'section.vanssa-slider')) {
            throw new \RuntimeException('Slider component section was not found.');
        }
    }

    /**
     * @Then I should see slider text :text
     */
    public function iShouldSeeSliderText(string $text): void
    {
        $content = $this->getSession()->getPage()->getText();
        if (!str_contains($content, $text)) {
            throw new \RuntimeException(sprintf('Expected text "%s" was not found on slider page.', $text));
        }
    }

    /**
     * @Then I should see slider with css class :className
     */
    public function iShouldSeeSliderWithCssClass(string $className): void
    {
        $page = $this->getSession()->getPage();
        if (null === $page->find('css', sprintf('section.vanssa-slider.%s', $className))) {
            throw new \RuntimeException(sprintf('Slider with CSS class "%s" was not found.', $className));
        }
    }

    /**
     * @Then slider stimulus options should include parallax strength :strength
     */
    public function sliderStimulusOptionsShouldIncludeParallaxStrength(string $strength): void
    {
        $page = $this->getSession()->getPage();
        $slider = $page->find('css', 'section.vanssa-slider');
        if (null === $slider) {
            throw new \RuntimeException('Slider component section was not found.');
        }

        $options = $slider->getAttribute('data-vanssa-slider-options-value');
        if (!is_string($options) || !str_contains($options, sprintf('"strength":"%s"', $strength))) {
            throw new \RuntimeException(sprintf('Parallax strength "%s" not found in slider Stimulus options.', $strength));
        }
    }
}
