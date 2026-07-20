<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Sylius\Component\Core\Model\Channel;
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

        // The shared test DB is never purged and shop @javascript scenarios
        // re-point channel hostnames at their throwaway test server (channel
        // resolution is host-based); restore the canonical hostname so the
        // admin legs stay order-independent.
        foreach ($this->entityManager->getRepository(Channel::class)->findAll() as $channel) {
            if ('localhost' !== $channel->getHostname()) {
                $channel->setHostname('localhost');
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @When I go to the slider index page
     */
    public function iGoToTheSliderIndexPage(): void
    {
        // The test database is shared with the functional suite (whose
        // fixtures are never purged) — raise the page size so the demo
        // sliders can't get paginated off the first page.
        $this->visitPath('/admin/sliders?limit=100');
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

    /**
     * @Given the slider :code is configured with :key set to :value
     */
    public function theSliderIsConfiguredWithSetTo(string $code, string $key, string $value): void
    {
        /** @var Slider|null $slider */
        $slider = $this->entityManager->getRepository(Slider::class)->findOneBy(['code' => $code]);
        if (null === $slider) {
            throw new \RuntimeException(sprintf('Cannot find slider by code "%s".', $code));
        }

        $settings = $slider->getSettings();
        $settings[$key] = match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
        $slider->setSettings($settings);

        $this->entityManager->flush();
    }

    /**
     * @Then the slider settings field :field should be present
     */
    public function theSliderSettingsFieldShouldBePresent(string $field): void
    {
        $element = $this->getSession()->getPage()->find('css', sprintf('[name*="[settings][%s]"]', $field));
        if (null === $element) {
            throw new \RuntimeException(sprintf('Slider settings field "%s" was not found on the page.', $field));
        }
    }

    /**
     * @Then the slider settings field :field should have selected value :value
     */
    public function theSliderSettingsFieldShouldHaveSelectedValue(string $field, string $value): void
    {
        $element = $this->getSession()->getPage()->find('css', sprintf('[name*="[settings][%s]"]', $field));
        if (null === $element) {
            throw new \RuntimeException(sprintf('Slider settings field "%s" was not found on the page.', $field));
        }

        $actual = $element->getValue();
        if ($actual !== $value) {
            throw new \RuntimeException(sprintf(
                'Expected field "%s" to have value "%s", got "%s".',
                $field,
                $value,
                is_scalar($actual) ? (string) $actual : get_debug_type($actual),
            ));
        }
    }

    /**
     * @Then I should see the slider preview panel
     */
    public function iShouldSeeTheSliderPreviewPanel(): void
    {
        $panel = $this->getSession()->getPage()->find('css', '[data-controller~="vanssa-preview-frame"]');
        if (null === $panel) {
            throw new \RuntimeException('Slider preview panel was not found on the page.');
        }
    }
}
