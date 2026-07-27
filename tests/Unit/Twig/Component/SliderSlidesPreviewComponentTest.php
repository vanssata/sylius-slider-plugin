<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Twig\Component;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Vanssa\SyliusSliderPlugin\Twig\Component\Admin\SliderSlidesPreviewComponent;

final class SliderSlidesPreviewComponentTest extends TestCase
{
    private SliderRepository&MockObject $sliderRepository;

    private SlideRepository&MockObject $slideRepository;

    private EntityManagerInterface&MockObject $entityManager;

    private ChannelContextInterface&MockObject $channelContext;

    private ChannelRepositoryInterface&MockObject $channelRepository;

    protected function setUp(): void
    {
        $this->sliderRepository = $this->createMock(SliderRepository::class);
        $this->slideRepository = $this->createMock(SlideRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->channelRepository = $this->createMock(ChannelRepositoryInterface::class);
    }

    public function testItReturnsTheContextChannelsDefaultLocaleCode(): void
    {
        $channel = $this->channel('en_US');
        $this->channelContext->method('getChannel')->willReturn($channel);

        $this->sliderRepository->expects(self::never())->method('find');

        $component = $this->createComponent();
        $component->sliderId = 1;

        self::assertSame('en_US', $component->getFallbackLocaleCode());
    }

    public function testItFallsBackToTheSlidersOwnChannelsWhenContextChannelIsNotFound(): void
    {
        $this->channelContext
            ->method('getChannel')
            ->willThrowException(new ChannelNotFoundException())
        ;

        $slider = new Slider();
        $slider->setChannelCodes(['WEB']);
        $this->sliderRepository->method('find')->with(1)->willReturn($slider);

        $webChannel = $this->channel('fr_FR');
        $this->channelRepository
            ->method('findOneByCode')
            ->with('WEB')
            ->willReturn($webChannel)
        ;

        $component = $this->createComponent();
        $component->sliderId = 1;

        self::assertSame('fr_FR', $component->getFallbackLocaleCode());
    }

    public function testItFallsThroughToTheSlidersChannelsWhenContextChannelHasNoDefaultLocale(): void
    {
        $channelWithoutLocale = $this->createMock(ChannelInterface::class);
        $channelWithoutLocale->method('getDefaultLocale')->willReturn(null);
        $this->channelContext->method('getChannel')->willReturn($channelWithoutLocale);

        $slider = new Slider();
        $slider->setChannelCodes(['WEB']);
        $this->sliderRepository->method('find')->with(1)->willReturn($slider);

        $webChannel = $this->channel('fr_FR');
        $this->channelRepository
            ->method('findOneByCode')
            ->with('WEB')
            ->willReturn($webChannel)
        ;

        $component = $this->createComponent();
        $component->sliderId = 1;

        self::assertSame('fr_FR', $component->getFallbackLocaleCode());
    }

    public function testItFallsBackToAnyEnabledChannelWhenSliderChannelsYieldNothingUsable(): void
    {
        $this->channelContext
            ->method('getChannel')
            ->willThrowException(new ChannelNotFoundException())
        ;

        // Slider not found at all -> getSlider() returns null -> no channel codes to try.
        $this->sliderRepository->method('find')->with(1)->willReturn(null);

        $enabledChannel = $this->channel('de_DE');
        $this->channelRepository->method('findEnabled')->willReturn([$enabledChannel]);

        $component = $this->createComponent();
        $component->sliderId = 1;

        self::assertSame('de_DE', $component->getFallbackLocaleCode());
    }

    public function testItReturnsNullWhenNoTierYieldsALocale(): void
    {
        $this->channelContext
            ->method('getChannel')
            ->willThrowException(new ChannelNotFoundException())
        ;

        $this->sliderRepository->method('find')->with(1)->willReturn(null);
        $this->channelRepository->method('findEnabled')->willReturn([]);

        $component = $this->createComponent();
        $component->sliderId = 1;

        self::assertNull($component->getFallbackLocaleCode());
    }

    private function createComponent(): SliderSlidesPreviewComponent
    {
        return new SliderSlidesPreviewComponent(
            $this->sliderRepository,
            $this->slideRepository,
            $this->entityManager,
            $this->channelContext,
            $this->channelRepository,
        );
    }

    private function channel(string $localeCode): ChannelInterface&MockObject
    {
        $locale = $this->createMock(LocaleInterface::class);
        $locale->method('getCode')->willReturn($localeCode);

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getDefaultLocale')->willReturn($locale);

        return $channel;
    }
}
