<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Twig;

use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;
use Vanssa\SyliusSliderPlugin\Twig\SliderExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class SliderExtensionTest extends TestCase
{
    private SliderRepository&MockObject $sliderRepository;

    private SlideRepository&MockObject $slideRepository;

    private Environment $twig;

    private RouterInterface&MockObject $router;

    protected function setUp(): void
    {
        $this->sliderRepository = $this->createMock(SliderRepository::class);
        $this->slideRepository = $this->createMock(SlideRepository::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->twig = new Environment(new ArrayLoader());
    }

    public function testItReturnsRawContentWhenRichEditorFilterIsMissing(): void
    {
        $extension = new SliderExtension($this->sliderRepository, $this->slideRepository, $this->router, $this->twig);

        self::assertSame('<p>Hello</p>', (string) $extension->renderContent('<p>Hello</p>'));
    }

    public function testItUsesRichEditorFilterWhenAvailable(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFilter(new \Twig\TwigFilter('monsieurbiz_richeditor_render_field', static fn (string $v): string => '<div class="rich">' . $v . '</div>', ['is_safe' => ['html']]));

        $extension = new SliderExtension($this->sliderRepository, $this->slideRepository, $this->router, $twig);

        self::assertSame('<div class="rich">hello</div>', (string) $extension->renderContent('hello'));
    }
}
