<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Twig\Component;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Preset\MockupCatalog;
use Vanssa\SyliusSliderPlugin\Preset\StylePresetProvider;
use Vanssa\SyliusSliderPlugin\Repository\StylePresetRepository;
use Vanssa\SyliusSliderPlugin\Twig\Component\Admin\SlidePreviewPanelComponent;

final class SlidePreviewPanelComponentTest extends TestCase
{
    private RepositoryInterface&MockObject $localeRepository;

    private ChannelRepositoryInterface&MockObject $channelRepository;

    protected function setUp(): void
    {
        $this->localeRepository = $this->createMock(RepositoryInterface::class);
        $this->channelRepository = $this->createMock(ChannelRepositoryInterface::class);
    }

    public function testItReturnsAllEnabledChannelsWhenSlideAndSliderHaveNoChannelRestriction(): void
    {
        $web = $this->channel('WEB');
        $mobile = $this->channel('MOBILE');
        $this->channelRepository->method('findEnabled')->willReturn([$web, $mobile]);

        $slide = new Slide();

        $component = new SlidePreviewPanelComponent($this->localeRepository, $this->channelRepository, $this->createStylePresetProvider());
        $component->slide = $slide;

        self::assertSame([$web, $mobile], $component->getChannels());
    }

    public function testItFiltersToTheSlidesOwnChannelCodesWhenSet(): void
    {
        $web = $this->channel('WEB');
        $mobile = $this->channel('MOBILE');
        $this->channelRepository->method('findEnabled')->willReturn([$web, $mobile]);

        $slide = new Slide();
        $slide->setChannelCodes(['WEB']);

        $component = new SlidePreviewPanelComponent($this->localeRepository, $this->channelRepository, $this->createStylePresetProvider());
        $component->slide = $slide;

        self::assertSame([$web], $component->getChannels());
    }

    public function testItFallsBackToTheParentSlidersChannelCodesWhenSlideHasNone(): void
    {
        $web = $this->channel('WEB');
        $mobile = $this->channel('MOBILE');
        $this->channelRepository->method('findEnabled')->willReturn([$web, $mobile]);

        $slider = new Slider();
        $slider->setChannelCodes(['MOBILE']);

        $slide = new Slide();
        $slide->setSlider($slider);

        $component = new SlidePreviewPanelComponent($this->localeRepository, $this->channelRepository, $this->createStylePresetProvider());
        $component->slide = $slide;

        self::assertSame([$mobile], $component->getChannels());
    }

    private function createStylePresetProvider(): StylePresetProvider
    {
        $repository = $this->createStub(StylePresetRepository::class);
        $repository->method('findEnabledByType')->willReturn([]);

        return new StylePresetProvider([], $repository, new MockupCatalog());
    }

    private function channel(string $code): ChannelInterface&MockObject
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getCode')->willReturn($code);

        return $channel;
    }
}
