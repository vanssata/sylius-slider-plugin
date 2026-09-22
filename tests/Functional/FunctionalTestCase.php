<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    /**
     * Ids of the channels ensureChannel() created in this test, removed again in tearDown().
     * CI runs this suite before Behat on the same database, and a channel left behind
     * turns off Sylius' single-channel fallback for every Behat scenario.
     *
     * @var list<mixed>
     */
    private array $createdChannelIds = [];

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    protected function tearDown(): void
    {
        $this->removeCreatedChannels();

        parent::tearDown();
    }

    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        return $entityManager;
    }

    protected function logInAsAdmin(): AdminUserInterface
    {
        $entityManager = $this->entityManager();

        $admin = $entityManager->getRepository(AdminUser::class)->findOneBy(['username' => 'functional-admin']);
        if (!$admin instanceof AdminUserInterface) {
            $admin = new AdminUser();
            $admin->setUsername('functional-admin');
            $admin->setEmail('functional-admin@example.com');
            $admin->setPassword('not-used-by-login-user');
            $admin->setLocaleCode('en_US');
            $admin->setEnabled(true);
            $entityManager->persist($admin);
            $entityManager->flush();
        }

        $this->client->loginUser($admin, 'admin');

        return $admin;
    }

    protected function ensureChannel(string $code = 'FUNCTIONAL', string $hostname = 'localhost'): ChannelInterface
    {
        $entityManager = $this->entityManager();

        $channel = $entityManager->getRepository(Channel::class)->findOneBy(['code' => $code]);
        if ($channel instanceof ChannelInterface) {
            return $channel;
        }

        $currency = $entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']);
        if (!$currency instanceof CurrencyInterface) {
            $currency = new Currency();
            $currency->setCode('USD');
            $entityManager->persist($currency);
        }

        $locale = $entityManager->getRepository(Locale::class)->findOneBy(['code' => 'en_US']);
        if (!$locale instanceof LocaleInterface) {
            $locale = new Locale();
            $locale->setCode('en_US');
            $entityManager->persist($locale);
        }

        $channel = new Channel();
        $channel->setCode($code);
        $channel->setName('Functional Channel');
        $channel->setHostname($hostname);
        $channel->setEnabled(true);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setBaseCurrency($currency);
        $channel->setDefaultLocale($locale);
        $channel->addCurrency($currency);
        $channel->addLocale($locale);

        $entityManager->persist($channel);
        $entityManager->flush();

        $this->createdChannelIds[] = $channel->getId();

        return $channel;
    }

    private function removeCreatedChannels(): void
    {
        if ([] === $this->createdChannelIds) {
            return;
        }

        $channelIds = $this->createdChannelIds;
        $this->createdChannelIds = [];

        $doctrine = self::getContainer()->get('doctrine');
        \assert($doctrine instanceof ManagerRegistry);

        $entityManager = $doctrine->getManager();
        \assert($entityManager instanceof EntityManagerInterface);
        if (!$entityManager->isOpen()) {
            $entityManager = $doctrine->resetManager();
            \assert($entityManager instanceof EntityManagerInterface);
        }

        // Drop whatever the test left unflushed; only the channels are removed.
        $entityManager->clear();

        foreach ($channelIds as $channelId) {
            $channel = $entityManager->find(Channel::class, $channelId);
            if (null !== $channel) {
                $entityManager->remove($channel);
            }
        }

        $entityManager->flush();
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function createSlider(string $code, array $settings = [], int $slideCount = 2): Slider
    {
        $entityManager = $this->entityManager();

        $slider = $entityManager->getRepository(Slider::class)->findOneBy(['code' => $code]);
        if (!$slider instanceof Slider) {
            $slider = new Slider();
            $slider->setCode($code);
            $entityManager->persist($slider);
        }

        $slider->setName('Slider ' . $code);
        $slider->setEnabled(true);
        $slider->setSettings(array_merge($slider->getSettings(), $settings));

        for ($i = 1; $i <= $slideCount; ++$i) {
            $slideCode = sprintf('%s-slide-%d', $code, $i);
            $slide = $entityManager->getRepository(Slide::class)->findOneBy(['code' => $slideCode]);
            if (!$slide instanceof Slide) {
                $slide = new Slide();
                $slide->setCode($slideCode);
                $entityManager->persist($slide);
            }

            $slide->setName('Slide ' . $i);
            $slide->setEnabled(true);
            $slide->setPosition($i);
            $slide->setSlideCover(sprintf('/media/functional/%s.jpg', $slideCode));
            $slider->addSlide($slide);
        }

        $entityManager->flush();

        return $slider;
    }
}
