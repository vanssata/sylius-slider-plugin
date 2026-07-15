<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Fixture\SliderDemoFixture;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Vanssa\SyliusSliderPlugin\Service\UploadedMediaStorage;

final class SliderDemoFixtureTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/vanssa_slider_fixture_test_' . bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0775, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->projectDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->projectDir);
        }
    }

    public function testItHasExpectedName(): void
    {
        self::assertSame('vanssa_slider_demo', $this->createFixture()->getName());
    }

    public function testItUploadsBundledMediaWithoutConsumingPluginAssets(): void
    {
        $pluginRoot = \dirname(__DIR__, 3);
        $bundledImage = $pluginRoot . '/assets/fixtures/images/desktop-1.jpg';
        $bundledVideo = $pluginRoot . '/assets/fixtures/videos/autonomous-loop.mp4';

        self::assertFileExists($bundledImage, 'Bundled fixture image must ship with the plugin.');
        self::assertFileExists($bundledVideo, 'Bundled fixture video must ship with the plugin.');

        $persistedSlides = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persistedSlides): void {
                if ($entity instanceof Slide) {
                    $persistedSlides[] = $entity;
                }
            },
        );

        $this->createFixture($entityManager)->load([]);

        self::assertCount(9, $persistedSlides);

        $byCode = [];
        foreach ($persistedSlides as $slide) {
            $byCode[$slide->getCode()] = $slide;
        }

        foreach ($byCode as $code => $slide) {
            self::assertNotNull($slide->getSlideCover(), sprintf('Slide "%s" must get a desktop cover.', $code));
            self::assertNotNull($slide->getSlideCoverMobile(), sprintf('Slide "%s" must get a mobile cover.', $code));
            self::assertFileExists(
                $this->projectDir . '/public' . $slide->getSlideCover(),
                sprintf('Uploaded desktop cover of slide "%s" must exist.', $code),
            );
        }

        foreach (['autonomous-loop', 'charging-network', 'big-buck-bunny'] as $code) {
            $video = $byCode[$code]->getSlideCoverVideo();
            self::assertNotNull($video, sprintf('Slide "%s" must get an uploaded video.', $code));
            self::assertStringStartsWith('/media/fixtures/videos/', (string) $video);
            self::assertFileExists($this->projectDir . '/public' . $video);
        }

        self::assertFileExists($bundledImage, 'Fixture load must not move the bundled image out of the plugin.');
        self::assertFileExists($bundledVideo, 'Fixture load must not move the bundled video out of the plugin.');
    }

    private function createFixture(?EntityManagerInterface $entityManager = null): SliderDemoFixture
    {
        return new SliderDemoFixture(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $this->createMock(SliderRepository::class),
            $this->createMock(SlideRepository::class),
            new UploadedMediaStorage($this->projectDir),
        );
    }
}
