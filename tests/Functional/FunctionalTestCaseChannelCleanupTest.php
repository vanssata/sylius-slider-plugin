<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional;

use Sylius\Component\Core\Model\Channel;

/**
 * CI runs this suite before Behat on the same database: a channel left behind
 * here disables Sylius' single-channel fallback for every Behat scenario.
 */
final class FunctionalTestCaseChannelCleanupTest extends FunctionalTestCase
{
    public function testTearDownRemovesTheChannelEnsureChannelCreated(): void
    {
        $this->ensureChannel('FUNCTIONAL_CLEANUP');

        $this->tearDown();

        self::assertNull(
            $this->entityManager()->getRepository(Channel::class)->findOneBy(['code' => 'FUNCTIONAL_CLEANUP']),
        );
    }

    public function testTearDownKeepsAChannelItDidNotCreate(): void
    {
        $owned = $this->ensureChannel('FUNCTIONAL_OWNED');

        $entityManager = $this->entityManager();
        $preexisting = new Channel();
        $preexisting->setCode('FUNCTIONAL_PREEXISTING');
        $preexisting->setName('Pre-existing Channel');
        $preexisting->setHostname('preexisting.example');
        $preexisting->setTaxCalculationStrategy('order_items_based');
        $preexisting->setBaseCurrency($owned->getBaseCurrency());
        $preexisting->setDefaultLocale($owned->getDefaultLocale());
        $entityManager->persist($preexisting);
        $entityManager->flush();

        try {
            // Found, not created: this call does not take ownership of it.
            $this->ensureChannel('FUNCTIONAL_PREEXISTING');

            $this->tearDown();

            $repository = $this->entityManager()->getRepository(Channel::class);
            self::assertNull($repository->findOneBy(['code' => 'FUNCTIONAL_OWNED']));
            self::assertNotNull($repository->findOneBy(['code' => 'FUNCTIONAL_PREEXISTING']));
        } finally {
            $this->removeChannel('FUNCTIONAL_PREEXISTING');
        }
    }

    private function removeChannel(string $code): void
    {
        $entityManager = $this->entityManager();
        $channel = $entityManager->getRepository(Channel::class)->findOneBy(['code' => $code]);
        if (null !== $channel) {
            $entityManager->remove($channel);
            $entityManager->flush();
        }
    }
}
