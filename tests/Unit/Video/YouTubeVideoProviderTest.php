<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Unit\Video;

use PHPUnit\Framework\TestCase;
use Vanssa\SyliusSliderPlugin\Video\VideoProviderRegistry;
use Vanssa\SyliusSliderPlugin\Video\YouTubeVideoProvider;

final class YouTubeVideoProviderTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function urlProvider(): iterable
    {
        yield 'watch url' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'watch url with extra params' => ['https://www.youtube.com/watch?t=42&v=dQw4w9WgXcQ&list=x', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'short link' => ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'short link with timestamp' => ['https://youtu.be/dQw4w9WgXcQ?t=10', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'embed url' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'shorts url' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'nocookie embed' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'mobile url' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'no scheme rejected' => ['www.youtube.com/watch?v=dQw4w9WgXcQ', null];
        yield 'self-hosted path rejected' => ['/media/slider/base-cover-video/abc.mp4', null];
        yield 'other host rejected' => ['https://vimeo.com/123456789', null];
        yield 'empty rejected' => ['', null];
        yield 'garbage rejected' => ['not a url', null];
    }

    /**
     * @dataProvider urlProvider
     */
    public function testNormalize(string $input, ?string $expected): void
    {
        self::assertSame($expected, (new YouTubeVideoProvider())->normalize($input));
    }

    public function testEmbedUrlUsesPrivacyEnhancedHostAndJsApi(): void
    {
        $embed = (new YouTubeVideoProvider())->embedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        self::assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?', $embed);
        self::assertStringContainsString('autoplay=1', $embed);
        self::assertStringContainsString('mute=1', $embed);
        self::assertStringContainsString('enablejsapi=1', $embed);
    }

    public function testEmbedUrlWithoutAutoplay(): void
    {
        $embed = (new YouTubeVideoProvider())->embedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false);

        self::assertStringContainsString('autoplay=0', $embed);
    }

    public function testRegistryDetectsExternalReferences(): void
    {
        $registry = new VideoProviderRegistry([new YouTubeVideoProvider()]);

        self::assertTrue($registry->isExternal('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        self::assertFalse($registry->isExternal('/media/slider/base-cover-video/abc.mp4'));
        self::assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $registry->normalize('https://youtu.be/dQw4w9WgXcQ'));
        self::assertNull($registry->normalize('https://example.com/video.mp4'));
    }
}
