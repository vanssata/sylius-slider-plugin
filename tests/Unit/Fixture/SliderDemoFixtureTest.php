<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Fixture\SliderDemoFixture;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;

final class SliderDemoFixtureTest extends TestCase
{
    public function testItHasExpectedName(): void
    {
        $fixture = new SliderDemoFixture(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SliderRepository::class),
            $this->createMock(SlideRepository::class),
            new UploadedMediaStorage(sys_get_temp_dir()),
        );

        self::assertSame('vanssa_slider_demo', $fixture->getName());
    }

    public function testItCanBeConstructedWithDependencies(): void
    {
        $fixture = new SliderDemoFixture(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SliderRepository::class),
            $this->createMock(SlideRepository::class),
            new UploadedMediaStorage(sys_get_temp_dir()),
        );

        self::assertInstanceOf(SliderDemoFixture::class, $fixture);
    }
}
