<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Fixture\SliderDemoFixture;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

final class SliderDemoFixtureTest extends TestCase
{
    public function testItHasExpectedName(): void
    {
        $fixture = new SliderDemoFixture(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SliderRepository::class),
            $this->createMock(SlideRepository::class),
        );

        self::assertSame('vanssa_slider_demo', $fixture->getName());
    }

    public function testItLoadsFixturesWithoutExistingResources(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::atLeastOnce())->method('persist');
        $entityManager->expects(self::atLeastOnce())->method('flush');

        $sliderRepository = $this->createMock(SliderRepository::class);
        $sliderRepository->method('findOneBy')->willReturn(null);

        $slideRepository = $this->createMock(SlideRepository::class);
        $slideRepository->method('findOneBy')->willReturn(null);

        $fixture = new SliderDemoFixture($entityManager, $sliderRepository, $slideRepository);

        $fixture->load([]);

        self::assertTrue(true);
    }
}
