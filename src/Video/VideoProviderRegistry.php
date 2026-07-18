<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Video;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class VideoProviderRegistry
{
    /**
     * @param iterable<VideoProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('vanssa_sylius_slider.video_provider')]
        private readonly iterable $providers,
    ) {
    }

    /** Provider owning an already-stored reference (normalized URL). */
    public function providerFor(string $reference): ?VideoProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($reference)) {
                return $provider;
            }
        }

        return null;
    }

    /** Normalizes arbitrary user input through the first accepting provider. */
    public function normalize(string $url): ?string
    {
        foreach ($this->providers as $provider) {
            $normalized = $provider->normalize($url);
            if (null !== $normalized) {
                return $normalized;
            }
        }

        return null;
    }

    public function isExternal(string $reference): bool
    {
        return null !== $this->providerFor($reference);
    }

    public function embedUrl(string $reference, bool $autoplay = true): ?string
    {
        return $this->providerFor($reference)?->embedUrl($reference, $autoplay);
    }
}
