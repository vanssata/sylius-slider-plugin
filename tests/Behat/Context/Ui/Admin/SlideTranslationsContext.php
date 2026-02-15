<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Driver\BrowserKitDriver;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Locale\Model\Locale;

final class SlideTranslationsContext extends RawMinkContext implements Context
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @Given the locale :localeCode exists
     */
    public function theLocaleExists(string $localeCode): void
    {
        $existing = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => $localeCode]);
        if (null !== $existing) {
            return;
        }

        $locale = new Locale();
        $locale->setCode($localeCode);

        $this->entityManager->persist($locale);
        $this->entityManager->flush();
    }

    /**
     * @When I am logged in to the administration as :username
     */
    public function iAmLoggedInToTheAdministrationAs(string $username): void
    {
        /** @var AdminUser|null $admin */
        $admin = $this->entityManager->getRepository(AdminUser::class)->findOneBy(['username' => $username]);
        if (null === $admin) {
            $admin = $this->entityManager->getRepository(AdminUser::class)->findOneBy(['email' => $username]);
        }

        if (null === $admin) {
            throw new \RuntimeException(sprintf('Cannot find admin user "%s".', $username));
        }

        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new \RuntimeException('This step requires BrowserKitDriver.');
        }

        $driver->getClient()->loginUser($admin, 'admin');
    }

    /**
     * @When I go to the slide creation page
     */
    public function iGoToTheSlideCreationPage(): void
    {
        $this->visitPath('/admin/slides/new');
    }

    /**
     * @Then I should see translation accordion for locale :localeCode
     */
    public function iShouldSeeTranslationAccordionForLocale(string $localeCode): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $needle = sprintf('data-test-slide-translations-accordion="%s"', $localeCode);

        if (!str_contains($content, $needle)) {
            throw new \RuntimeException(sprintf('Cannot find translation accordion for locale "%s".', $localeCode));
        }
    }
}
