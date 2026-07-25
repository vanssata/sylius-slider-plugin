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
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Preview\PreviewBreakpointFlattener;
use Vanssa\SyliusSliderPlugin\Repository\SlideRepository;

final readonly class SlidePreviewController
{
    /**
     * No real locale ever equals this, so passing it as the locale (with no
     * fallback locale) forces every Slide::getLocalized*() lookup to miss
     * every translation and fall through to the slide's own base fields —
     * i.e. "default" means the untranslated, channel-agnostic content.
     */
    private const DEFAULT_LOCALE_SENTINEL = '__default__';

    /**
     * The slide form's unmapped "remove this media" checkboxes (see
     * SlideType/SlideTranslationType), so a slot the admin just cleared with
     * the tile's × disappears from the preview before the form is saved.
     *
     * @var array<string, string> checkbox field => setter
     */
    private const MEDIA_REMOVAL_FIELDS = [
        'slideCoverRemove' => 'setSlideCover',
        'slideCoverMobileRemove' => 'setSlideCoverMobile',
        'slideCoverTabletRemove' => 'setSlideCoverTablet',
        'slideCoverVideoRemove' => 'setSlideCoverVideo',
        'slideCoverVideoMobileRemove' => 'setSlideCoverVideoMobile',
        'slideCoverVideoTabletRemove' => 'setSlideCoverVideoTablet',
    ];

    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param array<int, string> $shopEntrypoints
     */
    public function __construct(
        private SlideRepository $slideRepository,
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

        $slide = $this->slideRepository->find($id);
        if (!$slide instanceof Slide) {
            throw new NotFoundHttpException(sprintf('Slide "%d" does not exist.', $id));
        }

        $channelCode = (string) $request->query->get('channel', '');
        $channel = '' !== $channelCode ? $this->channelRepository->findOneByCode($channelCode) : null;

        if (!$channel instanceof ChannelInterface && '' === $channelCode) {
            $channel = $this->resolveDefaultChannel($slide);
        }

        if (!$channel instanceof ChannelInterface) {
            throw new NotFoundHttpException(sprintf('Channel "%s" does not exist.', $channelCode));
        }

        $localeParam = (string) $request->query->get('locale', '');
        $isDefault = '' === $localeParam || 'default' === $localeParam;
        $channelLocaleCode = $channel->getDefaultLocale()?->getCode() ?? 'en_US';
        $localeCode = $isDefault ? self::DEFAULT_LOCALE_SENTINEL : $localeParam;
        $fallbackLocaleCode = $isDefault ? null : $channelLocaleCode;

        $overrides = (string) ($request->request->get('overrides') ?? $request->query->get('overrides', ''));
        $this->applyDraftOverrides($slide, $overrides, $isDefault, $localeCode);

        // Bake the requested breakpoint in server-side: the preview frame
        // shares the admin page's viewport, so media queries and the shop's
        // matchMedia JS can never select tablet/mobile here.
        $breakpoint = PreviewBreakpointFlattener::normalize((string) $request->query->get('breakpoint', 'desktop'));
        $this->flattenForBreakpoint($slide, $isDefault, $localeCode, $fallbackLocaleCode, $breakpoint);

        $sliderSettings = $slide->getSlider() instanceof Slider
            ? $this->breakpointFlattener->flattenSliderSettings(
                $isDefault
                    ? $slide->getSlider()->getSettings()
                    : $slide->getSlider()->getLocalizedSettings($localeCode, $fallbackLocaleCode),
                $breakpoint,
            )
            : [];

        // Expose the requested channel so PreviewChannelContext resolves it
        // for the shop slide component rendered below.
        $request->attributes->set(PreviewChannelContext::REQUEST_ATTRIBUTE, $channel->getCode());
        $request->setLocale($isDefault ? $channelLocaleCode : $localeCode);

        return new Response($this->twig->render('@VanssaSyliusSliderPlugin/admin/slide/preview.html.twig', [
            'slide' => $slide,
            'channel' => $channel,
            'localeCode' => $localeCode,
            'fallbackLocaleCode' => $fallbackLocaleCode,
            'themeName' => $channel->getThemeName(),
            'shopEntrypoints' => $this->shopEntrypoints,
            'sliderSettings' => $sliderSettings,
            // Media lives in entity columns, not settings, so the flattener
            // above cannot bake it in — the component resolves it per
            // breakpoint instead.
            'breakpoint' => $breakpoint,
        ]));
    }

    /**
     * No channel was requested (the preview modal only asks for a locale and
     * a resolution): use the slide's own channel, its parent slider's first
     * channel, or the first enabled channel when neither is restricted.
     */
    private function resolveDefaultChannel(Slide $slide): ?ChannelInterface
    {
        foreach ($slide->getChannelCodes() as $channelCode) {
            $channel = $this->channelRepository->findOneByCode($channelCode);
            if ($channel instanceof ChannelInterface) {
                return $channel;
            }
        }

        foreach ($slide->getSlider()?->getChannelCodes() ?? [] as $channelCode) {
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
     * bracket-notation query string, matching how the slide form itself would
     * submit) on top of the persisted settings — in memory only, never
     * flushed, so the preview reflects the current draft without saving it.
     * In "default" mode only the base slide's own fields apply; for a
     * specific locale, that locale's translation (created in-memory if it
     * doesn't exist yet) is also overridden, including its "Overwrite"
     * checkboxes, so the preview respects the same granular gating as the
     * real render.
     */
    private function applyDraftOverrides(Slide $slide, string $overrides, bool $isDefault, string $locale): void
    {
        if ('' === $overrides) {
            return;
        }

        parse_str($overrides, $parsed);
        $slideData = is_array($parsed['slide'] ?? null) ? $parsed['slide'] : [];

        $baseSettings = is_array($slideData['settings'] ?? null) ? $slideData['settings'] : [];
        if ([] !== $baseSettings) {
            $slide->setSlideSettings(array_replace_recursive($slide->getSlideSettings(), $baseSettings));
        }

        self::applyMediaRemovals($slide, $slideData);

        if ($isDefault) {
            return;
        }

        $translationsData = is_array($slideData['translations'] ?? null) ? $slideData['translations'] : [];
        $localeData = is_array($translationsData[$locale] ?? null) ? $translationsData[$locale] : [];
        if ([] === $localeData) {
            return;
        }

        $translation = $slide->getTranslation($locale, null);
        if (!$translation instanceof SlideTranslation) {
            return;
        }

        $translationSettings = is_array($localeData['settings'] ?? null) ? $localeData['settings'] : [];
        if ([] !== $translationSettings) {
            $translation->setSlideSettings(array_replace_recursive($translation->getSlideSettings(), $translationSettings));
        }

        $translation->setOverrides([
            'button' => array_key_exists('addButton', $localeData),
            'media' => array_key_exists('overrideMedia', $localeData),
            'layout' => array_key_exists('overrideLayout', $localeData),
            'colors' => array_key_exists('overrideColors', $localeData),
            'effects' => array_key_exists('overrideEffects', $localeData),
            'visibility' => array_key_exists('overrideVisibility', $localeData),
        ]);

        if (isset($localeData['buttonLabel']) && is_string($localeData['buttonLabel'])) {
            $translation->setButtonLabel($localeData['buttonLabel']);
        }

        if (isset($localeData['url']) && is_string($localeData['url'])) {
            $translation->setUrl($localeData['url']);
        }

        self::applyMediaRemovals($translation, $localeData);
    }

    /**
     * An unchecked checkbox submits nothing, so the key's mere presence in the
     * draft means "this slot is flagged for removal".
     *
     * @param array<string, mixed> $data the draft's slide or translation branch
     */
    private static function applyMediaRemovals(Slide|SlideTranslation $target, array $data): void
    {
        foreach (self::MEDIA_REMOVAL_FIELDS as $field => $setter) {
            if (array_key_exists($field, $data)) {
                $target->{$setter}(null);
            }
        }
    }

    /**
     * Replaces the slide's settings with the effective values of ONE
     * breakpoint at ONE locale — in memory only. The involved translations'
     * settings are cleared afterwards so the component's own locale merge
     * does not re-apply raw responsive overrides on top of the flattened
     * result (their text/media columns and override flags stay untouched).
     */
    private function flattenForBreakpoint(Slide $slide, bool $isDefault, string $localeCode, ?string $fallbackLocaleCode, string $breakpoint): void
    {
        $slide->setSlideSettings($this->breakpointFlattener->flattenSlideSettings(
            $slide->getLocalizedSlideSettings($localeCode, $fallbackLocaleCode),
            $breakpoint,
        ));

        if ($isDefault) {
            return;
        }

        foreach (array_unique(array_filter([$localeCode, $fallbackLocaleCode])) as $locale) {
            $translation = $slide->getTranslation($locale, null);
            if ($translation instanceof SlideTranslation) {
                $translation->setSlideSettings([]);
            }
        }
    }
}
