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
        $bundledImage = $pluginRoot . '/assets/fixtures/images/desktop-1.webp';
        $bundledVideo = $pluginRoot . '/assets/fixtures/videos/big-buck-bunny.mp4';

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

        self::assertCount(7, $persistedSlides);

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

        foreach (['runway-video'] as $code) {
            $video = $byCode[$code]->getSlideCoverVideo();
            self::assertNotNull($video, sprintf('Slide "%s" must get an uploaded video.', $code));
            self::assertStringStartsWith('/media/fixtures/videos/', (string) $video);
            self::assertFileExists($this->projectDir . '/public' . $video);
        }

        foreach (['new-collection', 'summer-dresses', 'denim-essentials', 'graphic-tees', 'street-caps', 'season-sale'] as $code) {
            self::assertNull($byCode[$code]->getSlideCoverVideo(), sprintf('Slide "%s" must not get an uploaded video.', $code));
        }

        self::assertFileExists($bundledImage, 'Fixture load must not move the bundled image out of the plugin.');
        self::assertFileExists($bundledVideo, 'Fixture load must not move the bundled video out of the plugin.');
    }

    public function testItAppliesConfiguredStylePresetsToSlidersAndSlides(): void
    {
        $stylePresets = [
            'slider' => [
                'classic_arrows' => [
                    'label' => 'Classic Arrows',
                    'settings' => [
                        'settings.paginationStyle' => 'dots',
                        'settings.autoplay.enabled' => false,
                    ],
                ],
            ],
            'slide' => [
                'hero_dark' => [
                    'label' => 'Hero Dark',
                    'settings' => [
                        'settings.responsive.desktop.textColor' => 'rgba(255, 255, 255, 1)',
                    ],
                ],
            ],
        ];

        $persistedSliders = [];
        $persistedSlides = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persistedSliders, &$persistedSlides): void {
                if ($entity instanceof Slide) {
                    $persistedSlides[] = $entity;
                }
                if ($entity instanceof \Vanssa\SyliusSliderPlugin\Entity\Slider) {
                    $persistedSliders[] = $entity;
                }
            },
        );

        $this->createFixture($entityManager, $stylePresets)->load([]);

        $slidesByCode = [];
        foreach ($persistedSlides as $slide) {
            $slidesByCode[$slide->getCode()] = $slide;
        }
        $slidersByCode = [];
        foreach ($persistedSliders as $slider) {
            $slidersByCode[$slider->getCode()] = $slider;
        }

        $slideSettings = $slidesByCode['runway-video']->getSlideSettings();
        $responsive = \is_array($slideSettings['responsive'] ?? null) ? $slideSettings['responsive'] : [];
        $desktop = \is_array($responsive['desktop'] ?? null) ? $responsive['desktop'] : [];
        self::assertSame(
            'rgba(255, 255, 255, 1)',
            $desktop['textColor'] ?? null,
            'Slide style preset "hero_dark" must expand into the runway-video slide settings.',
        );

        $classicArrowsSettings = $slidersByCode['fashion-classic-arrows']->getSettings();
        $autoplay = \is_array($classicArrowsSettings['autoplay'] ?? null) ? $classicArrowsSettings['autoplay'] : [];
        self::assertSame('dots', $classicArrowsSettings['paginationStyle'] ?? null);
        self::assertFalse($autoplay['enabled'] ?? null);
        self::assertFalse($classicArrowsSettings['showTitle'] ?? null, 'Demo sliders must never render a heading.');
    }

    private function createFixture(?EntityManagerInterface $entityManager = null, array $stylePresets = []): SliderDemoFixture
    {
        return new SliderDemoFixture(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $this->createMock(SliderRepository::class),
            $this->createMock(SlideRepository::class),
            new UploadedMediaStorage($this->projectDir),
            $stylePresets,
        );
    }
}
