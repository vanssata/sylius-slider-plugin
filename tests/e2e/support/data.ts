/**
 * Fixture data contract for the e2e suite.
 *
 * Everything here comes from the `vanssa_sylius_slider_demo` fixture suite
 * (config/fixtures.yaml). Reload it with `make load-slider-fixtures` if a spec
 * ever leaves the dataset dirty.
 *
 * Sylius admin credentials are the stock Sylius fixture user.
 */

export const ADMIN = {
    email: 'sylius@example.com',
    password: 'sylius',
} as const;

export const SLIDERS = {
    /** Arrows + pagination, 4 slides — the canonical storefront fixture. */
    classicArrows: 'fashion-classic-arrows',
    minimalFade: 'fashion-minimal-fade',
    autoplayShowcase: 'fashion-autoplay-showcase',
    fullscreenHero: 'fashion-fullscreen-hero',
    compactBanner: 'fashion-compact-banner',
    parallaxShowcase: 'fashion-parallax-showcase',
} as const;

export const SLIDES = {
    /** First slide of `fashion-classic-arrows`. */
    newCollection: 'new-collection',
    summerDresses: 'summer-dresses',
    denimEssentials: 'denim-essentials',
    graphicTees: 'graphic-tees',
    streetCaps: 'street-caps',
    seasonSale: 'season-sale',
    /** Video slide (self-hosted / YouTube handling). */
    runwayVideo: 'runway-video',
} as const;

/**
 * The canonical slide order of `fashion-classic-arrows` as the demo fixture
 * creates it (src/Fixture/SliderDemoFixture.php).
 *
 * This matters beyond bookkeeping: the shop template marks slide index 0
 * `is-active`, and `slider_controller` sets `aria-hidden="true"` on every other
 * slide — so ARIA snapshots and axe scans rooted on a specific slide only see
 * anything while that slide is first. Any spec that reorders slides must
 * restore THIS order, not merely whatever it happened to find.
 */
export const CLASSIC_ARROWS_SLIDE_ORDER = [
    SLIDES.newCollection,
    SLIDES.summerDresses,
    SLIDES.denimEssentials,
    SLIDES.graphicTees,
] as const;

/** Breakpoint bands from templates/components/vanssa_sylius_slider/shop/slide.html.twig. */
export const VIEWPORTS = {
    desktop: { width: 1400, height: 900 },
    /** inside `@media (max-width: 1024px)` */
    tablet: { width: 820, height: 1180 },
    /** inside `@media (max-width: 767px)` */
    mobile: { width: 390, height: 844 },
} as const;

export const routes = {
    shopSlider: (code: string) => `/slider/${code}`,
    shopBanner: (code: string) => `/banner/${code}`,
    adminSliderIndex: '/admin/sliders/',
    adminSliderNew: '/admin/sliders/new',
    adminSliderEdit: (id: number | string) => `/admin/sliders/${id}/edit`,
    adminSliderPreview: (id: number | string) => `/admin/sliders/${id}/preview`,
    adminSlideIndex: '/admin/slides/',
    adminSlideEdit: (id: number | string) => `/admin/slides/${id}/edit`,
    adminSlideEditPanel: (id: number | string) => `/admin/slides/${id}/edit-panel`,
    adminStylePresetIndex: '/admin/style-presets/',
} as const;
