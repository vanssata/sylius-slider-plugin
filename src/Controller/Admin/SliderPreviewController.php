<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Controller\Admin;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;
use Vanssa\SyliusSliderPlugin\Context\Admin\PreviewChannelContext;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Entity\SliderTranslation;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Preview\PreviewBreakpointFlattener;
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
        private PreviewBreakpointFlattener $breakpointFlattener,
        private ?Profiler $profiler = null,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        // The preview is embedded in an iframe; keep the web debug toolbar
        // out of it in dev environments.
        $this->profiler?->disable();

        $slider = $this->sliderRepository->find($id);
        if (!$slider instanceof Slider) {
            throw new NotFoundHttpException(sprintf('Slider "%d" does not exist.', $id));
        }

        $channelCode = (string) $request->query->get('channel', '');
        $channel = '' !== $channelCode ? $this->channelRepository->findOneByCode($channelCode) : null;

        if (!$channel instanceof ChannelInterface && '' === $channelCode) {
            $channel = $this->resolveDefaultChannel($slider);
        }

        if (!$channel instanceof ChannelInterface) {
            throw new NotFoundHttpException(sprintf('Channel "%s" does not exist.', $channelCode));
        }

        $localeCode = (string) $request->query->get('locale', '');
        if ('' === $localeCode) {
            $localeCode = $channel->getDefaultLocale()?->getCode() ?? 'en_US';
        }

        $fallbackLocaleCode = $channel->getDefaultLocale()?->getCode();

        $overrides = (string) ($request->request->get('overrides') ?? $request->query->get('overrides', ''));
        $this->applyDraftOverrides($slider, $overrides, $localeCode);

        // Bake the requested breakpoint into the settings server-side: the
        // preview frame shares the admin page's viewport, so media queries
        // and the shop's matchMedia JS can never select tablet/mobile here.
        $breakpoint = PreviewBreakpointFlattener::normalize((string) $request->query->get('breakpoint', 'desktop'));
        $this->flattenForBreakpoint($slider, $localeCode, $fallbackLocaleCode, $breakpoint);

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
            // Media lives in entity columns, not settings, so the flattener
            // above cannot bake it in — the component resolves it per
            // breakpoint instead.
            'breakpoint' => $breakpoint,
        ]));
    }

    /**
     * No channel was requested (the preview modal only asks for a locale and
     * a resolution): use the slider's first associated channel, or the first
     * enabled channel when the slider isn't restricted to any.
     */
    private function resolveDefaultChannel(Slider $slider): ?ChannelInterface
    {
        foreach ($slider->getChannelCodes() as $channelCode) {
            $channel = $this->channelRepository->findOneByCode($channelCode);
            if ($channel instanceof ChannelInterface) {
                return $channel;
            }
        }

        foreach ($this->channelRepository->findEnabled() as $channel) {
            if ($channel instanceof ChannelInterface) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * Applies unsaved form edits (captured client-side and passed as a raw,
     * bracket-notation query string, matching how the slider form itself
     * would submit) on top of the persisted settings — in memory only, never
     * flushed, so the preview reflects the current draft without saving it.
     */
    private function applyDraftOverrides(Slider $slider, string $overrides, string $localeCode): void
    {
        if ('' === $overrides) {
            return;
        }

        parse_str($overrides, $parsed);

        $sliderData = is_array($parsed['slider'] ?? null) ? $parsed['slider'] : [];

        $settingsOverride = is_array($sliderData['settings'] ?? null) ? $sliderData['settings'] : [];
        if ([] !== $settingsOverride) {
            $slider->setSettings(array_replace_recursive($slider->getSettings(), $settingsOverride));
        }

        // Unsaved per-locale overrides (slider[translations][<locale>][settings])
        // must show up too — apply them onto the locale's translation in
        // memory, mirroring what saving would persist.
        $translationsData = is_array($sliderData['translations'] ?? null) ? $sliderData['translations'] : [];
        $localeData = is_array($translationsData[$localeCode] ?? null) ? $translationsData[$localeCode] : [];
        $translationSettings = is_array($localeData['settings'] ?? null) ? $localeData['settings'] : [];
        if ([] !== $translationSettings) {
            $translation = $slider->getTranslation($localeCode);
            if ($translation instanceof SliderTranslation) {
                $translation->setSettings(array_replace_recursive($translation->getSettings(), $translationSettings));
            }
        }
    }

    /**
     * Replaces the slider's (and each of its slides') settings with the
     * effective values of ONE breakpoint at ONE locale — in memory only. The
     * per-locale translation settings are cleared afterwards so the shop
     * component's own locale merge does not re-apply raw responsive
     * overrides on top of the flattened result.
     */
    private function flattenForBreakpoint(Slider $slider, string $localeCode, ?string $fallbackLocaleCode, string $breakpoint): void
    {
        $slider->setSettings($this->breakpointFlattener->flattenSliderSettings(
            $slider->getLocalizedSettings($localeCode, $fallbackLocaleCode),
            $breakpoint,
        ));

        $translation = $slider->getTranslation($localeCode, $fallbackLocaleCode);
        if ($translation instanceof SliderTranslation) {
            $translation->setSettings([]);
        }

        foreach ($slider->getSlides() as $slide) {
            $slide->setSlideSettings($this->breakpointFlattener->flattenSlideSettings(
                $slide->getLocalizedSlideSettings($localeCode, $fallbackLocaleCode),
                $breakpoint,
            ));

            foreach (array_unique(array_filter([$localeCode, $fallbackLocaleCode])) as $locale) {
                $slideTranslation = $slide->getTranslation($locale, null);
                if ($slideTranslation instanceof SlideTranslation) {
                    $slideTranslation->setSlideSettings([]);
                }
            }
        }
    }
}
