<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

final class SliderManagementContext extends RawMinkContext implements Context
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
     * @When I go to the slider index page
     */
    public function iGoToTheSliderIndexPage(): void
    {
        $this->visitPath('/admin/sliders');
    }

    /**
     * @When I go to the slider update page for code :code
     */
    public function iGoToTheSliderUpdatePageForCode(string $code): void
    {
        /** @var Slider|null $slider */
        $slider = $this->entityManager->getRepository(Slider::class)->findOneBy(['code' => $code]);
        if (null === $slider || null === $slider->getId()) {
            throw new \RuntimeException(sprintf('Cannot find slider by code "%s".', $code));
        }

        $this->visitPath(sprintf('/admin/sliders/%d/edit', $slider->getId()));
    }

    /**
     * @Then I should see slider entry :name
     */
    public function iShouldSeeSliderEntry(string $name): void
    {
        $content = $this->getSession()->getPage()->getText();
        if (!str_contains($content, $name)) {
            throw new \RuntimeException(sprintf('Expected slider "%s" was not found in admin list.', $name));
        }
    }

    /**
     * @Then I should see slide code :slideCode in slider preview
     */
    public function iShouldSeeSlideCodeInSliderPreview(string $slideCode): void
    {
        $content = $this->getSession()->getPage()->getText();
        if (!str_contains($content, $slideCode)) {
            throw new \RuntimeException(sprintf('Expected slide code "%s" not found in slider preview.', $slideCode));
        }
    }
}
