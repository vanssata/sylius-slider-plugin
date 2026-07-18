<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slide;

/**
 * Browser (@javascript) steps for the two-column editing workspace: settings
 * drawer, toolbar-driven locale/breakpoint context, live draft preview,
 * preset try-on, fullscreen.
 */
final class SliderEditorJsContext extends RawMinkContext implements Context
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FixtureInterface $sliderDemoFixture,
    ) {
    }

    /**
     * @Given slide demo fixtures are loaded
     */
    public function slideDemoFixturesAreLoaded(): void
    {
        $this->sliderDemoFixture->load([]);
    }

    /**
     * @When I go to the slide update page for code :code
     */
    public function iGoToTheSlideUpdatePage(string $code): void
    {
        $slide = $this->entityManager->getRepository(Slide::class)->findOneBy(['code' => $code]);
        if (!$slide instanceof Slide || null === $slide->getId()) {
            throw new \RuntimeException(sprintf('Cannot find slide by code "%s".', $code));
        }

        $this->visitPath(sprintf('/admin/slides/%d/edit', $slide->getId()));
        $this->waitCondition('!!document.querySelector(".vanssa-workspace")', 'the editing workspace');
    }

    /**
     * @Then the settings drawer should be closed
     */
    public function theSettingsDrawerShouldBeClosed(): void
    {
        if (null !== $this->getSession()->getPage()->find('css', '.vanssa-workspace.is-drawer-open')) {
            throw new \RuntimeException('Expected the settings drawer to be closed.');
        }
    }

    /**
     * @When I open the settings drawer
     */
    public function iOpenTheSettingsDrawer(): void
    {
        $this->findOrFail('[data-vanssa-preview-frame-target="drawerToggle"]')->click();
        $this->waitCondition('!!document.querySelector(".vanssa-workspace.is-drawer-open")', 'the settings drawer to open');
    }

    /**
     * @Then the drawer head should offer save, language and breakpoint controls
     */
    public function theDrawerHeadShouldOfferControls(): void
    {
        $head = $this->findOrFail('.vanssa-workspace__drawer-head');
        if (null === $head->find('css', 'button[form]')) {
            throw new \RuntimeException('Drawer head is missing the Save button.');
        }
        if (null === $head->find('css', 'select[data-vanssa-preview-frame-target="locale"]')) {
            throw new \RuntimeException('Drawer head is missing the language switcher.');
        }
        if (3 !== count($head->findAll('css', '[data-vanssa-preview-frame-target="sizeButton"]'))) {
            throw new \RuntimeException('Drawer head is missing the breakpoint switcher.');
        }
    }

    /**
     * @Then the drawer body should scroll independently
     */
    public function theDrawerBodyShouldScrollIndependently(): void
    {
        $result = $this->getSession()->evaluateScript(
            '(() => { const body = document.querySelector(".vanssa-workspace__drawer-body"); return body ? getComputedStyle(body).overflowY : null; })()',
        );
        if ('auto' !== $result) {
            throw new \RuntimeException(sprintf('Expected the drawer body to scroll on its own, got overflow-y "%s".', (string) $result));
        }
    }

    /**
     * @When I switch the toolbar breakpoint to :breakpoint
     */
    public function iSwitchTheToolbarBreakpoint(string $breakpoint): void
    {
        $this->findOrFail(sprintf('.vanssa-workspace__drawer-head [data-vanssa-preview-frame-breakpoint-param="%s"]', $breakpoint))->click();
    }

    /**
     * @Then only the :breakpoint form sections should be visible
     */
    public function onlyTheFormSectionsShouldBeVisible(string $breakpoint): void
    {
        $condition = sprintf(
            <<<'JS'
            (() => {
                const sections = [...document.querySelectorAll('#vanssa-slide-media-settings [data-vanssa-context-breakpoint]')];
                return sections.length > 0 && sections.every((section) =>
                    section.dataset.vanssaContextBreakpoint === '%s' ? !section.classList.contains('d-none') : section.classList.contains('d-none'));
            })()
            JS,
            $breakpoint,
        );
        $this->waitCondition($condition, sprintf('only the %s sections to be visible', $breakpoint));
    }

    /**
     * @When I switch the toolbar language to :localeCode
     */
    public function iSwitchTheToolbarLanguage(string $localeCode): void
    {
        $this->findOrFail('.vanssa-workspace__drawer-head select[data-vanssa-preview-frame-target="locale"]')->selectOption($localeCode);
    }

    /**
     * @Then the base media card should be hidden
     */
    public function theBaseMediaCardShouldBeHidden(): void
    {
        $this->waitCondition(
            'document.querySelector("#vanssa-slide-media-settings")?.classList.contains("d-none") === true',
            'the base media card to hide',
        );
    }

    /**
     * @When I fill the :localeCode desktop title with :value
     */
    public function iFillTheDesktopTitle(string $localeCode, string $value): void
    {
        $field = $this->findOrFail(sprintf('[name="slide[translations][%s][settings][responsive][desktop][title]"]', $localeCode));
        $field->setValue($value);
    }

    /**
     * @When I hover the style preset :name
     */
    public function iHoverTheStylePreset(string $name): void
    {
        $this->findOrFail('.vanssa-preview-panel .dropdown-toggle')->click();
        $this->findOrFail(sprintf('[data-vanssa-preset-applier-preset-param="%s"]', $name))->mouseOver();
    }

    /**
     * @When I click the style preset :name
     */
    public function iClickTheStylePreset(string $name): void
    {
        $this->findOrFail(sprintf('[data-vanssa-preset-applier-preset-param="%s"]', $name))->click();
    }

    /**
     * @Then the form field :fieldName should have value :value
     */
    public function theFormFieldShouldHaveValue(string $fieldName, string $value): void
    {
        $actual = $this->getSession()->evaluateScript(sprintf(
            'document.querySelector(\'[name="%s"]\')?.value',
            $fieldName,
        ));
        if ((string) $actual !== $value) {
            throw new \RuntimeException(sprintf('Expected field "%s" to have value "%s", got "%s".', $fieldName, $value, (string) $actual));
        }
    }

    /**
     * @When I toggle the fullscreen workspace
     */
    public function iToggleTheFullscreenWorkspace(): void
    {
        $this->findOrFail('[data-action="vanssa-preview-frame#toggleFullscreen"]')->click();
    }

    /**
     * @Then the workspace should be fullscreen
     */
    public function theWorkspaceShouldBeFullscreen(): void
    {
        $this->waitCondition('document.body.classList.contains("vanssa-workspace-fullscreen")', 'the fullscreen workspace');
    }

    /**
     * @Then the workspace should not be fullscreen
     */
    public function theWorkspaceShouldNotBeFullscreen(): void
    {
        $this->waitCondition('!document.body.classList.contains("vanssa-workspace-fullscreen")', 'fullscreen to end');
    }

    /**
     * @When I press the escape key
     */
    public function iPressTheEscapeKey(): void
    {
        $this->getSession()->executeScript(
            'document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }))',
        );
    }

    /**
     * @Then the preview should be scaled to fit
     */
    public function thePreviewShouldBeScaledToFit(): void
    {
        $this->waitCondition(
            '(document.querySelector(\'[data-vanssa-preview-frame-target="scaleBox"]\')?.style.height ?? "") !== ""',
            'the preview scale box to receive an explicit height',
        );
    }

    private function findOrFail(string $selector): NodeElement
    {
        $element = $this->getSession()->getPage()->find('css', $selector);
        if (null === $element) {
            throw new \RuntimeException(sprintf('Element "%s" not found.', $selector));
        }

        return $element;
    }

    private function waitCondition(string $jsCondition, string $description, int $timeoutMs = 8000): void
    {
        if (!$this->getSession()->wait($timeoutMs, $jsCondition)) {
            throw new \RuntimeException(sprintf('Timed out waiting for %s.', $description));
        }
    }
}
