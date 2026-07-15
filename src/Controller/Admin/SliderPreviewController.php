<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Controller\Admin;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Vanssa\SyliusSliderPlugin\Context\Admin\PreviewChannelContext;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Repository\SliderRepository;

final readonly class SliderPreviewController
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param array<int, string> $shopEntrypoints
     */
    public function __construct(
        private SliderRepository $sliderRepository,
        #[Autowire(service: 'sylius.repository.channel')]
        private ChannelRepositoryInterface $channelRepository,
        private Environment $twig,
        #[Autowire(param: 'vanssa_sylius_slider.preview.shop_entrypoints')]
        private array $shopEntrypoints,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $slider = $this->sliderRepository->find($id);
        if (!$slider instanceof Slider) {
            throw new NotFoundHttpException(sprintf('Slider "%d" does not exist.', $id));
        }

        $channelCode = (string) $request->query->get('channel', '');
        $channel = '' !== $channelCode ? $this->channelRepository->findOneByCode($channelCode) : null;
        if (!$channel instanceof ChannelInterface) {
            throw new NotFoundHttpException(sprintf('Channel "%s" does not exist.', $channelCode));
        }

        $localeCode = (string) $request->query->get('locale', '');
        if ('' === $localeCode) {
            $localeCode = $channel->getDefaultLocale()?->getCode() ?? 'en_US';
        }

        $fallbackLocaleCode = $channel->getDefaultLocale()?->getCode();

        // Expose the requested channel so PreviewChannelContext resolves it
        // for the shop slider component rendered below.
        $request->attributes->set(PreviewChannelContext::REQUEST_ATTRIBUTE, $channel->getCode());
        $request->setLocale($localeCode);

        return new Response($this->twig->render('@VanssaSyliusSliderPlugin/admin/slider/preview.html.twig', [
            'slider' => $slider,
            'channel' => $channel,
            'localeCode' => $localeCode,
            'fallbackLocaleCode' => $fallbackLocaleCode,
            'themeName' => $channel->getThemeName(),
            'shopEntrypoints' => $this->shopEntrypoints,
        ]));
    }
}
