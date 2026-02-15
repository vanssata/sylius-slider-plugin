<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;

final class SliderFrontendContext extends RawMinkContext implements Context
{
    public function __construct(
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
}
