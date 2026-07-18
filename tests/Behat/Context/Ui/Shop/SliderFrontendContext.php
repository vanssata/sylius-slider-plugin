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
     * @Given slider :code has setting :key set to :value
     */
    public function sliderHasSettingSetTo(string $code, string $key, string $value): void
    {
        /** @var Slider|null $slider */
        $slider = $this->entityManager->getRepository(Slider::class)->findOneBy(['code' => $code]);
        if (null === $slider) {
            throw new \RuntimeException(sprintf('Cannot find slider by code "%s".', $code));
        }

        $settings = $slider->getSettings();
        $settings[$key] = self::coerceValue($value);
        $slider->setSettings($settings);

        $this->entityManager->flush();
    }

    /**
     * @Given the slide :code has responsive :key set to :value for :breakpoint
     */
    public function theSlideHasResponsiveSettingSetTo(string $code, string $key, string $value, string $breakpoint): void
    {
        $slide = $this->entityManager->getRepository(\Vanssa\SyliusSliderPlugin\Entity\Slide::class)->findOneBy(['code' => $code]);
        if (null === $slide) {
            throw new \RuntimeException(sprintf('Cannot find slide by code "%s".', $code));
        }

        $settings = $slide->getSlideSettings();
        $responsive = is_array($settings['responsive'] ?? null) ? $settings['responsive'] : [];
        $breakpointSettings = is_array($responsive[$breakpoint] ?? null) ? $responsive[$breakpoint] : [];
        $breakpointSettings[$key] = self::coerceValue($value);
        $responsive[$breakpoint] = $breakpointSettings;
        $settings['responsive'] = $responsive;
        $slide->setSlideSettings($settings);

        $this->entityManager->flush();
    }

    /**
     * @Then the slide :code content style should contain :fragment
     */
    public function theSlideContentStyleShouldContain(string $code, string $fragment): void
    {
        $content = $this->getSession()->getPage()->find('css', sprintf('.vanssa-slide[data-slide-code="%s"] .vanssa-slide__content', $code));
        if (null === $content) {
            throw new \RuntimeException(sprintf('Cannot find rendered content for slide "%s".', $code));
        }

        $style = (string) $content->getAttribute('style');
        if (!str_contains($style, $fragment)) {
            throw new \RuntimeException(sprintf('Expected slide "%s" content style to contain "%s", got "%s".', $code, $fragment, $style));
        }
    }

    /**
     * @Given slider :code has autoplay enabled
     */
    public function sliderHasAutoplayEnabled(string $code): void
    {
        /** @var Slider|null $slider */
        $slider = $this->entityManager->getRepository(Slider::class)->findOneBy(['code' => $code]);
        if (null === $slider) {
            throw new \RuntimeException(sprintf('Cannot find slider by code "%s".', $code));
        }

        $settings = $slider->getSettings();
        $settings['autoplay'] = ['enabled' => true, 'interval' => 3000, 'pauseOnHover' => false];
        $slider->setSettings($settings);

        $this->entityManager->flush();
    }

    /**
     * @Then slider stimulus options should include :key with value :value
     */
    public function sliderStimulusOptionsShouldIncludeWithValue(string $key, string $value): void
    {
        $options = $this->sliderOptionsAttribute();
        $fragment = sprintf('"%s":%s', $key, json_encode(self::coerceValue($value), \JSON_THROW_ON_ERROR));

        if (!str_contains($options, $fragment)) {
            throw new \RuntimeException(sprintf('Fragment %s not found in slider Stimulus options: %s', $fragment, $options));
        }
    }

    /**
     * @Then I should see a lazy loaded slide image
     */
    public function iShouldSeeALazyLoadedSlideImage(): void
    {
        $page = $this->getSession()->getPage();
        if (null === $page->find('css', '.vanssa-slide img[loading="lazy"]')) {
            throw new \RuntimeException('No lazily loaded slide image was found.');
        }
    }

    /**
     * @Then I should see the autoplay progress bar
     */
    public function iShouldSeeTheAutoplayProgressBar(): void
    {
        $page = $this->getSession()->getPage();
        if (null === $page->find('css', '.vanssa-slider__progress-bar')) {
            throw new \RuntimeException('Autoplay progress bar was not found.');
        }
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
        $options = $this->sliderOptionsAttribute();
        if (!str_contains($options, sprintf('"strength":"%s"', $strength))) {
            throw new \RuntimeException(sprintf('Parallax strength "%s" not found in slider Stimulus options.', $strength));
        }
    }

    private function sliderOptionsAttribute(): string
    {
        $page = $this->getSession()->getPage();
        $slider = $page->find('css', 'section.vanssa-slider');
        if (null === $slider) {
            throw new \RuntimeException('Slider component section was not found.');
        }

        $options = $slider->getAttribute('data-vanssa-slider-options-value');
        if (!is_string($options)) {
            throw new \RuntimeException('Slider Stimulus options attribute was not found.');
        }

        return $options;
    }

    private static function coerceValue(string $value): bool|int|string
    {
        if ('true' === $value) {
            return true;
        }

        if ('false' === $value) {
            return false;
        }

        if (is_numeric($value) && (string) (int) $value === $value) {
            return (int) $value;
        }

        return $value;
    }
}
