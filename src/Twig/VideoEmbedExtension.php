<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Twig;

use Twig\Attribute\AsTwigFunction;
use Vanssa\SyliusSliderPlugin\Video\VideoProviderRegistry;

/**
 * Bridges the video provider registry into the shop/preview templates: a
 * stored video reference is either a self-hosted /media/... path (function
 * returns null → render a <video> tag) or an external provider URL
 * (returns the embed player URL → render an <iframe>).
 */
final readonly class VideoEmbedExtension
{
    public function __construct(
        private VideoProviderRegistry $videoProviderRegistry,
    ) {
    }

    #[AsTwigFunction('vanssa_video_embed_url')]
    public function embedUrl(?string $reference, bool $autoplay = true): ?string
    {
        if (null === $reference || '' === trim($reference)) {
            return null;
        }

        return $this->videoProviderRegistry->embedUrl($reference, $autoplay);
    }
}
