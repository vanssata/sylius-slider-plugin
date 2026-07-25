<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Video;

/**
 * External video provider (YouTube, later Vimeo, …). Media columns store
 * either a self-hosted /media/... path or a normalized external URL; a
 * provider recognizes its URLs and turns them into embeddable players.
 *
 * Register implementations with the vanssa_sylius_slider.video_provider tag
 * (autoconfigured) to add a provider — see docs/dev/extending.md.
 */
interface VideoProviderInterface
{
    public function name(): string;

    /** Whether the stored reference (normalized URL) belongs to this provider. */
    public function supports(string $reference): bool;

    /**
     * Turns any accepted user input (watch/short/embed URL…) into the
     * canonical stored form, or null when the input is not recognized.
     */
    public function normalize(string $url): ?string;

    /** Embed player URL for a stored reference. */
    public function embedUrl(string $reference, bool $autoplay = true): string;
}
