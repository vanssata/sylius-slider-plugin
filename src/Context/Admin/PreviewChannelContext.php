<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Context\Admin;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the channel for admin slider preview requests, where no
 * shop channel context is available. The preview controller stores the
 * requested channel code as a request attribute.
 */
final class PreviewChannelContext implements ChannelContextInterface
{
    public const REQUEST_ATTRIBUTE = '_vanssa_slider_preview_channel';

    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'sylius.repository.channel')]
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {
    }

    public function getChannel(): ChannelInterface
    {
        $request = $this->requestStack->getMainRequest();
        $channelCode = $request?->attributes->get(self::REQUEST_ATTRIBUTE);

        if (!is_string($channelCode) || '' === $channelCode) {
            throw new ChannelNotFoundException('No slider preview channel requested.');
        }

        $channel = $this->channelRepository->findOneByCode($channelCode);
        if (!$channel instanceof ChannelInterface) {
            throw new ChannelNotFoundException(sprintf('Preview channel "%s" does not exist.', $channelCode));
        }

        return $channel;
    }
}
