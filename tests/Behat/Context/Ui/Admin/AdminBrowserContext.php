<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\User\Security\PasswordUpdaterInterface;

/**
 * Chrome-session-compatible admin steps: the BrowserKit-only loginUser()
 * shortcut cannot authenticate a real browser, so @javascript scenarios log
 * in through the actual login form. Also provides JS wait helpers.
 */
final class AdminBrowserContext extends RawMinkContext implements Context
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordUpdaterInterface $passwordUpdater,
    ) {
    }

    /**
     * @Given there is an administrator :username identified by :password
     */
    public function thereIsAnAdministrator(string $username, string $password): void
    {
        $admin = $this->entityManager->getRepository(AdminUser::class)->findOneBy(['username' => $username]);
        if (!$admin instanceof AdminUser) {
            $admin = new AdminUser();
            $admin->setUsername($username);
            $admin->setEmail(sprintf('%s@example.com', $username));
            $admin->setLocaleCode('en_US');
            $this->entityManager->persist($admin);
        }

        $admin->setEnabled(true);
        $admin->setPlainPassword($password);
        $this->passwordUpdater->updatePassword($admin);
        $this->entityManager->flush();
    }

    /**
     * @When I sign in to the administration as :username with password :password
     */
    public function iSignInToTheAdministration(string $username, string $password): void
    {
        $this->visitPath('/admin/login');
        $page = $this->getSession()->getPage();
        $page->fillField('_username', $username);
        $page->fillField('_password', $password);
        $page->pressButton('Login');
        $this->waitFor('!document.querySelector(\'input[name="_password"]\')', 'the admin login to complete');
    }

    /**
     * @Then I wait until the selector :selector appears
     */
    public function iWaitUntilTheSelectorAppears(string $selector): void
    {
        $this->waitFor(sprintf('!!document.querySelector(%s)', json_encode($selector, \JSON_THROW_ON_ERROR)), sprintf('selector "%s"', $selector));
    }

    /**
     * @Then I wait until the preview frame contains :text
     */
    public function iWaitUntilThePreviewFrameContains(string $text): void
    {
        $this->waitFor(sprintf(
            '[...document.querySelectorAll(\'turbo-frame[data-vanssa-preview-frame-target="frame"]\')].some((frame) => frame.textContent.includes(%s))',
            json_encode($text, \JSON_THROW_ON_ERROR),
        ), sprintf('the preview frame to contain "%s"', $text), 10000);
    }

    /**
     * Public for sibling contexts sharing the session.
     */
    public function waitFor(string $jsCondition, string $description, int $timeoutMs = 5000): void
    {
        if (!$this->getSession()->wait($timeoutMs, $jsCondition)) {
            throw new \RuntimeException(sprintf('Timed out waiting for %s.', $description));
        }
    }
}
