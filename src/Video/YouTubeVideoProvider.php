<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Video;

final class YouTubeVideoProvider implements VideoProviderInterface
{
    private const ID_PATTERN = '[A-Za-z0-9_-]{6,20}';

    public function name(): string
    {
        return 'youtube';
    }

    public function supports(string $reference): bool
    {
        return null !== $this->extractId($reference);
    }

    public function normalize(string $url): ?string
    {
        $id = $this->extractId($url);

        return null === $id ? null : sprintf('https://www.youtube.com/watch?v=%s', $id);
    }

    public function embedUrl(string $reference, bool $autoplay = true): string
    {
        $id = $this->extractId($reference) ?? '';

        // Privacy-enhanced host; muted + playsinline + no controls mirror the
        // self-hosted background-video behavior; enablejsapi lets the slider
        // observe playback (visibility gating, autoplay-until-ended).
        return sprintf(
            'https://www.youtube-nocookie.com/embed/%s?%s',
            $id,
            http_build_query([
                'autoplay' => $autoplay ? 1 : 0,
                'mute' => 1,
                'playsinline' => 1,
                'controls' => 0,
                'rel' => 0,
                'enablejsapi' => 1,
            ]),
        );
    }

    private function extractId(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url) {
            return null;
        }

        $patterns = [
            '#^https?://(?:www\.|m\.)?youtube(?:-nocookie)?\.com/watch\?(?:.*&)?v=(' . self::ID_PATTERN . ')#',
            '#^https?://(?:www\.|m\.)?youtube(?:-nocookie)?\.com/(?:embed|shorts|live)/(' . self::ID_PATTERN . ')#',
            '#^https?://youtu\.be/(' . self::ID_PATTERN . ')#',
        ];

        foreach ($patterns as $pattern) {
            if (1 === preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
