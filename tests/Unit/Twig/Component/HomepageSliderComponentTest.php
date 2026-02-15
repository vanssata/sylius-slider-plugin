<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Twig\Component;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Vanssa\SyliusSliderPlugin\Twig\Component\Shop\HomepageSliderComponent;

final class HomepageSliderComponentTest extends TestCase
{
    private SliderRepository&MockObject $sliderRepository;
    private LocaleContextInterface&MockObject $localeContext;
    private ChannelContextInterface&MockObject $channelContext;
    private ChannelInterface&MockObject $channel;
    private LocaleInterface&MockObject $locale;

    protected function setUp(): void
    {
        $this->sliderRepository = $this->createMock(SliderRepository::class);
        $this->localeContext = $this->createMock(LocaleContextInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->locale = $this->createMock(LocaleInterface::class);
    }

    public function testItLoadsSliderByCode(): void
    {
        $slider = new Slider();
        $slider->setCode('homepage-main');
        $slider->setName('Homepage slider');

        $this->sliderRepository
            ->expects(self::once())
            ->method('findEnabledOneByCodeForChannel')
            ->with('homepage-main', 'WEB-US', 'en_US', 'en_US')
            ->willReturn($slider)
        ;

        $this->channelContext
            ->method('getChannel')
            ->willReturn($this->channel)
        ;
        $this->channel
            ->method('getCode')
            ->willReturn('WEB-US')
        ;
        $this->channel
            ->method('getDefaultLocale')
            ->willReturn($this->locale)
        ;
        $this->locale
            ->method('getCode')
            ->willReturn('en_US')
        ;
        $this->localeContext
            ->method('getLocaleCode')
            ->willReturn('en_US')
        ;

        $component = new HomepageSliderComponent($this->sliderRepository, $this->localeContext, $this->channelContext);
        $component->code = 'homepage-main';

        self::assertSame($slider, $component->getSlider());
    }
}
